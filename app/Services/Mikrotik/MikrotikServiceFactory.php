<?php

namespace App\Services\Mikrotik;

use App\Enums\MikrotikApiType;
use App\Models\Mikrotik;
use App\Services\Mikrotik\Contracts\MikrotikServiceInterface;

/**
 * Resolves the correct MikrotikServiceInterface implementation for a given
 * router. This is the only place that knows about concrete service
 * classes — Controllers and other services depend on the interface only,
 * so a new transport can be added here without touching them.
 */
class MikrotikServiceFactory
{
    public function make(Mikrotik $mikrotik): MikrotikServiceInterface
    {
        return match ($mikrotik->api_type) {
            MikrotikApiType::Api => new RouterOsApiService($mikrotik),
            MikrotikApiType::Rest => new RouterOsRestService($mikrotik),
        };
    }
}
