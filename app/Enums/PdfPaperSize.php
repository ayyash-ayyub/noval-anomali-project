<?php

namespace App\Enums;

enum PdfPaperSize: string
{
    case A4 = 'a4';
    case A5 = 'a5';
    case Thermal = 'thermal';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'A4 (grid, banyak voucher per lembar)',
            self::A5 => 'A5 (grid, lembar lebih kecil)',
            self::Thermal => 'Thermal Printer (struk 80mm)',
            self::Card => 'Custom Card (satu kartu per halaman)',
        };
    }

    /**
     * Columns of voucher cards per page for the grid-based sizes.
     * Thermal and Card are single-column (one voucher per printed unit).
     */
    public function columns(): int
    {
        return match ($this) {
            self::A4 => 3,
            self::A5 => 2,
            self::Thermal, self::Card => 1,
        };
    }
}
