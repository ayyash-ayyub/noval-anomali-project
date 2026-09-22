<?php

namespace Database\Factories;

use App\Enums\PasswordGenerationMethod;
use App\Enums\UsernameGenerationMethod;
use App\Models\Mikrotik;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\VoucherBatch>
 */
class VoucherBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mikrotik_id' => Mikrotik::factory(),
            'batch_code' => 'WIFI-'.now()->format('Y-m-d').'-'.strtoupper(Str::random(4)),
            'profile' => '2 Hours',
            'quantity' => 10,
            'username_prefix' => 'JKT',
            'username_method' => UsernameGenerationMethod::Sequential,
            'username_length' => 6,
            'password_method' => PasswordGenerationMethod::Numeric,
            'password_length' => 6,
        ];
    }
}
