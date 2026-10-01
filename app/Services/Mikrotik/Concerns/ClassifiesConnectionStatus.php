<?php

namespace App\Services\Mikrotik\Concerns;

use App\Enums\MikrotikStatus;

/**
 * Shared ONLINE/DEGRADED classification for a *successful* round trip,
 * used by every MikrotikServiceInterface implementation's testConnection().
 *
 * Per spec section 2 ("STATUS MIKROTIK"), OFFLINE means timeout/error only
 * — a slow-but-successful response is still DEGRADED, never OFFLINE. There
 * is deliberately no upper bound here: once a call actually comes back,
 * the only two outcomes are ONLINE or DEGRADED. OFFLINE is reserved for
 * the catch block (ConnectionTestResult::failure()) in each implementation,
 * so success=true and status=OFFLINE can never occur together.
 */
trait ClassifiesConnectionStatus
{
    private function classifyStatus(int $elapsedMs): MikrotikStatus
    {
        $onlineMaxMs = (int) config('mikrotik.status_thresholds.online_max_ms');

        return $elapsedMs < $onlineMaxMs
            ? MikrotikStatus::Online
            : MikrotikStatus::Degraded;
    }
}
