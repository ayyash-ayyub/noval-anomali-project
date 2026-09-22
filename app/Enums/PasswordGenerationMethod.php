<?php

namespace App\Enums;

enum PasswordGenerationMethod: string
{
    case Numeric = 'numeric';
    case Alphanumeric = 'alphanumeric';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => 'Random Numeric (e.g. 839271)',
            self::Alphanumeric => 'Random Alphanumeric (e.g. a8X2p1)',
        };
    }
}
