<?php

namespace App\Models;

use App\Enums\PdfExportStatus;
use App\Enums\PdfPaperSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherPdfExport extends Model
{
    protected $fillable = [
        'batch_id',
        'voucher_ids',
        'voucher_count',
        'paper_size',
        'status',
        'file_path',
        'error',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'voucher_ids' => 'array',
            'voucher_count' => 'integer',
            'paper_size' => PdfPaperSize::class,
            'status' => PdfExportStatus::class,
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
