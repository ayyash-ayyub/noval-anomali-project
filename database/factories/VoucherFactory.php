<?php

namespace Database\Factories;

use App\Enums\VoucherStatus;
use App\Models\Mikrotik;
use App\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => VoucherBatch::factory(),
            'mikrotik_id' => Mikrotik::factory(),
            'username' => 'JKT'.$this->faker->unique()->numerify('######'),
            'password' => $this->faker->numerify('######'),
            'profile' => '2 Hours',
            'status' => VoucherStatus::Pending,
        ];
    }

    public function synced(): static
    {
        return $this->state(fn () => [
            'status' => VoucherStatus::Synced,
            'router_user_id' => '*'.$this->faker->unique()->numerify('##'),
            'last_synced_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => VoucherStatus::Failed,
            'last_synced_at' => now(),
            'sync_error' => 'Connection timed out.',
        ]);
    }
}
