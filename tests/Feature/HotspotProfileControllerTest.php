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

class HotspotProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function operator(): User
    {
        return User::factory()->create();
    }

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    public function test_operator_cannot_view_the_add_profile_form(): void
    {
        Mikrotik::factory()->create();

        $this->actingAs($this->operator())
            ->get(route('hotspot.profiles.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_add_profile_form_with_live_pools(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getIpPools')->once()->andReturn([
            ['name' => 'dhcp_pool1', 'ranges' => '10.5.50.2-10.5.50.254'],
        ]);
        $this->mockFactory($fakeService);

        $this->actingAs($this->admin())
            ->get(route('hotspot.profiles.create', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('dhcp_pool1')
            ->assertSeeText('Add User Profile');
    }

    public function test_add_profile_form_shows_connection_error_instead_of_pools(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('getIpPools')
            ->once()
            ->andThrow(new MikrotikConnectionException('Connection timed out.'));
        $this->mockFactory($fakeService);

        $this->actingAs($this->admin())
            ->get(route('hotspot.profiles.create', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('Connection timed out.');
    }

    public function test_operator_cannot_submit_a_new_profile(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->operator())
            ->post(route('hotspot.profiles.store'), [
                'mikrotik_id' => $mikrotik->id,
                'name' => 'ayyash-test',
                'shared_users' => 1,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_a_profile_and_it_is_audited(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('createHotspotProfile')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['name'] === 'ayyash-test'
                && $data['address_pool'] === 'dhcp_pool1'
                && $data['shared_users'] === 1
                && $data['rate_limit'] === '512k/1M'
                && $data['session_timeout'] === '1d'
                && $data['parent_queue'] === 'none'))
            ->andReturn(['.id' => '*10', 'name' => 'ayyash-test']);
        $this->mockFactory($fakeService);

        $response = $this->actingAs($this->admin())->post(route('hotspot.profiles.store'), [
            'mikrotik_id' => $mikrotik->id,
            'name' => 'ayyash-test',
            'address_pool' => 'dhcp_pool1',
            'shared_users' => 1,
            'rate_limit' => '512k/1M',
            'session_timeout' => '1d',
            'parent_queue' => 'none',
        ]);

        $response->assertRedirect(route('hotspot.profiles', ['mikrotik' => $mikrotik->id]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hotspot_profile.create',
            'result' => 'success',
            'mikrotik_id' => $mikrotik->id,
        ]);
    }

    public function test_invalid_rate_limit_format_is_rejected(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('hotspot.profiles.store'), [
                'mikrotik_id' => $mikrotik->id,
                'name' => 'ayyash-test',
                'shared_users' => 1,
                'rate_limit' => 'not-a-rate-limit',
            ])
            ->assertSessionHasErrors('rate_limit');
    }

    public function test_router_rejection_is_shown_and_audited_as_failed(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('createHotspotProfile')
            ->once()
            ->andThrow(new MikrotikConnectionException('already have profile with this name'));
        $this->mockFactory($fakeService);

        $response = $this->actingAs($this->admin())->post(route('hotspot.profiles.store'), [
            'mikrotik_id' => $mikrotik->id,
            'name' => 'default',
            'shared_users' => 1,
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hotspot_profile.create',
            'result' => 'failed',
        ]);
    }
}
