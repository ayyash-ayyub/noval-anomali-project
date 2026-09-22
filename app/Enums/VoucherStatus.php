<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Pending = 'PENDING';
    case Synced = 'SYNCED';
    case Active = 'ACTIVE';
    case Used = 'USED';
    case Expired = 'EXPIRED';
    case Disabled = 'DISABLED';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Synced => 'Available',
            self::Active => 'Active',
            self::Used => 'Used',
            self::Expired => 'Expired',
            self::Disabled => 'Disabled',
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
            self::Synced => 'blue',
            self::Active => 'green',
            self::Used => 'gray',
            self::Expired => 'yellow',
            self::Disabled => 'red',
            self::Failed => 'red',
        };
    }
}
