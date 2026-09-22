<?php

namespace App\Jobs;

use App\Enums\MikrotikStatus;
use App\Models\Mikrotik;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Periodically refreshes the active-HotSpot-user counts shown on the
 * dashboard. Live-fetching every router on every dashboard page load
 * would make the dashboard slow and fragile (one unreachable router
 * shouldn't stall it) — this job runs on a schedule instead and caches
 * the result, on the 'active-user-sync' queue named in spec section 6.
 * The dedicated /hotspot/active page still fetches live, since visiting
 * it is an explicit "show me current state" action.
 */
class SyncActiveHotspotUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public const CACHE_KEY_TOTAL = 'dashboard.active_users.total';

    public const CACHE_KEY_PER_MIKROTIK = 'dashboard.active_users.per_mikrotik';

    public const CACHE_KEY_SYNCED_AT = 'dashboard.active_users.synced_at';

    public function handle(MikrotikServiceFactory $factory): void
    {
        $mikrotiks = Mikrotik::where('status', MikrotikStatus::Online->value)->get();

        $total = 0;
        $perMikrotik = [];

        foreach ($mikrotiks as $mikrotik) {
            try {
                $count = count($factory->make($mikrotik)->getActiveHotspotUsers());
                $perMikrotik[$mikrotik->id] = $count;
                $total += $count;
            } catch (MikrotikConnectionException) {
                // Skip an unreachable router rather than failing the
                // whole sync — its count just stays stale until it's
                // back online.
            }
        }

        $ttl = now()->addMinutes(3);

        Cache::put(self::CACHE_KEY_TOTAL, $total, $ttl);
        Cache::put(self::CACHE_KEY_PER_MIKROTIK, $perMikrotik, $ttl);
        Cache::put(self::CACHE_KEY_SYNCED_AT, now(), $ttl);
    }
}
