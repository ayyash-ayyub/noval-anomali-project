<?php

namespace Tests\Feature;

use App\Enums\VoucherStatus;
use App\Jobs\CreateHotspotVoucherChunkJob;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Voucher\HotspotUserSyncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * These tests call handle()/failed() on the job directly rather than
 * going through Bus::batch()->dispatch(). Under the 'sync' queue
 * connection used in tests, Bus::batch() wraps job execution in its own
 * DB transaction (Illuminate\Bus\Batch::add()) — so a job that partially
 * succeeds and then throws has ALL of its writes (including the
 * successful ones) rolled back along with the failure, which only
 * happens because 'sync' executes jobs inline inside that transaction.
 * Under the real 'redis' queue, dispatch() only pushes the job and
 * returns; execution happens later in a separate worker process,
 * entirely outside that transaction, so this artifact does not occur in
 * production. Testing the job directly sidesteps the mismatch and
 * exercises the actual retry/idempotency logic that matters.
 */
class CreateHotspotVoucherChunkJobTest extends TestCase
{
    use RefreshDatabase;

    private function mockFactory(MikrotikServiceInterface $fakeService): void
    {
        $this->partialMock(MikrotikServiceFactory::class, function ($mock) use ($fakeService) {
            $mock->shouldReceive('make')->andReturn($fakeService);
        });
    }

    public function test_it_marks_vouchers_synced_on_success(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $voucher = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000001']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->once()->with('JKT000001')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')
            ->once()
            ->with(['name' => 'JKT000001', 'password' => $voucher->password, 'profile' => $voucher->profile])
            ->andReturn(['.id' => '*7']);
        $this->mockFactory($fakeService);

        (new CreateHotspotVoucherChunkJob([$voucher->id]))->handle(app(MikrotikServiceFactory::class), app(HotspotUserSyncer::class));

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Synced, $voucher->status);
        $this->assertSame('*7', $voucher->router_user_id);
        $this->assertNotNull($voucher->last_synced_at);
    }

    public function test_idempotency_treats_an_already_existing_router_user_as_synced(): void
    {
        // The core spec-7 scenario: MikroTik actually created the user
        // but the response timed out, so a retry lands here. The job
        // must detect the existing user and NOT attempt to create it
        // again — that create would fail (or worse, silently succeed as
        // a second entry on older RouterOS versions).
        $mikrotik = Mikrotik::factory()->create();
        $voucher = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000001']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')
            ->once()
            ->with('JKT000001')
            ->andReturn(['.id' => '*9', 'name' => 'JKT000001']);
        $fakeService->shouldNotReceive('createHotspotUser');
        $this->mockFactory($fakeService);

        (new CreateHotspotVoucherChunkJob([$voucher->id]))->handle(app(MikrotikServiceFactory::class), app(HotspotUserSyncer::class));

        $voucher->refresh();
        $this->assertEquals(VoucherStatus::Synced, $voucher->status);
        $this->assertSame('*9', $voucher->router_user_id);
    }

    public function test_successful_vouchers_stay_synced_even_when_a_sibling_in_the_chunk_fails(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $ok1 = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000001']);
        $bad = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000002']);
        $ok2 = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'JKT000003']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')
            ->andReturnUsing(function (array $data) {
                if ($data['name'] === 'JKT000002') {
                    throw new MikrotikConnectionException('Connection timed out.');
                }

                return ['.id' => '*1'];
            });
        $this->mockFactory($fakeService);

        $job = new CreateHotspotVoucherChunkJob([$ok1->id, $bad->id, $ok2->id]);

        try {
            $job->handle(app(MikrotikServiceFactory::class), app(HotspotUserSyncer::class));
            $this->fail('Expected the job to re-throw the connection failure.');
        } catch (MikrotikConnectionException) {
            // expected — the queue would now retry this job.
        }

        $this->assertEquals(VoucherStatus::Synced, $ok1->fresh()->status);
        $this->assertEquals(VoucherStatus::Synced, $ok2->fresh()->status);
        // The failing voucher is left PENDING (not FAILED) so a retry
        // picks it back up — only failed() marks it FAILED for good.
        $this->assertEquals(VoucherStatus::Pending, $bad->fresh()->status);
    }

    public function test_failed_marks_remaining_pending_vouchers_in_the_chunk_as_failed(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $alreadySynced = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);
        $stillPending = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id]);

        $job = new CreateHotspotVoucherChunkJob([$alreadySynced->id, $stillPending->id]);
        $job->failed(new MikrotikConnectionException('Connection timed out.'));

        $this->assertEquals(VoucherStatus::Synced, $alreadySynced->fresh()->status);
        $this->assertEquals(VoucherStatus::Failed, $stillPending->fresh()->status);
        $this->assertSame('Connection timed out.', $stillPending->fresh()->sync_error);
    }

    public function test_it_skips_vouchers_that_are_no_longer_pending(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $alreadySynced = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldNotReceive('findHotspotUser');
        $fakeService->shouldNotReceive('createHotspotUser');
        $this->mockFactory($fakeService);

        // Re-running the job for an already-resolved voucher (e.g. a
        // duplicate queue pickup) must not attempt to re-create it.
        (new CreateHotspotVoucherChunkJob([$alreadySynced->id]))->handle(app(MikrotikServiceFactory::class), app(HotspotUserSyncer::class));

        $this->assertTrue(true); // Mockery's shouldNotReceive assertion runs on tearDown.
    }

    public function test_it_processes_only_vouchers_belonging_to_the_given_chunk(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $inChunk = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'INCHUNK01']);
        $notInChunk = Voucher::factory()->create(['mikrotik_id' => $mikrotik->id, 'username' => 'OUTCHUNK1']);

        $fakeService = Mockery::mock(MikrotikServiceInterface::class);
        $fakeService->shouldReceive('findHotspotUser')->once()->with('INCHUNK01')->andReturn(null);
        $fakeService->shouldReceive('createHotspotUser')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['name'] === 'INCHUNK01'))
            ->andReturn(['.id' => '*1']);
        $this->mockFactory($fakeService);

        (new CreateHotspotVoucherChunkJob([$inChunk->id]))->handle(app(MikrotikServiceFactory::class), app(HotspotUserSyncer::class));

        $this->assertEquals(VoucherStatus::Synced, $inChunk->fresh()->status);
        $this->assertEquals(VoucherStatus::Pending, $notInChunk->fresh()->status);
    }

    public function test_retry_configuration_matches_spec_expectations(): void
    {
        $job = new CreateHotspotVoucherChunkJob([1]);

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300, 900], $job->backoff());
    }
}
