<?php

namespace App\Services\Mikrotik\Contracts;

use App\Services\Mikrotik\DTO\ConnectionTestResult;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;

/**
 * Abstraction over a single RouterOS device. Controllers must never talk to
 * a router directly — they depend on this interface, resolved for a given
 * Mikrotik model through MikrotikServiceFactory. New transports (a future
 * SNMP or SSH implementation, for example) can be added without touching
 * any Controller.
 */
interface MikrotikServiceInterface
{
    /**
     * Attempt a lightweight round-trip against the router and classify
     * the result as ONLINE / DEGRADED / OFFLINE based on response time.
     * Never throws — connection failures are reported inside the result.
     */
    public function testConnection(): ConnectionTestResult;

    /**
     * Identity/resource information about the router itself
     * (RouterOS version, board name, uptime, CPU load, free memory, ...).
     *
     * @return array<string, mixed>
     *
     * @throws MikrotikConnectionException
     */
    public function getRouterInfo(): array;

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function getHotspotProfiles(): array;

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function getHotspotUsers(): array;

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function getActiveHotspotUsers(): array;

    /**
     * Looks up a single hotspot user by username. Returns null when not
     * found — this is the idempotency primitive required by spec section
     * 7: callers must check this before create() to correctly treat a
     * "the create succeeded but the response timed out" retry as already
     * synced, instead of attempting (and failing on) a duplicate create.
     *
     * @return array<string, mixed>|null
     *
     * @throws MikrotikConnectionException
     */
    public function findHotspotUser(string $username): ?array;

    /**
     * @param  array<string, mixed>  $data  Expected keys: name, password, profile, and optionally limit_uptime, comment.
     * @return array<string, mixed> The created hotspot user record as returned by the router.
     *
     * @throws MikrotikConnectionException
     */
    public function createHotspotUser(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> The updated hotspot user record.
     *
     * @throws MikrotikConnectionException
     */
    public function updateHotspotUser(string $username, array $data): array;

    /**
     * @throws MikrotikConnectionException
     */
    public function disableHotspotUser(string $username): bool;

    /**
     * @throws MikrotikConnectionException
     */
    public function deleteHotspotUser(string $username): bool;

    /**
     * Read-only fetch of the router's current hotspot user list, used by
     * the reconciliation/synchronization flow. Comparing this against the
     * local `vouchers` table (and surfacing discrepancies) is implemented
     * in the Voucher/Synchronization module, not here.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function syncUsers(): array;
}
