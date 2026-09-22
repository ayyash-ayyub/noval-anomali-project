<?php

namespace App\Services\Mikrotik\Exceptions;

use RuntimeException;

/**
 * Raised whenever a Service Layer implementation fails to connect to,
 * authenticate with, or complete a request against a RouterOS device.
 *
 * The message is always safe to log/display — it must never contain the
 * router's credentials.
 */
class MikrotikConnectionException extends RuntimeException
{
}
