<?php

namespace App\Services\Voucher;

use App\Enums\VoucherBatchStatus;
use App\Models\VoucherBatch;

/**
 * Flips a VoucherBatch out of PROCESSING once every chunk job has
 * finished (called from the Bus::batch()->finally() callback).
 */
class VoucherBatchFinalizer
{
    public function finalize(int $voucherBatchId): void
    {
        $batch = VoucherBatch::find($voucherBatchId);

        if (! $batch) {
            return;
        }

        $progress = $batch->progress();
        $unresolved = $progress['failed'] > 0 || $progress['pending'] > 0;

        $status = match (true) {
            ! $unresolved => VoucherBatchStatus::Completed,
            $progress['success'] === 0 => VoucherBatchStatus::Failed,
            default => VoucherBatchStatus::Partial,
        };

        $batch->update(['status' => $status]);
    }
}
