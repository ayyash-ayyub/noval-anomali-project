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

class HotspotIpBindingControllerTest extends TestCase
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

    public function test_operator_cannot_view_the_add_ip_binding_form(): void
    {
        Mikrotik::factory()->create();

        $this->actingAs($this->operator())
            ->get(route('hotspot.ip-bindings.create'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_add_ip_binding_form(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('hotspot.ip-bindings.create', ['mikrotik' => $mikrotik->id]))
            ->assertOk()
            ->assertSeeText('Add IP Binding');
    }

    public function test_operator_cannot_submit_a_new_ip_binding(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->operator())
            ->post(route('hotspot.ip-bindings.store'), [
                'mikrotik_id' => $mikrotik->id,
                'mac_address' => 'AA:BB:CC:00:11:22',
                'type' => 'bypassed',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_an_ip_binding_and_it_is_audited(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('createIpBinding')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['mac_address'] === 'AA:BB:CC:00:11:22'
                && $data['name'] === 'Laptop Kantor'
                && $data['type'] === 'bypassed'))
            ->andReturn(['.id' => '*10', 'mac-address' => 'AA:BB:CC:00:11:22']);
        $this->mockFactory($fakeService);

        $response = $this->actingAs($this->admin())->post(route('hotspot.ip-bindings.store'), [
            'mikrotik_id' => $mikrotik->id,
            'name' => 'Laptop Kantor',
            'mac_address' => 'AA:BB:CC:00:11:22',
            'type' => 'bypassed',
        ]);

        $response->assertRedirect(route('hotspot.ip-bindings', ['mikrotik' => $mikrotik->id]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hotspot_ip_binding.create',
            'result' => 'success',
            'mikrotik_id' => $mikrotik->id,
        ]);
    }

    public function test_invalid_mac_address_format_is_rejected(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('hotspot.ip-bindings.store'), [
                'mikrotik_id' => $mikrotik->id,
                'mac_address' => 'not-a-mac-address',
                'type' => 'bypassed',
            ])
            ->assertSessionHasErrors('mac_address');
    }

    public function test_invalid_type_is_rejected(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('hotspot.ip-bindings.store'), [
                'mikrotik_id' => $mikrotik->id,
                'mac_address' => 'AA:BB:CC:00:11:22',
                'type' => 'not-a-real-type',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_router_rejection_is_shown_and_audited_as_failed(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('createIpBinding')
            ->once()
            ->andThrow(new MikrotikConnectionException('failure: already have such entry'));
        $this->mockFactory($fakeService);

        $response = $this->actingAs($this->admin())->post(route('hotspot.ip-bindings.store'), [
            'mikrotik_id' => $mikrotik->id,
            'mac_address' => 'AA:BB:CC:00:11:22',
            'type' => 'bypassed',
        ]);

        $response->assertSessionHasErrors('mac_address');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hotspot_ip_binding.create',
            'result' => 'failed',
        ]);
    }
}
