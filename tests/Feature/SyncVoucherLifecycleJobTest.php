<?php

namespace Tests\Feature;

use App\Enums\MikrotikStatus;
use App\Enums\VoucherStatus;
use App\Jobs\SyncVoucherLifecycleJob;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Voucher\VoucherLifecycleReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncVoucherLifecycleJobTest extends TestCase
{
    use RefreshDatabase;

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    public function test_a_synced_voucher_becomes_active_once_its_session_appears_in_the_active_list(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $voucher = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000001']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->andReturn([['user' => 'JKT000001', 'uptime' => '5m0s']]);
        $fakeService->shouldReceive('getHotspotUsers')->andReturn([]);
        $this->mockFactory($fakeService);

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Active, $voucher->status);
        $this->assertNotNull($voucher->activated_at);
    }

    public function test_an_active_voucher_no_longer_on_the_router_is_marked_expired(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $voucher = Voucher::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'username' => 'JKT000002',
            'status' => VoucherStatus::Active,
            'limit_uptime' => '02:00:00',
        ]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->andReturn([]);
        $fakeService->shouldReceive('getHotspotUsers')->andReturn([]);
        $this->mockFactory($fakeService);

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Expired, $voucher->status);
        $this->assertNotNull($voucher->expired_at);
    }

    public function test_an_active_voucher_that_used_up_its_full_limit_uptime_is_marked_used(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $voucher = Voucher::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'username' => 'JKT000003',
            'status' => VoucherStatus::Active,
            'limit_uptime' => '02:00:00',
        ]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->andReturn([]);
        $fakeService->shouldReceive('getHotspotUsers')->andReturn([
            ['name' => 'JKT000003', 'uptime' => '02:00:00'],
        ]);
        $this->mockFactory($fakeService);

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Used, $voucher->status);
    }

    public function test_an_active_voucher_whose_session_ended_early_goes_back_to_synced(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $voucher = Voucher::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'username' => 'JKT000004',
            'status' => VoucherStatus::Active,
            'limit_uptime' => '02:00:00',
        ]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->andReturn([]);
        $fakeService->shouldReceive('getHotspotUsers')->andReturn([
            ['name' => 'JKT000004', 'uptime' => '00:10:00'],
        ]);
        $this->mockFactory($fakeService);

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $this->assertEquals(VoucherStatus::Synced, $voucher->fresh()->status);
    }

    public function test_it_skips_offline_routers(): void
    {
        Mikrotik::factory()->create(['status' => MikrotikStatus::Offline]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldNotReceive('getActiveHotspotUsers');
        $this->mockFactory($fakeService);

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $this->assertTrue(true);
    }

    public function test_an_unreachable_router_does_not_stop_the_rest_of_the_sync(): void
    {
        $unreachable = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $reachable = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $voucher = Voucher::factory()->synced()->create(['mikrotik_id' => $reachable->id, 'username' => 'JKT000005']);

        $badService = Mockery::mock(MikrotikServiceInterface::class);
        $badService->shouldReceive('getActiveHotspotUsers')->andThrow(new MikrotikConnectionException('down'));

        $goodService = Mockery::mock(MikrotikServiceInterface::class);
        $goodService->shouldReceive('getActiveHotspotUsers')->andReturn([['user' => 'JKT000005', 'uptime' => '1m']]);
        $goodService->shouldReceive('getHotspotUsers')->andReturn([]);

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($unreachable, $badService, $reachable, $goodService) {
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($unreachable)))->andReturn($badService);
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($reachable)))->andReturn($goodService);
        });

        (new SyncVoucherLifecycleJob)->handle(app(MikrotikServiceFactory::class), app(VoucherLifecycleReconciler::class));

        $this->assertEquals(VoucherStatus::Active, $voucher->fresh()->status);
    }
}
