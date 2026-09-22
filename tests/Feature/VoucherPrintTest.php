<?php

namespace Tests\Feature;

use App\Enums\PdfExportStatus;
use App\Jobs\GenerateVoucherPdfJob;
use App\Models\Mikrotik;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherPdfExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoucherPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_all_creates_export_for_every_voucher_in_the_batch(): void
    {
        Storage::fake('local');

        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create(['mikrotik_id' => $mikrotik->id]);
        Voucher::factory()->count(3)->synced()->create(['mikrotik_id' => $mikrotik->id, 'batch_id' => $batch->id]);

        $response = $this->actingAs(User::factory()->create())->post(route('vouchers.print.store'), [
            'batch_id' => $batch->id,
            'paper_size' => 'a4',
        ]);

        $export = VoucherPdfExport::first();

        $response->assertRedirect(route('vouchers.print.show', $export));
        $this->assertNotNull($export);
        $this->assertSame($batch->id, $export->batch_id);
        $this->assertSame(3, $export->voucher_count);
        $this->assertCount(3, $export->voucher_ids);
        // The sync queue driver in tests runs the job inline, so the PDF
        // should already be READY by the time the request completes.
        $export->refresh();
        $this->assertEquals(PdfExportStatus::Ready, $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_print_selected_creates_export_for_only_the_chosen_vouchers(): void
    {
        Storage::fake('local');

        $mikrotik = Mikrotik::factory()->create();
        $v1 = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);
        $v2 = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);
        Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]); // not selected

        $this->actingAs(User::factory()->create())->post(route('vouchers.print.store'), [
            'voucher_ids' => [$v1->id, $v2->id],
            'paper_size' => 'thermal',
        ]);

        $export = VoucherPdfExport::first();

        $this->assertNull($export->batch_id);
        $this->assertSame(2, $export->voucher_count);
        $this->assertEqualsCanonicalizing([$v1->id, $v2->id], $export->voucher_ids);
    }

    public function test_request_must_specify_either_batch_or_vouchers_not_both(): void
    {
        $mikrotik = Mikrotik::factory()->create();
        $batch = VoucherBatch::factory()->create(['mikrotik_id' => $mikrotik->id]);
        $voucher = Voucher::factory()->synced()->create(['mikrotik_id' => $mikrotik->id]);

        $this->actingAs(User::factory()->create())->post(route('vouchers.print.store'), [
            'batch_id' => $batch->id,
            'voucher_ids' => [$voucher->id],
            'paper_size' => 'a4',
        ])->assertSessionHasErrors('batch_id');

        $this->actingAs(User::factory()->create())->post(route('vouchers.print.store'), [
            'paper_size' => 'a4',
        ])->assertSessionHasErrors('batch_id');
    }

    public function test_download_is_blocked_until_the_pdf_is_ready(): void
    {
        $export = VoucherPdfExport::create([
            'voucher_ids' => [1, 2],
            'voucher_count' => 2,
            'paper_size' => 'a4',
            'status' => PdfExportStatus::Processing,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('vouchers.print.download', $export))
            ->assertNotFound();
    }

    public function test_ready_pdf_can_be_downloaded(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('voucher-pdf/test.pdf', '%PDF-1.4 fake content');

        $export = VoucherPdfExport::create([
            'voucher_ids' => [1, 2],
            'voucher_count' => 2,
            'paper_size' => 'a4',
            'status' => PdfExportStatus::Ready,
            'file_path' => 'voucher-pdf/test.pdf',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('vouchers.print.download', $export))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_progress_endpoint_returns_json_status(): void
    {
        $export = VoucherPdfExport::create([
            'voucher_ids' => [1],
            'voucher_count' => 1,
            'paper_size' => 'card',
            'status' => PdfExportStatus::Failed,
            'error' => 'Something broke.',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('vouchers.print.progress', $export))
            ->assertOk()
            ->assertJson(['status' => 'FAILED', 'error' => 'Something broke.']);
    }
}
