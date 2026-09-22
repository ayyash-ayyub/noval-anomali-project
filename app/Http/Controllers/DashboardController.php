<?php

namespace App\Http\Controllers;

use App\Enums\MikrotikStatus;
use App\Enums\VoucherStatus;
use App\Jobs\SyncActiveHotspotUsersJob;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Show the monitoring dashboard.
     *
     * MikroTik and Voucher stats are real Eloquent counts. Active HotSpot
     * user count comes from a short-lived cache kept warm by
     * SyncActiveHotspotUsersJob on a schedule — live-fetching every
     * router on every dashboard load would make the dashboard slow and
     * fragile. See the dedicated /hotspot/active page for a live view.
     */
    public function index(): View
    {
        $stats = [
            'mikrotik_total' => Mikrotik::count(),
            'mikrotik_online' => Mikrotik::where('status', MikrotikStatus::Online)->count(),
            'mikrotik_offline' => Mikrotik::where('status', MikrotikStatus::Offline)->count(),
            'voucher_total' => Voucher::count(),
            'voucher_available' => Voucher::where('status', VoucherStatus::Synced)->count(),
            'voucher_active' => Voucher::where('status', VoucherStatus::Active)->count(),
            'voucher_used' => Voucher::where('status', VoucherStatus::Used)->count(),
            'voucher_expired' => Voucher::where('status', VoucherStatus::Expired)->count(),
            'voucher_failed' => Voucher::where('status', VoucherStatus::Failed)->count(),
            'active_users' => Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_TOTAL, 0),
            'voucher_generated_today' => Voucher::whereDate('created_at', today())->count(),
            'voucher_used_today' => Voucher::where('status', VoucherStatus::Used)->whereDate('updated_at', today())->count(),
        ];

        $activeUsersSyncedAt = Cache::get(SyncActiveHotspotUsersJob::CACHE_KEY_SYNCED_AT);

        $recentErrors = Mikrotik::whereNotNull('last_error')
            ->orderByDesc('last_check_at')
            ->limit(5)
            ->get(['id', 'name', 'last_error', 'last_check_at']);

        $recentBatches = VoucherBatch::with('mikrotik')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'stats' => $stats,
            'activeUsersSyncedAt' => $activeUsersSyncedAt,
            'recentErrors' => $recentErrors,
            'recentBatches' => $recentBatches,
        ]);
    }
}
