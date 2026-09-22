<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $logs = AuditLog::query()
            ->with(['user', 'mikrotik'])
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}
