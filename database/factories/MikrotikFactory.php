<?php

namespace Database\Factories;

use App\Enums\MikrotikApiType;
use App\Enums\MikrotikStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Mikrotik>
 */
class MikrotikFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'host' => fake()->unique()->ipv4(),
            'port' => 8728,
            'username' => 'admin',
            'password_encrypted' => 'secret-password',
            'api_type' => MikrotikApiType::Api,
            'ssl_enabled' => false,
            'status' => MikrotikStatus::Unknown,
            'description' => null,
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'status' => MikrotikStatus::Online,
            'last_check_at' => now(),
            'last_online_at' => now(),
            'last_response_time' => 45,
            'last_error' => null,
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => MikrotikStatus::Offline,
            'last_check_at' => now(),
            'last_offline_at' => now(),
            'last_response_time' => null,
            'last_error' => 'Connection timed out',
        ]);
    }
}
