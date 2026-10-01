<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpBindingRequest;
use App\Models\Mikrotik;
use App\Services\AuditLogService;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Creates HotSpot IP bindings directly on a router's own configuration
 * (/ip/hotspot/ip-binding) — mirrors Mikhmon's IP Binding feature per the
 * user's request. Nothing is stored locally; like HotspotController's
 * read-only pages, a binding only ever lives on the router itself.
 */
class HotspotIpBindingController extends Controller
{
    public function __construct(
        private readonly MikrotikServiceFactory $factory,
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(Request $request): View
    {
        $this->authorize('createIpBinding', Mikrotik::class);

        $mikrotiks = Mikrotik::orderBy('name')->get();
        $selected = $mikrotiks->firstWhere('id', (int) $request->query('mikrotik'))
            ?? $mikrotiks->first();

        return view('hotspot.ip-bindings.create', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
        ]);
    }

    public function store(StoreIpBindingRequest $request): RedirectResponse
    {
        $mikrotik = Mikrotik::findOrFail($request->validated('mikrotik_id'));
        $data = $request->safe()->except('mikrotik_id');

        try {
            $this->factory->make($mikrotik)->createIpBinding($data);
        } catch (MikrotikConnectionException $e) {
            $this->auditLog->log(
                action: 'hotspot_ip_binding.create',
                result: 'failed',
                description: "Gagal membuat IP binding '{$data['mac_address']}' pada '{$mikrotik->name}': {$e->getMessage()}",
                mikrotik: $mikrotik,
            );

            return back()->withInput()
                ->withErrors(['mac_address' => "Gagal membuat IP binding di router: {$e->getMessage()}"]);
        }

        $label = $data['name'] ?? $data['mac_address'];

        $this->auditLog->log(
            action: 'hotspot_ip_binding.create',
            description: "IP binding '{$data['mac_address']}' ({$label}) dibuat pada MikroTik '{$mikrotik->name}'.",
            mikrotik: $mikrotik,
        );

        return redirect()->route('hotspot.ip-bindings', ['mikrotik' => $mikrotik->id])
            ->with('status', "IP binding '{$data['mac_address']}' berhasil dibuat pada router '{$mikrotik->name}'.");
    }
}
