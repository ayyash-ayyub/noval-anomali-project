<?php

namespace Tests\Unit;

use App\Services\Mikrotik\RouterOsDuration;
use Tests\TestCase;

class RouterOsDurationTest extends TestCase
{
    public function test_it_parses_suffix_form(): void
    {
        $this->assertSame(30, RouterOsDuration::toSeconds('30s'));
        $this->assertSame(90, RouterOsDuration::toSeconds('1m30s'));
        $this->assertSame(5400, RouterOsDuration::toSeconds('1h30m'));
    }

    public function test_it_parses_colon_form_with_and_without_day_week_prefix(): void
    {
        $this->assertSame(7384, RouterOsDuration::toSeconds('02:03:04'));
        $this->assertSame(93784, RouterOsDuration::toSeconds('1d02:03:04'));
        $this->assertSame(2512984, RouterOsDuration::toSeconds('4w1d02:03:04'));
    }

    public function test_it_returns_zero_for_the_zero_value(): void
    {
        $this->assertSame(0, RouterOsDuration::toSeconds('0s'));
    }

    public function test_it_returns_null_for_none_empty_or_unparseable_values(): void
    {
        $this->assertNull(RouterOsDuration::toSeconds('none'));
        $this->assertNull(RouterOsDuration::toSeconds(null));
        $this->assertNull(RouterOsDuration::toSeconds(''));
        $this->assertNull(RouterOsDuration::toSeconds('garbage'));
    }
}
