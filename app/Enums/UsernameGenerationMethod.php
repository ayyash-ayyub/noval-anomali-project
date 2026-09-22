<?php

namespace App\Enums;

enum UsernameGenerationMethod: string
{
    case Sequential = 'sequential';
    case Random = 'random';

    public function label(): string
    {
        return match ($this) {
            self::Sequential => 'Sequential (JKT000001, JKT000002, ...)',
            self::Random => 'Random',
        };
    }
}
