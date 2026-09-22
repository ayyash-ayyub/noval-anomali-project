<?php

namespace App\Enums;

enum PdfExportStatus: string
{
    case Pending = 'PENDING';
    case Processing = 'PROCESSING';
    case Ready = 'READY';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }

    /**
     * Tailwind accent key consumed by <x-status-badge>.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'blue',
            self::Ready => 'green',
            self::Failed => 'red',
        };
    }
}
