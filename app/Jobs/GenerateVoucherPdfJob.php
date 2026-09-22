<?php

namespace App\Jobs;

use App\Enums\PdfExportStatus;
use App\Models\Voucher;
use App\Models\VoucherPdfExport;
use App\Services\Voucher\VoucherPdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Renders a VoucherPdfExport's PDF and stores it on disk. Always queued
 * (on 'pdf-generation', per spec section 6's named queues) rather than
 * generated inline — a batch can be up to 1000 vouchers, and rendering
 * that many cards is exactly the kind of work spec section 13 says
 * "sebaiknya menggunakan queue" for.
 */
class GenerateVoucherPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $exportId)
    {
    }

    public function handle(VoucherPdfRenderer $renderer): void
    {
        $export = VoucherPdfExport::find($this->exportId);

        if (! $export) {
            return;
        }

        $export->update(['status' => PdfExportStatus::Processing]);

        $vouchers = Voucher::with('mikrotik')
            ->whereIn('id', $export->voucher_ids)
            ->orderBy('username')
            ->get();

        $pdf = $renderer->render($vouchers, $export->paper_size);

        $filename = 'voucher-pdf/'.$export->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        $export->update([
            'status' => PdfExportStatus::Ready,
            'file_path' => $filename,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        VoucherPdfExport::where('id', $this->exportId)->update([
            'status' => PdfExportStatus::Failed->value,
            'error' => $exception?->getMessage() ?? 'Unknown error while generating PDF.',
        ]);
    }
}
