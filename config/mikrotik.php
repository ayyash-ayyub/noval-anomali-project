<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connection Timeouts
    |--------------------------------------------------------------------------
    |
    | Applied to both the binary RouterOS API and the RouterOS REST API.
    | Keep these short — a single unreachable router must never be allowed
    | to stall a batch/health-check job for long.
    |
    */

    'connect_timeout' => (int) env('MIKROTIK_CONNECT_TIMEOUT', 5),

    'read_timeout' => (int) env('MIKROTIK_READ_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Default Ports
    |--------------------------------------------------------------------------
    |
    | Used only to pre-fill the MikroTik form / to resolve a port when the
    | operator leaves it blank. The stored port on each router is always
    | configurable per spec.
    |
    */

    'default_ports' => [
        'api' => 8728,
        'api_ssl' => 8729,
        'rest' => 80,
        'rest_ssl' => 443,
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Thresholds (milliseconds)
    |--------------------------------------------------------------------------
    |
    | < online_max  => ONLINE
    | online_max..degraded_max => DEGRADED
    | timeout/error/> degraded_max => OFFLINE
    |
    */

    'status_thresholds' => [
        'online_max_ms' => 300,
        'degraded_max_ms' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | REST API TLS Verification
    |--------------------------------------------------------------------------
    |
    | MikroTik routers commonly serve the REST API over HTTPS with a
    | self-signed certificate. Disable verification only for trusted,
    | private/VPN-only network segments.
    |
    */

    'rest_verify_tls' => (bool) env('MIKROTIK_REST_VERIFY_TLS', false),

];
