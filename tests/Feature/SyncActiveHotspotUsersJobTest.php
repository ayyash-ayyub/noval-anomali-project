<?php

namespace Tests\Feature;

use App\Enums\MikrotikStatus;
use App\Jobs\SyncActiveHotspotUsersJob;
use App\Models\Mikrotik;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SyncActiveHotspotUsersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_caches_total_and_per_mikrotik_active_user_counts(): void
    {
        $online1 = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $online2 = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        Mikrotik::factory()->create(['status' => MikrotikStatus::Offline]);

        $service1 = Mockery::mock(MikrotikServiceInterface::class);
        $service1->shouldReceive('getActiveHotspotUsers')->once()->andReturn([['user' => 'a'], ['user' => 'b']]);

        $service2 = Mockery::mock(MikrotikServiceInterface::class);
        $service2->shouldReceive('getActiveHotspotUsers')->once()->andReturn([['user' => 'c']]);

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($online1, $service1, $online2, $service2) {
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($online1)))->andReturn($service1);
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($online2)))->andReturn($service2);
        });

        (new SyncActiveHotspotUsersJob())->handle(app(MikrotikServiceFactory::class));

        $this->assertSame(3, Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_TOTAL));
        $this->assertSame(
            [$online1->id => 2, $online2->id => 1],
            Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_PER_MIKROTIK)
        );
        $this->assertNotNull(Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_SYNCED_AT));
    }

    public function test_it_skips_offline_routers(): void
    {
        Mikrotik::factory()->create(['status' => MikrotikStatus::Offline]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldNotReceive('getActiveHotspotUsers');
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });

        (new SyncActiveHotspotUsersJob())->handle(app(MikrotikServiceFactory::class));

        $this->assertSame(0, Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_TOTAL));
    }

    public function test_an_unreachable_router_does_not_stop_the_rest_of_the_sync(): void
    {
        $unreachable = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);
        $reachable = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);

        $badService = Mockery::mock(MikrotikServiceInterface::class);
        $badService->shouldReceive('getActiveHotspotUsers')->once()->andThrow(new MikrotikConnectionException('down'));

        $goodService = Mockery::mock(MikrotikServiceInterface::class);
        $goodService->shouldReceive('getActiveHotspotUsers')->once()->andReturn([['user' => 'a']]);

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($unreachable, $badService, $reachable, $goodService) {
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($unreachable)))->andReturn($badService);
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($reachable)))->andReturn($goodService);
        });

        (new SyncActiveHotspotUsersJob())->handle(app(MikrotikServiceFactory::class));

        $this->assertSame(1, Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_TOTAL));
    }
}
