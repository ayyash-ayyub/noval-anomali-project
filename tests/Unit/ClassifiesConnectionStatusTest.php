<?php

namespace Tests\Unit;

use App\Enums\MikrotikStatus;
use App\Services\Mikrotik\Concerns\ClassifiesConnectionStatus;
use Tests\TestCase;

/**
 * Regression coverage for the bug found during the 2026-10-01 live
 * MikroTik test: a successful-but-slow round trip was being classified
 * MikrotikStatus::Offline while ConnectionTestResult::success() still
 * reported success=true, so MikrotikStatusChecker wrote last_online_at
 * on a router it had just marked OFFLINE. Per spec section 2, OFFLINE
 * must only come from timeout/error (an actual thrown exception), never
 * from a latency threshold on a call that came back.
 */
class ClassifiesConnectionStatusTest extends TestCase
{
    private function classifier(): object
    {
        return new class
        {
            use ClassifiesConnectionStatus;

            public function classify(int $elapsedMs): MikrotikStatus
            {
                return $this->classifyStatus($elapsedMs);
            }
        };
    }

    public function test_it_classifies_online_below_the_configured_threshold(): void
    {
        config(['mikrotik.status_thresholds.online_max_ms' => 500]);

        $this->assertSame(MikrotikStatus::Online, $this->classifier()->classify(100));
        $this->assertSame(MikrotikStatus::Online, $this->classifier()->classify(499));
    }

    public function test_it_classifies_degraded_at_or_above_the_configured_threshold(): void
    {
        config(['mikrotik.status_thresholds.online_max_ms' => 500]);

        $this->assertSame(MikrotikStatus::Degraded, $this->classifier()->classify(500));
        $this->assertSame(MikrotikStatus::Degraded, $this->classifier()->classify(2000));
    }

    public function test_a_successful_response_is_never_classified_offline_no_matter_how_slow(): void
    {
        config(['mikrotik.status_thresholds.online_max_ms' => 500]);

        // Regression case: this is the exact latency observed live (2346ms)
        // that previously tripped the removed "> degraded_max_ms => Offline"
        // branch while ConnectionTestResult::success() still said true.
        $this->assertSame(MikrotikStatus::Degraded, $this->classifier()->classify(2346));
        $this->assertSame(MikrotikStatus::Degraded, $this->classifier()->classify(60000));
    }
}
