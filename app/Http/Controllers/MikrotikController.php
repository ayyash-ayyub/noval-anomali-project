<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMikrotikRequest;
use App\Http\Requests\UpdateMikrotikRequest;
use App\Models\Mikrotik;
use App\Services\AuditLogService;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Mikrotik\MikrotikStatusChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MikrotikController extends Controller
{
    public function __construct(
        private readonly MikrotikStatusChecker $statusChecker,
        private readonly MikrotikServiceFactory $serviceFactory,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Mikrotik::class);

        $mikrotiks = Mikrotik::query()
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('mikrotiks.index', compact('mikrotiks'));
    }

    public function create(): View
    {
        $this->authorize('create', Mikrotik::class);

        return view('mikrotiks.create');
    }

    public function store(StoreMikrotikRequest $request): RedirectResponse
    {
        $mikrotik = Mikrotik::create([
            ...$request->safe()->except('ssl_enabled'),
            'ssl_enabled' => $request->boolean('ssl_enabled'),
        ]);

        $this->auditLog->log(
            action: 'mikrotik.create',
            description: "MikroTik '{$mikrotik->name}' ({$mikrotik->host}:{$mikrotik->port}) ditambahkan.",
            mikrotik: $mikrotik,
            targetType: Mikrotik::class,
            targetId: $mikrotik->id,
        );

        return redirect()->route('mikrotiks.show', $mikrotik)
            ->with('status', "MikroTik '{$mikrotik->name}' berhasil ditambahkan.");
    }

    public function show(Mikrotik $mikrotik): View
    {
        $this->authorize('view', $mikrotik);

        return view('mikrotiks.show', compact('mikrotik'));
    }

    public function edit(Mikrotik $mikrotik): View
    {
        $this->authorize('update', $mikrotik);

        return view('mikrotiks.edit', compact('mikrotik'));
    }

    public function update(UpdateMikrotikRequest $request, Mikrotik $mikrotik): RedirectResponse
    {
        $data = $request->safe()->except(['ssl_enabled', 'password_encrypted']);
        $data['ssl_enabled'] = $request->boolean('ssl_enabled');

        if ($request->filled('password_encrypted')) {
            $data['password_encrypted'] = $request->validated('password_encrypted');
        }

        $mikrotik->update($data);

        $this->auditLog->log(
            action: 'mikrotik.update',
            description: "MikroTik '{$mikrotik->name}' ({$mikrotik->host}:{$mikrotik->port}) diperbarui.",
            mikrotik: $mikrotik,
            targetType: Mikrotik::class,
            targetId: $mikrotik->id,
        );

        return redirect()->route('mikrotiks.show', $mikrotik)
            ->with('status', "MikroTik '{$mikrotik->name}' berhasil diperbarui.");
    }

    public function destroy(Mikrotik $mikrotik): RedirectResponse
    {
        $this->authorize('delete', $mikrotik);

        $name = $mikrotik->name;
        $host = $mikrotik->host;
        $port = $mikrotik->port;

        $mikrotik->delete();

        $this->auditLog->log(
            action: 'mikrotik.delete',
            description: "MikroTik '{$name}' ({$host}:{$port}) dihapus.",
        );

        return redirect()->route('mikrotiks.index')
            ->with('status', "MikroTik '{$name}' berhasil dihapus.");
    }

    public function testConnection(Mikrotik $mikrotik): RedirectResponse
    {
        $this->authorize('testConnection', $mikrotik);

        $result = $this->statusChecker->check($mikrotik);

        $this->auditLog->log(
            action: 'mikrotik.test_connection',
            result: $result->success ? 'success' : 'failed',
            description: $result->success
                ? "Test connection ke '{$mikrotik->name}' berhasil ({$result->responseTimeMs} ms, {$result->status->label()})."
                : "Test connection ke '{$mikrotik->name}' gagal: {$result->error}",
            mikrotik: $mikrotik,
            targetType: Mikrotik::class,
            targetId: $mikrotik->id,
        );

        return back()->with(
            $result->success ? 'status' : 'error',
            $result->success
                ? "Koneksi berhasil — {$result->status->label()} ({$result->responseTimeMs} ms)."
                : "Koneksi gagal: {$result->error}"
        );
    }

    public function routerInfo(Mikrotik $mikrotik): RedirectResponse
    {
        $this->authorize('testConnection', $mikrotik);

        try {
            $info = $this->serviceFactory->make($mikrotik)->getRouterInfo();
        } catch (MikrotikConnectionException $e) {
            return back()->with('error', "Gagal mengambil informasi router: {$e->getMessage()}");
        }

        return back()->with('router_info', $info);
    }

    /**
     * Compares the router's actual hotspot users against the local
     * `vouchers` table and shows discrepancies (spec section 9).
     * Read-only — nothing is ever deleted or modified here; the operator
     * decides what to do about what's shown.
     */
    public function sync(Mikrotik $mikrotik): View
    {
        $this->authorize('view', $mikrotik);

        try {
            $routerUsers = collect($this->serviceFactory->make($mikrotik)->syncUsers());
        } catch (MikrotikConnectionException $e) {
            return view('mikrotiks.sync', [
                'mikrotik' => $mikrotik,
                'error' => $e->getMessage(),
                'matchedCount' => 0,
                'laravelOnly' => collect(),
                'mikrotikOnly' => collect(),
            ]);
        }

        $routerUsernames = $routerUsers->pluck('name')->filter()->values();

        $allLocalUsernames = $mikrotik->vouchers()->pluck('username');

        // Only vouchers we believe are already on the router are eligible
        // to be flagged "missing" — a PENDING/FAILED voucher not being on
        // the router yet is expected, not a discrepancy.
        $expectedOnRouter = $mikrotik->vouchers()
            ->whereIn('status', ['SYNCED', 'ACTIVE', 'USED', 'EXPIRED', 'DISABLED'])
            ->get();

        $matchedCount = $allLocalUsernames->intersect($routerUsernames)->count();
        $laravelOnly = $expectedOnRouter->whereNotIn('username', $routerUsernames->all());
        $mikrotikOnly = $routerUsers->whereNotIn('name', $allLocalUsernames->all());

        $this->auditLog->log(
            action: 'mikrotik.sync',
            description: "Sync check '{$mikrotik->name}': {$matchedCount} cocok, {$laravelOnly->count()} hanya di Laravel, {$mikrotikOnly->count()} hanya di MikroTik.",
            mikrotik: $mikrotik,
            targetType: Mikrotik::class,
            targetId: $mikrotik->id,
        );

        return view('mikrotiks.sync', [
            'mikrotik' => $mikrotik,
            'error' => null,
            'matchedCount' => $matchedCount,
            'laravelOnly' => $laravelOnly,
            'mikrotikOnly' => $mikrotikOnly,
        ]);
    }
}
