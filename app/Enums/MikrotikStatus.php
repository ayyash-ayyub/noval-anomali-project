<?php

namespace App\Enums;

enum MikrotikStatus: string
{
    case Online = 'ONLINE';
    case Degraded = 'DEGRADED';
    case Offline = 'OFFLINE';
    case Unknown = 'UNKNOWN';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Degraded => 'Degraded',
            self::Offline => 'Offline',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * Tailwind accent key consumed by <x-status-badge>.
     */
    public function color(): string
    {
        return match ($this) {
            self::Online => 'green',
            self::Degraded => 'yellow',
            self::Offline => 'red',
            self::Unknown => 'gray',
        };
    }
}
