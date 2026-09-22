<?php

namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use App\Services\Mikrotik\DTO\ConnectionTestResult;

/**
 * Single source of truth for refreshing a router's health/status fields.
 * Used by the manual "Test Connection" controller action and by the
 * scheduled health-check command, so both paths stay perfectly consistent.
 */
class MikrotikStatusChecker
{
    public function __construct(private readonly MikrotikServiceFactory $factory)
    {
    }

    public function check(Mikrotik $mikrotik): ConnectionTestResult
    {
        $result = $this->factory->make($mikrotik)->testConnection();

        $mikrotik->last_check_at = now();
        $mikrotik->last_response_time = $result->responseTimeMs;
        $mikrotik->status = $result->status;

        if ($result->success) {
            $mikrotik->last_online_at = now();
            $mikrotik->last_error = null;
        } else {
            $mikrotik->last_offline_at = now();
            $mikrotik->last_error = $result->error;
        }

        $mikrotik->save();

        return $result;
    }
}
