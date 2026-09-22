<?php

namespace Tests\Feature;

use App\Enums\MikrotikStatus;
use App\Models\Mikrotik;
use App\Models\User;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\DTO\ConnectionTestResult;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class MikrotikControllerTest extends TestCase
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

    public function test_admin_can_view_mikrotik_index(): void
    {
        Mikrotik::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('mikrotiks.index'))
            ->assertOk()
            ->assertSeeText('MikroTik Management');
    }

    public function test_operator_can_view_but_not_create_mikrotik(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('mikrotiks.index'))->assertOk();
        $this->actingAs($operator)->get(route('mikrotiks.create'))->assertForbidden();
    }

    public function test_admin_can_create_mikrotik_with_encrypted_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('mikrotiks.store'), [
            'name' => 'Jakarta Pusat',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password_encrypted' => 'SuperSecret123',
            'api_type' => 'api',
            'ssl_enabled' => '0',
        ]);

        $mikrotik = Mikrotik::firstWhere('name', 'Jakarta Pusat');

        $response->assertRedirect(route('mikrotiks.show', $mikrotik));
        $this->assertNotNull($mikrotik);
        $this->assertSame('SuperSecret123', $mikrotik->plainPassword());

        // The raw DB column must never contain the plaintext password.
        $raw = DB::table('mikrotiks')->where('id', $mikrotik->id)->value('password_encrypted');
        $this->assertStringNotContainsString('SuperSecret123', $raw);
    }

    public function test_operator_cannot_create_mikrotik(): void
    {
        $response = $this->actingAs($this->operator())->post(route('mikrotiks.store'), [
            'name' => 'Should Fail',
            'host' => '192.168.88.2',
            'port' => 8728,
            'username' => 'admin',
            'password_encrypted' => 'secret',
            'api_type' => 'api',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('mikrotiks', ['name' => 'Should Fail']);
    }

    public function test_duplicate_host_and_port_is_rejected(): void
    {
        Mikrotik::factory()->create(['host' => '192.168.88.1', 'port' => 8728]);

        $response = $this->actingAs($this->admin())->post(route('mikrotiks.store'), [
            'name' => 'Duplicate',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password_encrypted' => 'secret',
            'api_type' => 'api',
        ]);

        $response->assertSessionHasErrors('port');
    }

    public function test_admin_can_update_mikrotik_and_keep_password_when_left_blank(): void
    {
        $admin = $this->admin();
        $mikrotik = Mikrotik::factory()->create(['password_encrypted' => 'OriginalPass']);

        $response = $this->actingAs($admin)->put(route('mikrotiks.update', $mikrotik), [
            'name' => 'Renamed Router',
            'host' => $mikrotik->host,
            'port' => $mikrotik->port,
            'username' => $mikrotik->username,
            'password_encrypted' => '',
            'api_type' => $mikrotik->api_type->value,
            'ssl_enabled' => '0',
        ]);

        $response->assertRedirect(route('mikrotiks.show', $mikrotik));

        $mikrotik->refresh();
        $this->assertSame('Renamed Router', $mikrotik->name);
        $this->assertSame('OriginalPass', $mikrotik->plainPassword());
    }

    public function test_operator_cannot_delete_mikrotik(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->operator())
            ->delete(route('mikrotiks.destroy', $mikrotik))
            ->assertForbidden();

        $this->assertModelExists($mikrotik);
    }

    public function test_admin_can_delete_mikrotik(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('mikrotiks.destroy', $mikrotik))
            ->assertRedirect(route('mikrotiks.index'));

        $this->assertModelMissing($mikrotik);
    }

    public function test_test_connection_persists_status_from_mocked_service(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Unknown]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('testConnection')
            ->once()
            ->andReturn(ConnectionTestResult::success(42, MikrotikStatus::Online));

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });

        $this->actingAs($this->admin())
            ->post(route('mikrotiks.test', $mikrotik))
            ->assertRedirect();

        $mikrotik->refresh();
        $this->assertEquals(MikrotikStatus::Online, $mikrotik->status);
        $this->assertSame(42, $mikrotik->last_response_time);
        $this->assertNotNull($mikrotik->last_online_at);
        $this->assertNull($mikrotik->last_error);
    }

    public function test_test_connection_marks_offline_on_failure(): void
    {
        $mikrotik = Mikrotik::factory()->create(['status' => MikrotikStatus::Online]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('testConnection')
            ->once()
            ->andReturn(ConnectionTestResult::failure('Connection timed out.'));

        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });

        $this->actingAs($this->admin())
            ->post(route('mikrotiks.test', $mikrotik));

        $mikrotik->refresh();
        $this->assertEquals(MikrotikStatus::Offline, $mikrotik->status);
        $this->assertSame('Connection timed out.', $mikrotik->last_error);
        $this->assertNotNull($mikrotik->last_offline_at);
    }
}
