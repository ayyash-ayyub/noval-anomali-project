<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FailedJobControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedFailedJob(): int
    {
        return DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'redis',
            'queue' => 'voucher-generation',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\CreateHotspotVoucherChunkJob']),
            'exception' => "MikrotikConnectionException: Connection timed out.\n#0 stack trace...",
            'failed_at' => now(),
        ]);
    }

    public function test_admin_can_view_failed_jobs(): void
    {
        $this->seedFailedJob();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('failed-jobs.index'))
            ->assertOk()
            ->assertSeeText('CreateHotspotVoucherChunkJob');
    }

    public function test_operator_cannot_view_failed_jobs(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('failed-jobs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_failed_job(): void
    {
        $id = $this->seedFailedJob();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('failed-jobs.destroy', $id))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['id' => $id]);
    }

    public function test_operator_cannot_delete_a_failed_job(): void
    {
        $id = $this->seedFailedJob();

        $this->actingAs(User::factory()->create())
            ->delete(route('failed-jobs.destroy', $id))
            ->assertForbidden();

        $this->assertDatabaseHas('failed_jobs', ['id' => $id]);
    }

    public function test_operator_cannot_retry_a_failed_job(): void
    {
        $id = $this->seedFailedJob();

        $this->actingAs(User::factory()->create())
            ->post(route('failed-jobs.retry', $id))
            ->assertForbidden();

        $this->assertDatabaseHas('failed_jobs', ['id' => $id]);
    }
}
