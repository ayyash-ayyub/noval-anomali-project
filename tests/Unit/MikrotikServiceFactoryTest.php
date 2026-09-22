<?php

namespace Tests\Unit;

use App\Enums\MikrotikApiType;
use App\Models\Mikrotik;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Mikrotik\RouterOsApiService;
use App\Services\Mikrotik\RouterOsRestService;
use PHPUnit\Framework\TestCase;

class MikrotikServiceFactoryTest extends TestCase
{
    public function test_it_resolves_the_binary_api_service_for_api_type(): void
    {
        $mikrotik = new Mikrotik(['api_type' => MikrotikApiType::Api]);

        $service = (new MikrotikServiceFactory)->make($mikrotik);

        $this->assertInstanceOf(RouterOsApiService::class, $service);
    }

    public function test_it_resolves_the_rest_service_for_rest_type(): void
    {
        $mikrotik = new Mikrotik(['api_type' => MikrotikApiType::Rest]);

        $service = (new MikrotikServiceFactory)->make($mikrotik);

        $this->assertInstanceOf(RouterOsRestService::class, $service);
    }
}
