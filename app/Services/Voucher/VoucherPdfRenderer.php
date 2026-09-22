<?php

namespace App\Services\Voucher;

use App\Enums\PdfPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Collection;

/**
 * Renders a set of vouchers into a printable PDF, one Blade template
 * shared across all four paper sizes from spec section 13 (A4, A5,
 * Thermal Printer, Custom Card) — only the page dimensions and column
 * count change per size.
 */
class VoucherPdfRenderer
{
    /**
     * 80mm thermal receipt width in points (80 * 72 / 25.4).
     */
    private const THERMAL_WIDTH_PT = 226.77;

    private const THERMAL_HEIGHT_PT = 320.0;

    /**
     * CR80 card size (85.6mm x 54mm) in points.
     */
    private const CARD_WIDTH_PT = 242.65;

    private const CARD_HEIGHT_PT = 153.07;

    /**
     * @param  Collection<int, \App\Models\Voucher>  $vouchers
     */
    public function render(Collection $vouchers, PdfPaperSize $paperSize): PdfDocument
    {
        $columns = $paperSize->columns();

        $pdf = Pdf::loadView('pdf.vouchers', [
            'vouchers' => $vouchers,
            'paperSize' => $paperSize,
            'columns' => $columns,
            'rows' => $vouchers->chunk($columns)->values(),
        ]);

        match ($paperSize) {
            PdfPaperSize::A4 => $pdf->setPaper('a4', 'portrait'),
            PdfPaperSize::A5 => $pdf->setPaper('a5', 'portrait'),
            // Custom sizes are always passed with "portrait" — dompdf
            // swaps width/height on "landscape" even for custom arrays,
            // so the array below already encodes the final shape.
            PdfPaperSize::Thermal => $pdf->setPaper([0, 0, self::THERMAL_WIDTH_PT, self::THERMAL_HEIGHT_PT], 'portrait'),
            PdfPaperSize::Card => $pdf->setPaper([0, 0, self::CARD_WIDTH_PT, self::CARD_HEIGHT_PT], 'portrait'),
        };

        return $pdf;
    }
}
