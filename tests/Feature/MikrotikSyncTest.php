<?php

namespace Tests\Feature;

use App\Enums\VoucherStatus;
use App\Models\Mikrotik;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MikrotikSyncTest extends TestCase
{
    use RefreshDatabase;

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    public function test_sync_identifies_matched_and_discrepant_users(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        // Synced in Laravel AND present on the router.
        Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000001']);
        // Synced in Laravel but MISSING from the router.
        Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000002']);
        // Still PENDING — must NOT be flagged as a discrepancy.
        Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000003']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('syncUsers')->once()->andReturn([
            ['name' => 'JKT000001', 'profile' => '2 Hours'],
            ['name' => 'ORPHAN001', 'profile' => '1 Hour', 'comment' => 'manually added'],
        ]);
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())->get(route('mikrotiks.sync', $mikrotik));

        $response->assertOk();
        $response->assertViewHas('matchedCount', 1);
        $response->assertViewHas('laravelOnly', fn ($vouchers) => $vouchers->count() === 1 && $vouchers->first()->username === 'JKT000002');
        $response->assertViewHas('mikrotikOnly', fn ($users) => $users->count() === 1 && $users->first()['name'] === 'ORPHAN001');
    }

    public function test_sync_shows_error_when_router_unreachable(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('syncUsers')->once()->andThrow(new MikrotikConnectionException('Connection timed out.'));
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())->get(route('mikrotiks.sync', $mikrotik));

        $response->assertOk()->assertSeeText('Connection timed out.');
    }

    public function test_failed_and_pending_vouchers_are_never_flagged_as_laravel_only(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        Voucher::factory()->failed()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000099']);
        Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000098', 'status' => VoucherStatus::Pending]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('syncUsers')->once()->andReturn([]);
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())->get(route('mikrotiks.sync', $mikrotik));

        $response->assertViewHas('laravelOnly', fn ($vouchers) => $vouchers->isEmpty());
    }
}
