<?php

namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use App\Services\Mikrotik\Concerns\ClassifiesConnectionStatus;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\DTO\ConnectionTestResult;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * MikrotikServiceInterface implementation backed by the RouterOS REST API
 * (RouterOS >= 7.1, served by the router's own web server over HTTP/HTTPS).
 *
 * Reference: https://help.mikrotik.com/docs/display/ROS/REST+API
 */
class RouterOsRestService implements MikrotikServiceInterface
{
    use ClassifiesConnectionStatus;

    public function __construct(private readonly Mikrotik $mikrotik) {}

    public function testConnection(): ConnectionTestResult
    {
        $start = microtime(true);

        try {
            $this->get('/system/resource');
        } catch (Throwable $e) {
            return ConnectionTestResult::failure($this->cleanMessage($e));
        }

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);

        return ConnectionTestResult::success($elapsedMs, $this->classifyStatus($elapsedMs));
    }

    public function getRouterInfo(): array
    {
        $resource = $this->get('/system/resource');
        $identity = $this->get('/system/identity');

        return [
            'identity' => $identity['name'] ?? null,
            'board_name' => $resource['board-name'] ?? null,
            'version' => $resource['version'] ?? null,
            'uptime' => $resource['uptime'] ?? null,
            'cpu_load' => $resource['cpu-load'] ?? null,
            'free_memory' => $resource['free-memory'] ?? null,
            'total_memory' => $resource['total-memory'] ?? null,
            'free_hdd_space' => $resource['free-hdd-space'] ?? null,
            'architecture' => $resource['architecture-name'] ?? null,
        ];
    }

    public function getHotspotProfiles(): array
    {
        return $this->get('/ip/hotspot/user/profile');
    }

    public function getHotspotUsers(): array
    {
        return $this->get('/ip/hotspot/user');
    }

    public function getIpPools(): array
    {
        return $this->get('/ip/pool');
    }

    public function createHotspotProfile(array $data): array
    {
        $payload = ['name' => $data['name']];

        if (! empty($data['address_pool']) && $data['address_pool'] !== 'none') {
            $payload['address-pool'] = $data['address_pool'];
        }

        if (! empty($data['shared_users'])) {
            $payload['shared-users'] = (string) $data['shared_users'];
        }

        if (! empty($data['rate_limit'])) {
            $payload['rate-limit'] = $data['rate_limit'];
        }

        if (! empty($data['session_timeout'])) {
            $payload['session-timeout'] = $data['session_timeout'];
        }

        if (! empty($data['parent_queue']) && $data['parent_queue'] !== 'none') {
            $payload['parent-queue'] = $data['parent_queue'];
        }

        return $this->put('/ip/hotspot/user/profile', $payload);
    }

    public function getIpBindings(): array
    {
        return $this->get('/ip/hotspot/ip-binding');
    }

    public function createIpBinding(array $data): array
    {
        $payload = ['mac-address' => $data['mac_address']];

        if (! empty($data['type'])) {
            $payload['type'] = $data['type'];
        }

        if (! empty($data['name'])) {
            $payload['comment'] = $data['name'];
        }

        if (! empty($data['address'])) {
            $payload['address'] = $data['address'];
        }

        if (! empty($data['to_address'])) {
            $payload['to-address'] = $data['to_address'];
        }

        return $this->put('/ip/hotspot/ip-binding', $payload);
    }

    public function getActiveHotspotUsers(): array
    {
        return $this->get('/ip/hotspot/active');
    }

    public function findHotspotUser(string $username): ?array
    {
        $rows = $this->get('/ip/hotspot/user', ['name' => $username]);

        return $rows[0] ?? null;
    }

    public function createHotspotUser(array $data): array
    {
        $payload = ['name' => $data['name'], 'password' => $data['password']];

        if (! empty($data['profile'])) {
            $payload['profile'] = $data['profile'];
        }

        if (! empty($data['limit_uptime'])) {
            $payload['limit-uptime'] = $data['limit_uptime'];
        }

        if (! empty($data['comment'])) {
            $payload['comment'] = $data['comment'];
        }

        return $this->put('/ip/hotspot/user', $payload);
    }

    public function updateHotspotUser(string $username, array $data): array
    {
        $id = $this->findUserId($username);

        $payload = [];

        if (array_key_exists('password', $data)) {
            $payload['password'] = $data['password'];
        }

        if (array_key_exists('profile', $data)) {
            $payload['profile'] = $data['profile'];
        }

        if (array_key_exists('limit_uptime', $data)) {
            $payload['limit-uptime'] = $data['limit_uptime'];
        }

        if (array_key_exists('comment', $data)) {
            $payload['comment'] = $data['comment'];
        }

        return $this->patch("/ip/hotspot/user/{$id}", $payload);
    }

    public function disableHotspotUser(string $username): bool
    {
        $id = $this->findUserId($username);

        $this->patch("/ip/hotspot/user/{$id}", ['disabled' => 'yes']);

        return true;
    }

    public function deleteHotspotUser(string $username): bool
    {
        $id = $this->findUserId($username);

        $this->delete("/ip/hotspot/user/{$id}");

        return true;
    }

    public function syncUsers(): array
    {
        return $this->getHotspotUsers();
    }

    /**
     * @throws MikrotikConnectionException
     */
    private function findUserId(string $username): string
    {
        $rows = $this->get('/ip/hotspot/user', ['name' => $username]);

        if (empty($rows)) {
            throw new MikrotikConnectionException("Hotspot user '{$username}' was not found on this router.");
        }

        return $rows[0]['.id'] ?? $rows[0]['id'];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws MikrotikConnectionException
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->client()->get($path, $query)->throw();
        } catch (Throwable $e) {
            throw new MikrotikConnectionException($this->cleanMessage($e), previous: $e);
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws MikrotikConnectionException
     */
    private function put(string $path, array $payload): array
    {
        try {
            $response = $this->client()->put($path, $payload)->throw();
        } catch (Throwable $e) {
            throw new MikrotikConnectionException($this->cleanMessage($e), previous: $e);
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws MikrotikConnectionException
     */
    private function patch(string $path, array $payload): array
    {
        try {
            $response = $this->client()->patch($path, $payload)->throw();
        } catch (Throwable $e) {
            throw new MikrotikConnectionException($this->cleanMessage($e), previous: $e);
        }

        return $response->json() ?? [];
    }

    /**
     * @throws MikrotikConnectionException
     */
    private function delete(string $path): void
    {
        try {
            $this->client()->delete($path)->throw();
        } catch (Throwable $e) {
            throw new MikrotikConnectionException($this->cleanMessage($e), previous: $e);
        }
    }

    private function client(): PendingRequest
    {
        $scheme = $this->mikrotik->ssl_enabled ? 'https' : 'http';

        return Http::withBasicAuth($this->mikrotik->username, $this->mikrotik->plainPassword())
            ->baseUrl("{$scheme}://{$this->mikrotik->host}:{$this->mikrotik->port}/rest")
            ->connectTimeout((int) config('mikrotik.connect_timeout'))
            ->timeout((int) config('mikrotik.read_timeout'))
            ->withOptions(['verify' => (bool) config('mikrotik.rest_verify_tls')])
            ->acceptJson()
            ->asJson();
    }

    /**
     * Strips low-level transport noise (and guarantees no credential can
     * ever leak) from an exception message before it is logged or shown.
     */
    private function cleanMessage(Throwable $e): string
    {
        if ($e instanceof RequestException && $e->response->status() === 401) {
            return 'Authentication failed (invalid username/password).';
        }

        return match (true) {
            str_contains($e->getMessage(), 'timed out') => 'Connection timed out.',
            str_contains($e->getMessage(), 'Connection refused') => 'Connection refused.',
            default => 'RouterOS REST API error: '.$e->getMessage(),
        };
    }
}
