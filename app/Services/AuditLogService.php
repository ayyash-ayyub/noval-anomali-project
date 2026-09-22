<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Mikrotik;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Central place to write audit_logs rows. Never pass a password or API
 * credential into $description — callers are responsible for keeping the
 * message limited to safe, technical details (per spec section 17).
 */
class AuditLogService
{
    public function log(
        string $action,
        string $result = 'success',
        ?string $description = null,
        ?Mikrotik $mikrotik = null,
        ?string $targetType = null,
        ?int $targetId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'mikrotik_id' => $mikrotik?->id,
            'ip_address' => Request::ip(),
            'result' => $result,
            'description' => $description,
        ]);
    }
}
