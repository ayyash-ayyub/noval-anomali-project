<?php

namespace App\Services\Voucher;

use App\Enums\VoucherStatus;
use App\Models\Voucher;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;

/**
 * Creates (or confirms) a single voucher's hotspot user on its router.
 * Shared by the batch queue job and the manual "Retry Voucher" action so
 * both go through the exact same idempotency-aware logic.
 */
class HotspotUserSyncer
{
    /**
     * @throws MikrotikConnectionException
     */
    public function sync(MikrotikServiceInterface $service, Voucher $voucher): void
    {
        // Idempotency (spec section 7): a prior attempt may have already
        // created this user on the router even though that attempt
        // appeared to fail (timeout, dropped connection, ...). Always
        // check first so a retry never produces a duplicate.
        $existing = $service->findHotspotUser($voucher->username);

        if ($existing !== null) {
            $voucher->update([
                'status' => VoucherStatus::Synced,
                'router_user_id' => $existing['.id'] ?? $existing['id'] ?? null,
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

            return;
        }

        $created = $service->createHotspotUser([
            'name' => $voucher->username,
            'password' => $voucher->password,
            'profile' => $voucher->profile,
        ]);

        $voucher->update([
            'status' => VoucherStatus::Synced,
            'router_user_id' => $created['.id'] ?? $created['id'] ?? null,
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }
}
