<?php

namespace App\Services\Mikrotik\DTO;

use App\Enums\MikrotikStatus;

final readonly class ConnectionTestResult
{
    public function __construct(
        public bool $success,
        public MikrotikStatus $status,
        public ?int $responseTimeMs,
        public ?string $error = null,
    ) {
    }

    public static function success(int $responseTimeMs, MikrotikStatus $status): self
    {
        return new self(true, $status, $responseTimeMs);
    }

    public static function failure(string $error): self
    {
        return new self(false, MikrotikStatus::Offline, null, $error);
    }
}
