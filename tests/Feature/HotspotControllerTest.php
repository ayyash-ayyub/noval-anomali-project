<?php

namespace Tests\Feature;

use App\Models\Mikrotik;
use App\Models\User;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HotspotControllerTest extends TestCase
{
    use RefreshDatabase;

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    public function test_profiles_page_shows_empty_state_when_no_mikrotik_registered(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.profiles'))
            ->assertOk()
            ->assertSeeText('Belum ada MikroTik terdaftar.');
    }

    public function test_profiles_page_lists_data_from_service(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getHotspotProfiles')->once()->andReturn([
            ['name' => '2 Hours', 'session-timeout' => '02:00:00', 'rate-limit' => '2M/2M', 'shared-users' => '1'],
        ]);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.profiles', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('2 Hours')
            ->assertSeeText('02:00:00');
    }

    public function test_profiles_page_shows_connection_error(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getHotspotProfiles')
            ->once()
            ->andThrow(new MikrotikConnectionException('Connection timed out.'));
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.profiles', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('Connection timed out.');
    }

    public function test_users_page_lists_data_from_service(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getHotspotUsers')->once()->andReturn([
            ['name' => 'JKT000001', 'password' => '839271', 'profile' => '2 Hours', 'disabled' => 'false'],
        ]);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.users', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('JKT000001')
            ->assertSeeText('839271');
    }

    public function test_users_page_is_paginated(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $routerUsers = collect(range(1, 60))
            ->map(fn (int $i) => ['name' => sprintf('JKT%06d', $i), 'password' => '000000', 'profile' => 'default', 'disabled' => 'false'])
            ->all();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getHotspotUsers')->once()->andReturn($routerUsers);
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('hotspot.users', ['mikrotik' => $mikrotik->id]));

        $response->assertOk()
            ->assertSeeText('JKT000001')
            ->assertSeeText('JKT000050')
            ->assertDontSeeText('JKT000051');
    }

    public function test_users_page_second_page_shows_remaining_users(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $routerUsers = collect(range(1, 60))
            ->map(fn (int $i) => ['name' => sprintf('JKT%06d', $i), 'password' => '000000', 'profile' => 'default', 'disabled' => 'false'])
            ->all();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getHotspotUsers')->once()->andReturn($routerUsers);
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('hotspot.users', ['mikrotik' => $mikrotik->id, 'page' => 2]));

        $response->assertOk()
            ->assertDontSeeText('JKT000001')
            ->assertSeeText('JKT000060');
    }

    public function test_active_page_aggregates_sessions_across_routers(): void
    {
        $jakarta = Mikrotik::factory()->create(['name' => 'Jakarta']);
        $bandung = Mikrotik::factory()->create(['name' => 'Bandung']);

        $jakartaService = Mockery::mock(MikrotikServiceInterface::class);
        $jakartaService->shouldReceive('getActiveHotspotUsers')->once()->andReturn([
            ['user' => 'JKT000001', 'address' => '10.5.50.2', 'mac-address' => 'AA:BB:CC:00:11:22', 'uptime' => '10m'],
        ]);

        $bandungService = Mockery::mock(MikrotikServiceInterface::class);
        $bandungService->shouldReceive('getActiveHotspotUsers')
            ->once()
            ->andThrow(new MikrotikConnectionException('Router unreachable.'));

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($jakarta, $jakartaService, $bandung, $bandungService) {
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($jakarta)))->andReturn($jakartaService);
            $mock->shouldReceive('make')->with(Mockery::on(fn ($m) => $m->is($bandung)))->andReturn($bandungService);
        });

        $response = $this->actingAs(User::factory()->create())->get(route('hotspot.active'));

        $response->assertOk()
            ->assertSeeText('JKT000001')
            ->assertSeeText('Router unreachable.');
    }

    public function test_active_page_is_paginated_but_total_stat_counts_every_session(): void
    {
        Mikrotik::factory()->create();

        $activeSessions = collect(range(1, 60))
            ->map(fn (int $i) => ['user' => sprintf('JKT%06d', $i), 'address' => '10.5.50.2', 'mac-address' => 'AA:BB:CC:00:11:22', 'uptime' => '10m'])
            ->all();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->once()->andReturn($activeSessions);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.active'))
            ->assertOk()
            ->assertSeeText('JKT000001')
            ->assertSeeText('JKT000050')
            ->assertDontSeeText('JKT000051')
            // Total Active Users stat card must reflect all 60, not just the 50 shown on this page.
            ->assertSeeText('60');
    }

    public function test_active_page_second_page_shows_remaining_sessions(): void
    {
        Mikrotik::factory()->create();

        $activeSessions = collect(range(1, 60))
            ->map(fn (int $i) => ['user' => sprintf('JKT%06d', $i), 'address' => '10.5.50.2', 'mac-address' => 'AA:BB:CC:00:11:22', 'uptime' => '10m'])
            ->all();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getActiveHotspotUsers')->once()->andReturn($activeSessions);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.active', ['page' => 2]))
            ->assertOk()
            ->assertDontSeeText('JKT000001')
            ->assertSeeText('JKT000060');
    }

    public function test_ip_bindings_page_lists_data_from_service(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getIpBindings')->once()->andReturn([
            ['mac-address' => 'AA:BB:CC:00:11:22', 'comment' => 'Laptop Kantor', 'type' => 'bypassed', 'disabled' => 'false'],
        ]);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.ip-bindings', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('AA:BB:CC:00:11:22')
            ->assertSeeText('Laptop Kantor');
    }

    public function test_ip_bindings_page_shows_connection_error(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getIpBindings')
            ->once()
            ->andThrow(new MikrotikConnectionException('Connection timed out.'));
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.ip-bindings', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('Connection timed out.');
    }

    public function test_ip_bindings_page_is_paginated(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $bindings = collect(range(1, 60))
            ->map(fn (int $i) => ['mac-address' => sprintf('AA:BB:CC:00:00:%02d', $i), 'comment' => "Device {$i}", 'type' => 'bypassed'])
            ->all();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getIpBindings')->once()->andReturn($bindings);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->get(route('hotspot.ip-bindings', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('Device 1')
            ->assertSeeText('Device 50')
            ->assertDontSeeText('Device 51');
    }

    public function test_operator_can_view_all_hotspot_pages(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)->get(route('hotspot.profiles'))->assertOk();
        $this->actingAs($operator)->get(route('hotspot.users'))->assertOk();
        $this->actingAs($operator)->get(route('hotspot.active'))->assertOk();
        $this->actingAs($operator)->get(route('hotspot.ip-bindings'))->assertOk();
    }
}
