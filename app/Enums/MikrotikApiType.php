<?php

namespace App\Enums;

enum MikrotikApiType: string
{
    case Api = 'api';
    case Rest = 'rest';

    public function label(): string
    {
        return match ($this) {
            self::Api => 'RouterOS API',
            self::Rest => 'RouterOS REST API',
        };
    }
}
