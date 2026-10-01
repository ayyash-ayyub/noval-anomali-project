<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotspotProfileRequest;
use App\Models\Mikrotik;
use App\Services\AuditLogService;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Creates HotSpot user profiles directly on a router's own configuration
 * (/ip/hotspot/user/profile) — mirrors Mikhmon's "Add User Profile" form
 * field-for-field where RouterOS has a native equivalent, per the user's
 * request. Nothing is stored locally; like HotspotController's read-only
 * pages, the profile only ever lives on the router itself.
 */
class HotspotProfileController extends Controller
{
    public function __construct(
        private readonly MikrotikServiceFactory $factory,
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(Request $request): View
    {
        $this->authorize('createHotspotProfile', Mikrotik::class);

        $mikrotiks = Mikrotik::orderBy('name')->get();
        $selected = $mikrotiks->firstWhere('id', (int) $request->query('mikrotik'))
            ?? $mikrotiks->first();

        $pools = [];
        $poolError = null;

        if ($selected) {
            try {
                $pools = $this->factory->make($selected)->getIpPools();
            } catch (MikrotikConnectionException $e) {
                $poolError = $e->getMessage();
            }
        }

        return view('hotspot.profiles.create', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
            'pools' => $pools,
            'poolError' => $poolError,
        ]);
    }

    public function store(StoreHotspotProfileRequest $request): RedirectResponse
    {
        $mikrotik = Mikrotik::findOrFail($request->validated('mikrotik_id'));
        $data = $request->safe()->except('mikrotik_id');

        try {
            $this->factory->make($mikrotik)->createHotspotProfile($data);
        } catch (MikrotikConnectionException $e) {
            $this->auditLog->log(
                action: 'hotspot_profile.create',
                result: 'failed',
                description: "Gagal membuat profile '{$data['name']}' pada '{$mikrotik->name}': {$e->getMessage()}",
                mikrotik: $mikrotik,
            );

            return back()->withInput()
                ->withErrors(['name' => "Gagal membuat profile di router: {$e->getMessage()}"]);
        }

        $this->auditLog->log(
            action: 'hotspot_profile.create',
            description: "Profile '{$data['name']}' dibuat pada MikroTik '{$mikrotik->name}'.",
            mikrotik: $mikrotik,
        );

        return redirect()->route('hotspot.profiles', ['mikrotik' => $mikrotik->id])
            ->with('status', "Profile '{$data['name']}' berhasil dibuat pada router '{$mikrotik->name}'.");
    }
}
