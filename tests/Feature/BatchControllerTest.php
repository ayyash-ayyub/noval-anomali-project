<?php

namespace Tests\Feature;

use App\Enums\VoucherBatchStatus;
use App\Models\Mikrotik;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_index_lists_batches(): void
    {
        VoucherBatch::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('vouchers.batches'))
            ->assertOk();
    }

    public function test_batch_show_displays_progress(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create(['mikrotik_id' => $mikrotik->id, 'quantity' => 10]);

        Voucher::factory()->count(6)->synced()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);
        Voucher::factory()->count(2)->failed()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);
        Voucher::factory()->count(2)->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);

        $response = $this->actingAs(User::factory()->create())->get(route('vouchers.batches.show', $batch));

        $response->assertOk();
        $response->assertViewHas('progress', function (array $progress) {
            return $progress['total'] === 10
                && $progress['success'] === 6
                && $progress['failed'] === 2
                && $progress['pending'] === 2
                && $progress['percent'] === 80;
        });
    }

    public function test_progress_endpoint_returns_json(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create([
            'mikrotik_id' => $mikrotik->id,
            'quantity' => 5,
            'status' => VoucherBatchStatus::Processing,
        ]);
        Voucher::factory()->count(5)->synced()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);

        $response = $this->actingAs(User::factory()->create())->getJson(route('vouchers.batches.progress', $batch));

        $response->assertOk()->assertJson([
            'status' => 'PROCESSING',
            'total' => 5,
            'success' => 5,
            'failed' => 0,
            'pending' => 0,
            'percent' => 100,
        ]);
    }
}
