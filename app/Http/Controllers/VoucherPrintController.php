<?php

namespace App\Http\Controllers;

use App\Enums\PdfExportStatus;
use App\Http\Requests\StorePdfExportRequest;
use App\Jobs\GenerateVoucherPdfJob;
use App\Models\VoucherBatch;
use App\Models\VoucherPdfExport;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherPrintController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $exports = VoucherPdfExport::query()
            ->with(['batch', 'requester'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.print.index', compact('exports'));
    }

    public function store(StorePdfExportRequest $request): RedirectResponse
    {
        if ($request->filled('batch_id')) {
            $batch = VoucherBatch::findOrFail($request->integer('batch_id'));
            $voucherIds = $batch->vouchers()->pluck('id')->all();
        } else {
            $batch = null;
            $voucherIds = $request->input('voucher_ids');
        }

        $export = VoucherPdfExport::create([
            'batch_id' => $batch?->id,
            'voucher_ids' => $voucherIds,
            'voucher_count' => count($voucherIds),
            'paper_size' => $request->input('paper_size'),
            'requested_by' => $request->user()->id,
        ]);

        GenerateVoucherPdfJob::dispatch($export->id)->onQueue('pdf-generation');

        $this->auditLog->log(
            action: 'voucher.generate_pdf',
            description: "PDF export #{$export->id} diminta untuk ".count($voucherIds)." voucher (".$export->paper_size->label().").",
            mikrotik: $batch?->mikrotik,
            targetType: VoucherPdfExport::class,
            targetId: $export->id,
        );

        return redirect()->route('vouchers.print.show', $export)
            ->with('status', 'PDF sedang dibuat di background.');
    }

    public function show(VoucherPdfExport $export): View
    {
        $export->load(['batch', 'requester']);

        return view('vouchers.print.show', compact('export'));
    }

    public function progress(VoucherPdfExport $export): JsonResponse
    {
        return response()->json([
            'status' => $export->status->value,
            'error' => $export->error,
        ]);
    }

    public function download(VoucherPdfExport $export): StreamedResponse
    {
        abort_unless($export->status === PdfExportStatus::Ready && $export->file_path, 404);
        abort_unless(Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download(
            $export->file_path,
            "voucher-{$export->id}.pdf"
        );
    }
}
