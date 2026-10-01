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
    | Classification for a round trip that actually came back (spec section
    | 2's "Contoh" values are the starting point, not a fixed rule):
    |
    |   < online_max_ms  => ONLINE
    |   >= online_max_ms => DEGRADED
    |
    | OFFLINE is reserved for "timeout/error" per spec — i.e. the call threw
    | (see Concerns\ClassifiesConnectionStatus and each service's catch
    | block), never a slow-but-successful response. There is deliberately
    | no latency ceiling that downgrades a successful call to OFFLINE.
    |
    | The default below (500ms) assumes the router may be reached over the
    | public internet rather than a LAN — a real production router tested
    | over its public IP measured a consistent 384-450ms round trip, which
    | the original 300ms spec example would have permanently misreported as
    | DEGRADED. Tune per deployment via .env; a LAN-only/VPN router can set
    | this back down to 300.
    |
    | 'degraded_max_ms' is advisory only (not an enforced ceiling) — kept as
    | spec's original reference point for how slow is "still fine" to show
    | an operator, available for future alerting/logging if ever needed.
    |
    */

    'status_thresholds' => [
        'online_max_ms' => (int) env('MIKROTIK_ONLINE_MAX_MS', 500),
        'degraded_max_ms' => (int) env('MIKROTIK_DEGRADED_MAX_MS', 2000),
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
