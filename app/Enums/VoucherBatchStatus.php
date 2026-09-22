<?php

namespace App\Enums;

enum VoucherBatchStatus: string
{
    case Pending = 'PENDING';
    case Processing = 'PROCESSING';
    case Completed = 'COMPLETED';
    case Partial = 'PARTIAL';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Partial => 'Partial',
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
            self::Completed => 'green',
            self::Partial => 'yellow',
            self::Failed => 'red',
        };
    }
}
