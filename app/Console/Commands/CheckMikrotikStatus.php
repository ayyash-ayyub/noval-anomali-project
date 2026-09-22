<?php

namespace App\Console\Commands;

use App\Models\Mikrotik;
use App\Services\Mikrotik\MikrotikStatusChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMikrotikStatus extends Command
{
    protected $signature = 'mikrotik:check-status';

    protected $description = 'Run a health check against every registered MikroTik and refresh its ONLINE/DEGRADED/OFFLINE status.';

    public function handle(MikrotikStatusChecker $checker): int
    {
        $mikrotiks = Mikrotik::all();

        $this->info("Checking {$mikrotiks->count()} MikroTik router(s)...");

        foreach ($mikrotiks as $mikrotik) {
            $result = $checker->check($mikrotik);

            // Technical info only — never the router's credentials.
            Log::info('mikrotik.health_check', [
                'mikrotik_id' => $mikrotik->id,
                'status' => $result->status->value,
                'response_time_ms' => $result->responseTimeMs,
                'success' => $result->success,
            ]);

            $this->line(sprintf(
                '  [%s] %s — %s%s',
                $result->status->value,
                $mikrotik->name,
                $result->success ? "{$result->responseTimeMs} ms" : 'FAILED',
                $result->success ? '' : " ({$result->error})"
            ));
        }

        return self::SUCCESS;
    }
}
