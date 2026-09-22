<?php

namespace App\Http\Controllers;

use App\Models\VoucherBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{
    public function index(): View
    {
        $batches = VoucherBatch::query()
            ->with(['mikrotik', 'creator'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.batches.index', compact('batches'));
    }

    public function show(VoucherBatch $batch): View
    {
        $batch->load(['mikrotik', 'creator']);

        $vouchers = $batch->vouchers()
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('vouchers.batches.show', [
            'batch' => $batch,
            'vouchers' => $vouchers,
            'progress' => $batch->progress(),
        ]);
    }

    /**
     * Lightweight JSON polling endpoint for the batch progress bar
     * (spec section 12: "gunakan polling ringan").
     */
    public function progress(VoucherBatch $batch): JsonResponse
    {
        return response()->json([
            'status' => $batch->status->value,
            ...$batch->progress(),
        ]);
    }
}
