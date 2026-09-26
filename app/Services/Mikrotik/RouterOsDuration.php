<?php

namespace App\Services\Mikrotik;

/**
 * Parses RouterOS's terse duration format (used by fields like
 * `session-timeout`, `limit-uptime`, and the cumulative `uptime` on
 * /ip/hotspot/user) into seconds. RouterOS renders durations under a day
 * as suffixed components ("1h30m", "45s") and durations of a day or more
 * with a leading "Nw"/"Nd" followed by a colon-separated "HH:MM:SS"
 * remainder ("1d02:03:04", "4w3d05:30:00").
 */
class RouterOsDuration
{
    public static function toSeconds(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === 'none' || $value === '0s') {
            return $value === '0s' ? 0 : null;
        }

        if (str_contains($value, ':')) {
            return self::parseColonForm($value);
        }

        return self::parseSuffixForm($value);
    }

    private static function parseColonForm(string $value): ?int
    {
        if (! preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(\d+):(\d+):(\d+)$/', $value, $m)) {
            return null;
        }

        $weeks = (int) ($m[1] ?? 0);
        $days = (int) ($m[2] ?? 0);
        $hours = (int) $m[3];
        $minutes = (int) $m[4];
        $seconds = (int) $m[5];

        return $weeks * 604800 + $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
    }

    private static function parseSuffixForm(string $value): ?int
    {
        if (! preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $m) || $m === ['']) {
            return null;
        }

        if (array_filter(array_slice($m, 1)) === []) {
            return null;
        }

        $weeks = (int) ($m[1] ?? 0);
        $days = (int) ($m[2] ?? 0);
        $hours = (int) ($m[3] ?? 0);
        $minutes = (int) ($m[4] ?? 0);
        $seconds = (int) ($m[5] ?? 0);

        return $weeks * 604800 + $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
    }
}
