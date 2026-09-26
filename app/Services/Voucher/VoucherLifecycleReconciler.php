<?php

namespace App\Services\Voucher;

use App\Enums\VoucherStatus;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\RouterOsDuration;

/**
 * Reconciles a router's live HotSpot state against the local `vouchers`
 * table so status reflects actual usage instead of staying SYNCED forever
 * (spec section 4/9). RouterOS has no native "USED" concept, so a
 * SYNCED/ACTIVE voucher whose session has ended is classified by
 * inference:
 *
 * - No longer on the router at all      -> EXPIRED (removed/consumed)
 * - Still on the router, cumulative
 *   `uptime` has reached `limit_uptime` -> USED (fully consumed)
 * - Still on the router, under its
 *   `limit_uptime`                      -> back to SYNCED (session simply
 *                                          ended; time remains available)
 *
 * Never deletes or disables anything — this only updates local status.
 */
class VoucherLifecycleReconciler
{
    /**
     * @throws MikrotikConnectionException
     */
    public function reconcile(Mikrotik $mikrotik, MikrotikServiceInterface $service): void
    {
        $activeUsernames = collect($service->getActiveHotspotUsers())
            ->pluck('user')
            ->filter()
            ->all();

        $this->activateSyncedVouchers($mikrotik, $activeUsernames);
        $this->resolveEndedSessions($mikrotik, $activeUsernames, $service);
    }

    /**
     * @param  array<int, string>  $activeUsernames
     */
    private function activateSyncedVouchers(Mikrotik $mikrotik, array $activeUsernames): void
    {
        if (empty($activeUsernames)) {
            return;
        }

        Voucher::where('mikrotik_id', $mikrotik->id)
            ->where('status', VoucherStatus::Synced)
            ->whereIn('username', $activeUsernames)
            ->update([
                'status' => VoucherStatus::Active,
                'activated_at' => now(),
            ]);
    }

    /**
     * @param  array<int, string>  $activeUsernames
     *
     * @throws MikrotikConnectionException
     */
    private function resolveEndedSessions(Mikrotik $mikrotik, array $activeUsernames, MikrotikServiceInterface $service): void
    {
        $endedVouchers = Voucher::where('mikrotik_id', $mikrotik->id)
            ->where('status', VoucherStatus::Active)
            ->whereNotIn('username', $activeUsernames)
            ->get();

        if ($endedVouchers->isEmpty()) {
            return;
        }

        $routerUsers = collect($service->getHotspotUsers())->keyBy('name');

        foreach ($endedVouchers as $voucher) {
            $routerUser = $routerUsers->get($voucher->username);

            if ($routerUser === null) {
                $voucher->update(['status' => VoucherStatus::Expired, 'expired_at' => now()]);

                continue;
            }

            $usedSeconds = RouterOsDuration::toSeconds($routerUser['uptime'] ?? null) ?? 0;
            $limitSeconds = RouterOsDuration::toSeconds($voucher->limit_uptime);

            if ($limitSeconds !== null && $usedSeconds >= $limitSeconds) {
                $voucher->update(['status' => VoucherStatus::Used, 'expired_at' => now()]);
            } else {
                $voucher->update(['status' => VoucherStatus::Synced]);
            }
        }
    }
}
