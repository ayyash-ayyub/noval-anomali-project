<?php

namespace App\Services\Voucher;

use App\Enums\UsernameGenerationMethod;
use App\Models\Voucher;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates unique hotspot usernames for a batch. Uniqueness is always
 * checked against existing vouchers.username for the same router — this
 * is the same scope as the vouchers.(mikrotik_id, username) DB constraint,
 * so a generated batch can never violate it.
 */
class UsernameGenerator
{
    /**
     * @return array<int, string> Exactly $quantity unique usernames.
     */
    public function generate(
        UsernameGenerationMethod $method,
        string $prefix,
        int $length,
        int $quantity,
        int $mikrotikId,
    ): array {
        return match ($method) {
            UsernameGenerationMethod::Sequential => $this->sequential($prefix, $length, $quantity, $mikrotikId),
            // "User = Password" still needs a generated base value — a
            // random code (rather than a predictable sequential number)
            // is the right choice here since the same string becomes the
            // password too; VoucherBatchService::generate() copies these
            // straight into the password column.
            UsernameGenerationMethod::Random, UsernameGenerationMethod::UserEqualsPassword => $this->random($prefix, $length, $quantity, $mikrotikId),
        };
    }

    /**
     * @return array<int, string>
     */
    private function sequential(string $prefix, int $length, int $quantity, int $mikrotikId): array
    {
        $existingSuffixes = Voucher::where('mikrotik_id', $mikrotikId)
            ->where('username', 'like', $prefix.'%')
            ->pluck('username')
            ->map(fn (string $username) => substr($username, strlen($prefix)))
            ->filter(fn (string $suffix) => ctype_digit($suffix));

        $nextNumber = $existingSuffixes->isEmpty() ? 1 : ((int) $existingSuffixes->max()) + 1;

        $usernames = [];

        for ($i = 0; $i < $quantity; $i++) {
            $usernames[] = $prefix.str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT);
            $nextNumber++;
        }

        return $usernames;
    }

    /**
     * @return array<int, string>
     *
     * @throws RuntimeException if unique usernames can't be produced within a sane number of attempts.
     */
    private function random(string $prefix, int $length, int $quantity, int $mikrotikId): array
    {
        $candidates = [];
        $attempts = 0;
        $maxAttempts = max(50, $quantity * 50);

        while (count($candidates) < $quantity) {
            if (++$attempts > $maxAttempts) {
                throw new RuntimeException('Tidak dapat menghasilkan username unik yang cukup — coba perpanjang username length.');
            }

            $candidates[$prefix.Str::upper(Str::random($length))] = true;
        }

        $candidates = array_keys($candidates);

        $existing = Voucher::where('mikrotik_id', $mikrotikId)
            ->whereIn('username', $candidates)
            ->pluck('username')
            ->all();

        if (empty($existing)) {
            return $candidates;
        }

        $clean = array_values(array_diff($candidates, $existing));
        $shortfall = $quantity - count($clean);

        if ($shortfall > 0) {
            $clean = array_merge($clean, $this->random($prefix, $length, $shortfall, $mikrotikId));
        }

        return $clean;
    }
}
