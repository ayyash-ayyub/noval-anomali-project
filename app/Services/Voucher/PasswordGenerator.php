<?php

namespace App\Services\Voucher;

use App\Enums\PasswordGenerationMethod;
use Illuminate\Support\Str;

/**
 * Generates voucher passwords. Unlike usernames, passwords carry no
 * uniqueness requirement — only (mikrotik_id, username) must be unique.
 */
class PasswordGenerator
{
    public function generate(PasswordGenerationMethod $method, int $length): string
    {
        return match ($method) {
            PasswordGenerationMethod::Numeric => $this->numeric($length),
            PasswordGenerationMethod::Alphanumeric => Str::password(
                length: $length,
                letters: true,
                numbers: true,
                symbols: false,
                spaces: false,
            ),
        };
    }

    /**
     * @return array<int, string>
     */
    public function generateMany(PasswordGenerationMethod $method, int $length, int $quantity): array
    {
        return array_map(fn () => $this->generate($method, $length), range(1, $quantity));
    }

    private function numeric(int $length): string
    {
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
