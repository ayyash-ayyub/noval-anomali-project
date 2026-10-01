<?php

namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use App\Services\Mikrotik\Concerns\ClassifiesConnectionStatus;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\DTO\ConnectionTestResult;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use Throwable;

/**
 * MikrotikServiceInterface implementation backed by the RouterOS binary
 * API protocol (TCP 8728, or TLS 8729 when ssl_enabled is set).
 */
class RouterOsApiService implements MikrotikServiceInterface
{
    use ClassifiesConnectionStatus;

    public function __construct(private readonly Mikrotik $mikrotik) {}

    public function testConnection(): ConnectionTestResult
    {
        $start = microtime(true);

        try {
            $client = $this->connectedClient();
            $client->query(['/system/resource/print']);
            $client->close();
        } catch (Throwable $e) {
            return ConnectionTestResult::failure($e->getMessage());
        }

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);

        return ConnectionTestResult::success($elapsedMs, $this->classifyStatus($elapsedMs));
    }

    public function getRouterInfo(): array
    {
        $client = $this->connectedClient();

        try {
            $rows = $client->query(['/system/resource/print']);
            $identity = $client->query(['/system/identity/print']);
        } finally {
            $client->close();
        }

        $resource = $rows[0] ?? [];

        return [
            'identity' => $identity[0]['name'] ?? null,
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
        $client = $this->connectedClient();

        try {
            return $client->query(['/ip/hotspot/user/profile/print']);
        } finally {
            $client->close();
        }
    }

    public function getIpPools(): array
    {
        $client = $this->connectedClient();

        try {
            return $client->query(['/ip/pool/print']);
        } finally {
            $client->close();
        }
    }

    public function createHotspotProfile(array $data): array
    {
        $client = $this->connectedClient();

        try {
            $sentence = ['/ip/hotspot/user/profile/add', '=name='.$data['name']];

            if (! empty($data['address_pool']) && $data['address_pool'] !== 'none') {
                $sentence[] = '=address-pool='.$data['address_pool'];
            }

            if (! empty($data['shared_users'])) {
                $sentence[] = '=shared-users='.$data['shared_users'];
            }

            if (! empty($data['rate_limit'])) {
                $sentence[] = '=rate-limit='.$data['rate_limit'];
            }

            if (! empty($data['session_timeout'])) {
                $sentence[] = '=session-timeout='.$data['session_timeout'];
            }

            if (! empty($data['parent_queue']) && $data['parent_queue'] !== 'none') {
                $sentence[] = '=parent-queue='.$data['parent_queue'];
            }

            $result = $client->execute($sentence);
            $id = $result['done']['ret'] ?? null;

            if ($id === null) {
                throw new MikrotikConnectionException('RouterOS did not return an id for the created hotspot profile.');
            }

            $rows = $client->query(['/ip/hotspot/user/profile/print', '?.id='.$id]);

            return $rows[0] ?? ['.id' => $id, 'name' => $data['name']];
        } finally {
            $client->close();
        }
    }

    public function getIpBindings(): array
    {
        $client = $this->connectedClient();

        try {
            return $client->query(['/ip/hotspot/ip-binding/print']);
        } finally {
            $client->close();
        }
    }

    public function createIpBinding(array $data): array
    {
        $client = $this->connectedClient();

        try {
            $sentence = ['/ip/hotspot/ip-binding/add', '=mac-address='.$data['mac_address']];

            if (! empty($data['type'])) {
                $sentence[] = '=type='.$data['type'];
            }

            if (! empty($data['name'])) {
                $sentence[] = '=comment='.$data['name'];
            }

            if (! empty($data['address'])) {
                $sentence[] = '=address='.$data['address'];
            }

            if (! empty($data['to_address'])) {
                $sentence[] = '=to-address='.$data['to_address'];
            }

            $result = $client->execute($sentence);
            $id = $result['done']['ret'] ?? null;

            if ($id === null) {
                throw new MikrotikConnectionException('RouterOS did not return an id for the created IP binding.');
            }

            $rows = $client->query(['/ip/hotspot/ip-binding/print', '?.id='.$id]);

            return $rows[0] ?? ['.id' => $id, 'mac-address' => $data['mac_address']];
        } finally {
            $client->close();
        }
    }

    public function getHotspotUsers(): array
    {
        $client = $this->connectedClient();

        try {
            return $client->query(['/ip/hotspot/user/print']);
        } finally {
            $client->close();
        }
    }

    public function getActiveHotspotUsers(): array
    {
        $client = $this->connectedClient();

        try {
            return $client->query(['/ip/hotspot/active/print']);
        } finally {
            $client->close();
        }
    }

    public function findHotspotUser(string $username): ?array
    {
        $client = $this->connectedClient();

        try {
            $rows = $client->query(['/ip/hotspot/user/print', '?name='.$username]);

            return $rows[0] ?? null;
        } finally {
            $client->close();
        }
    }

    public function createHotspotUser(array $data): array
    {
        $client = $this->connectedClient();

        try {
            $sentence = ['/ip/hotspot/user/add', '=name='.$data['name'], '=password='.$data['password']];

            if (! empty($data['profile'])) {
                $sentence[] = '=profile='.$data['profile'];
            }

            if (! empty($data['limit_uptime'])) {
                $sentence[] = '=limit-uptime='.$data['limit_uptime'];
            }

            if (! empty($data['comment'])) {
                $sentence[] = '=comment='.$data['comment'];
            }

            $result = $client->execute($sentence);
            $id = $result['done']['ret'] ?? null;

            if ($id === null) {
                throw new MikrotikConnectionException('RouterOS did not return an id for the created hotspot user.');
            }

            $rows = $client->query(['/ip/hotspot/user/print', '?.id='.$id]);

            return $rows[0] ?? ['.id' => $id, 'name' => $data['name']];
        } finally {
            $client->close();
        }
    }

    public function updateHotspotUser(string $username, array $data): array
    {
        $client = $this->connectedClient();

        try {
            $id = $this->findUserId($client, $username);

            $sentence = ['/ip/hotspot/user/set', '=.id='.$id];

            if (array_key_exists('password', $data)) {
                $sentence[] = '=password='.$data['password'];
            }

            if (array_key_exists('profile', $data)) {
                $sentence[] = '=profile='.$data['profile'];
            }

            if (array_key_exists('limit_uptime', $data)) {
                $sentence[] = '=limit-uptime='.$data['limit_uptime'];
            }

            if (array_key_exists('comment', $data)) {
                $sentence[] = '=comment='.$data['comment'];
            }

            $client->execute($sentence);

            $rows = $client->query(['/ip/hotspot/user/print', '?.id='.$id]);

            return $rows[0] ?? [];
        } finally {
            $client->close();
        }
    }

    public function disableHotspotUser(string $username): bool
    {
        $client = $this->connectedClient();

        try {
            $id = $this->findUserId($client, $username);

            $client->execute(['/ip/hotspot/user/set', '=.id='.$id, '=disabled=yes']);

            return true;
        } finally {
            $client->close();
        }
    }

    public function deleteHotspotUser(string $username): bool
    {
        $client = $this->connectedClient();

        try {
            $id = $this->findUserId($client, $username);

            $client->execute(['/ip/hotspot/user/remove', '=.id='.$id]);

            return true;
        } finally {
            $client->close();
        }
    }

    public function syncUsers(): array
    {
        return $this->getHotspotUsers();
    }

    /**
     * @throws MikrotikConnectionException
     */
    private function findUserId(RouterOsApiClient $client, string $username): string
    {
        $rows = $client->query(['/ip/hotspot/user/print', '?name='.$username]);

        if (empty($rows)) {
            throw new MikrotikConnectionException("Hotspot user '{$username}' was not found on this router.");
        }

        return $rows[0]['.id'];
    }

    /**
     * @throws MikrotikConnectionException
     */
    private function connectedClient(): RouterOsApiClient
    {
        $client = new RouterOsApiClient(
            host: $this->mikrotik->host,
            port: $this->mikrotik->port,
            ssl: $this->mikrotik->ssl_enabled,
            connectTimeoutSeconds: (int) config('mikrotik.connect_timeout'),
            readTimeoutSeconds: (int) config('mikrotik.read_timeout'),
        );

        $client->connect();
        $client->login($this->mikrotik->username, $this->mikrotik->plainPassword());

        return $client;
    }
}
