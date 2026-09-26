<?php

namespace App\Jobs;

use App\Enums\MikrotikStatus;
use App\Models\Mikrotik;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Voucher\VoucherLifecycleReconciler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Periodically reconciles voucher status against each router's live
 * HotSpot state (see VoucherLifecycleReconciler for the classification
 * rules). Runs on the same 'active-user-sync' queue as
 * SyncActiveHotspotUsersJob since both only ever read router state, and
 * spec section 6 asks that worker count stay small.
 */
class SyncVoucherLifecycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function handle(MikrotikServiceFactory $factory, VoucherLifecycleReconciler $reconciler): void
    {
        $mikrotiks = Mikrotik::where('status', MikrotikStatus::Online->value)->get();

        foreach ($mikrotiks as $mikrotik) {
            try {
                $reconciler->reconcile($mikrotik, $factory->make($mikrotik));
            } catch (MikrotikConnectionException) {
                // Skip an unreachable router this cycle — it will be
                // picked up again on the next scheduled run.
            }
        }
    }
}
