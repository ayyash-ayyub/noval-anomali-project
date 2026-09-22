<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only monitoring page for jobs the queue gave up on after every
 * retry attempt (spec section 6: "Gunakan ... failed_jobs"). Retry/forget
 * delegate to Laravel's own queue:retry / queue:forget commands rather
 * than reimplementing payload handling.
 */
class FailedJobController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(50)
            ->through(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->display_name = $payload['displayName'] ?? 'Unknown job';

                return $job;
            });

        return view('failed-jobs.index', compact('jobs'));
    }

    public function retry(int $id): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $job = DB::table('failed_jobs')->find($id);

        // The failed jobs driver is 'database-uuids' (config/queue.php),
        // so queue:retry/queue:forget key off `uuid`, not the numeric id
        // used in this resource's URLs.
        Artisan::call('queue:retry', ['id' => [$job->uuid ?? $id]]);

        $this->auditLog->log(
            action: 'failed_job.retry',
            description: $job ? "Failed job #{$id} ({$this->displayName($job)}) di-retry." : "Failed job #{$id} di-retry.",
        );

        return back()->with('status', "Job #{$id} telah dikirim ulang ke queue.");
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $job = DB::table('failed_jobs')->find($id);

        Artisan::call('queue:forget', ['id' => $job->uuid ?? $id]);

        $this->auditLog->log(
            action: 'failed_job.delete',
            description: $job ? "Failed job #{$id} ({$this->displayName($job)}) dihapus." : "Failed job #{$id} dihapus.",
        );

        return back()->with('status', "Job #{$id} dihapus dari daftar failed jobs.");
    }

    private function displayName(object $job): string
    {
        $payload = json_decode($job->payload, true);

        return $payload['displayName'] ?? 'unknown';
    }
}
