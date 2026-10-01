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
     * IP pools configured on the router (/ip/pool), used to populate the
     * Address Pool choice when creating a HotSpot profile — never guessed
     * or hardcoded, always read live from the device.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function getIpPools(): array;

    /**
     * Creates a new HotSpot user profile on the router. Only RouterOS'
     * own native profile fields are sent — see StoreHotspotProfileRequest
     * for which keys $data may contain.
     *
     * @param  array<string, mixed>  $data  Expected keys: name, and optionally address_pool, shared_users, rate_limit, session_timeout, parent_queue.
     * @return array<string, mixed> The created profile record as returned by the router.
     *
     * @throws MikrotikConnectionException
     */
    public function createHotspotProfile(array $data): array;

    /**
     * HotSpot IP bindings (/ip/hotspot/ip-binding) — MAC address entries
     * that bypass, block, or get special NAT treatment at the HotSpot
     * login step, independent of any voucher/user account. The device
     * "name" has no dedicated RouterOS field; by convention (matching
     * Mikhmon) it's stored in the binding's own comment field.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MikrotikConnectionException
     */
    public function getIpBindings(): array;

    /**
     * @param  array<string, mixed>  $data  Expected keys: mac_address, type, and optionally name, address, to_address.
     * @return array<string, mixed> The created binding record as returned by the router.
     *
     * @throws MikrotikConnectionException
     */
    public function createIpBinding(array $data): array;

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
