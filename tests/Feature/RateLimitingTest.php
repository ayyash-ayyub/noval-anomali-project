<?php

namespace Tests\Feature;

use App\Enums\MikrotikStatus;
use App\Models\Mikrotik;
use App\Models\User;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\DTO\ConnectionTestResult;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Spec section 15 lists rate limiting as a WAJIB (required) hardening
 * item. These only check that the named limiters registered in
 * AppServiceProvider are actually wired to the routes — the limiter
 * values themselves are exercised by Laravel's own ThrottleRequests tests.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_test_connection_attempts_are_throttled(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('testConnection')->andReturn(
            ConnectionTestResult::success(50, MikrotikStatus::Online)
        );
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });

        $user = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($user)->post(route('mikrotiks.test', $mikrotik));
        }

        $response = $this->actingAs($user)->post(route('mikrotiks.test', $mikrotik));

        $response->assertStatus(429);
    }
}
