<?php

namespace Tests\Feature;

use App\Enums\VoucherBatchStatus;
use App\Enums\VoucherStatus;
use App\Models\Mikrotik;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VoucherControllerTest extends TestCase
{
    use RefreshDatabase;

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    private function validPayload(Mikrotik $mikrotik, array $overrides = []): array
    {
        return array_merge([
            'mikrotik_id' => $mikrotik->id,
            'profile' => '2 Hours',
            'quantity' => 5,
            'username_prefix' => 'JKT',
            'username_method' => 'sequential',
            'username_length' => 6,
            'password_method' => 'numeric',
            'password_length' => 6,
        ], $overrides);
    }

    public function test_operator_can_generate_a_voucher_batch(): void
    {
        $operator = User::factory()->create();
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->times(5)->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')
            ->times(5)
            ->andReturn(['.id' => '*1', 'name' => 'JKT000001']);
        $this->mockFactory($fakeService);

        $response = $this->actingAs($operator)->post(route('vouchers.store'), $this->validPayload($mikrotik));

        $batch = VoucherBatch::first();

        $response->assertRedirect(route('vouchers.batches.show', $batch));
        $this->assertNotNull($batch);
        $this->assertSame(5, $batch->quantity);
        $this->assertSame(5, Voucher::where('batch_id', $batch->id)->count());

        // The sync queue driver in tests runs the chunk job (and its
        // ->finally() finalizer) inline, so the batch should already be
        // resolved by the time the request completes.
        $batch->refresh();
        $this->assertEquals(VoucherBatchStatus::Completed, $batch->status);
        $this->assertSame(5, Voucher::where('batch_id', $batch->id)->where('status', VoucherStatus::Synced->value)->count());
    }

    /**
     * Per-voucher partial-failure (some succeed, some fail within one
     * chunk) is covered at the job level in
     * CreateHotspotVoucherChunkJobTest — see that file's class docblock
     * for why: under the 'sync' queue connection used in tests,
     * Bus::batch() wraps job execution in its own DB transaction, so a
     * job that partially fails has ALL its writes rolled back together,
     * which doesn't reflect real (Redis) queue behavior. This test only
     * checks that the controller stays well-behaved (no 500, batch
     * resolves to a terminal status) when every voucher fails outright.
     */
    public function test_generate_endpoint_resolves_cleanly_even_when_every_voucher_fails(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')
            ->andThrow(new MikrotikConnectionException('Connection timed out.'));
        $this->mockFactory($fakeService);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('vouchers.store'), $this->validPayload($mikrotik, ['quantity' => 3]));

        $response->assertRedirect();

        $batch = VoucherBatch::first();
        $this->assertEquals(VoucherBatchStatus::Failed, $batch->status);
    }

    public function test_generated_usernames_follow_sequential_prefix_format(): void
    {
        $mikrotik = Mikrotik::factory()->create();

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')->andReturn(['.id' => '*1']);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->post(route('vouchers.store'), $this->validPayload($mikrotik, ['quantity' => 3]));

        $usernames = Voucher::orderBy('id')->pluck('username')->all();

        $this->assertSame(['JKT000001', 'JKT000002', 'JKT000003'], $usernames);
    }

    public function test_duplicate_username_on_same_router_is_rejected_by_database(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create(['mikrotik_id' => $mikrotik->id]);
        Voucher::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'batch_id' => $batch->id,
            'username' => 'DUPLICATE',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Voucher::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'batch_id' => $batch->id,
            'username' => 'DUPLICATE',
        ]);
    }

    public function test_voucher_can_be_disabled(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $voucher = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('disableHotspotUser')->once()->with($voucher->username)->andReturn(true);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->post(route('vouchers.disable', $voucher))
            ->assertRedirect();

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Disabled, $voucher->status);
        $this->assertNotNull($voucher->disabled_at);
    }

    public function test_voucher_list_can_be_filtered_by_status(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);
        Voucher::factory()->failed()->create(['mikrotik_id' => $mikrotik->id]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('vouchers.index', ['status' => 'FAILED']));

        $response->assertOk();
        $response->assertViewHas('vouchers', fn ($vouchers) => $vouchers->total() === 1);
    }

    public function test_failed_voucher_can_be_retried_successfully(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $voucher = Voucher::factory()->failed()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000009']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->once()->with('JKT000009')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')->once()->andReturn(['.id' => '*5']);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->post(route('vouchers.retry', $voucher))
            ->assertRedirect();

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Synced, $voucher->status);
        $this->assertSame('*5', $voucher->router_user_id);
    }

    public function test_retrying_a_voucher_that_is_not_failed_is_rejected(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $voucher = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldNotReceive('findHotspotUser');
        $fakeService->shouldNotReceive('createHotspotUser');
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())
            ->post(route('vouchers.retry', $voucher))
            ->assertRedirect();

        $this->assertEquals(VoucherStatus::Synced, $voucher->fresh()->status);
    }

    public function test_retry_re_resolves_the_parent_batch_status(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create(['mikrotik_id' => $mikrotik->id, 'quantity' => 2, 'status' => VoucherBatchStatus::Partial]);
        $synced = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);
        $failed = Voucher::factory()->failed()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id, 'username' => 'JKT000010']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->once()->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')->once()->andReturn(['.id' => '*1']);
        $this->mockFactory($fakeService);

        $this->actingAs(User::factory()->create())->post(route('vouchers.retry', $failed));

        $this->assertEquals(VoucherBatchStatus::Completed, $batch->fresh()->status);
    }
}
