<?php

namespace App\Jobs;

use App\Enums\VoucherStatus;
use App\Models\Voucher;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Voucher\HotspotUserSyncer;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Creates the HotSpot user on the router for one chunk (~100) of a
 * voucher batch.
 *
 * Idempotency (spec section 7): every voucher is looked up on the router
 * via findHotspotUser() BEFORE create() is attempted. This is what
 * correctly turns the classic "MikroTik created the user but the
 * response timed out, so Laravel thinks it failed and retries" scenario
 * into a safe no-op instead of a duplicate-create attempt — regardless
 * of whether this is the first attempt or a queue retry.
 *
 * Retry: every voucher in the chunk is attempted even if an earlier one
 * fails — a failure just leaves that voucher PENDING and is re-thrown
 * once the whole chunk has been walked, which is what makes Laravel
 * actually retry this job. A retry only re-processes vouchers still
 * PENDING (already-SYNCED ones are filtered out up front and never
 * touched again), so re-running this job is always safe. Once every
 * attempt is exhausted, failed() marks whatever is still PENDING as
 * FAILED with the last error.
 */
class CreateHotspotVoucherChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param  array<int, int>  $voucherIds
     */
    public function __construct(public readonly array $voucherIds)
    {
    }

    /**
     * Seconds to wait before each retry attempt. A router that's down
     * tends to stay down for a while, so this spaces attempts out rather
     * than hammering it immediately.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MikrotikServiceFactory $factory, HotspotUserSyncer $syncer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $vouchers = Voucher::whereIn('id', $this->voucherIds)
            ->where('status', VoucherStatus::Pending->value)
            ->get();

        if ($vouchers->isEmpty()) {
            return;
        }

        $mikrotik = $vouchers->first()->mikrotik;
        $service = $factory->make($mikrotik);

        $lastError = null;

        foreach ($vouchers as $voucher) {
            try {
                $syncer->sync($service, $voucher);
            } catch (MikrotikConnectionException $e) {
                // Leave this voucher PENDING (don't mark it FAILED yet) so
                // a retry — or failed() once retries are exhausted — can
                // still pick it back up. Keep going with the rest of the
                // chunk instead of aborting on the first failure.
                $lastError = $e;
            }
        }

        // Surfacing the last error (instead of swallowing it) is what
        // makes Laravel actually retry this job. The retry only
        // re-processes vouchers still PENDING, so anything that already
        // succeeded above is never touched again.
        if ($lastError !== null) {
            throw $lastError;
        }
    }

    /**
     * Runs once every retry attempt is exhausted. Any voucher in this
     * chunk that's still PENDING could not be synced after $tries
     * attempts — mark it FAILED (with the reason) instead of leaving it
     * stuck in PENDING forever. It can be retried manually from the UI.
     */
    public function failed(?Throwable $exception): void
    {
        Voucher::whereIn('id', $this->voucherIds)
            ->where('status', VoucherStatus::Pending->value)
            ->update([
                'status' => VoucherStatus::Failed->value,
                'last_synced_at' => now(),
                'sync_error' => $exception?->getMessage() ?? 'Unknown error after retries exhausted.',
            ]);
    }
}
