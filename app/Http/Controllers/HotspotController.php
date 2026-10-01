<?php

namespace App\Http\Controllers;

use App\Models\Mikrotik;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Read-only proxy views over live RouterOS data (HotSpot profiles, static
 * hotspot users, and active sessions). Nothing here is stored locally —
 * these pages always reflect what each router reports at request time.
 */
class HotspotController extends Controller
{
    public function __construct(private readonly MikrotikServiceFactory $factory) {}

    public function profiles(Request $request): View
    {
        [$mikrotiks, $selected] = $this->resolveSelectedMikrotik($request);

        $profiles = [];
        $error = null;

        if ($selected) {
            try {
                $profiles = $this->factory->make($selected)->getHotspotProfiles();
            } catch (MikrotikConnectionException $e) {
                $error = $e->getMessage();
            }
        }

        return view('hotspot.profiles', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
            'profiles' => $profiles,
            'error' => $error,
        ]);
    }

    public function users(Request $request): View
    {
        [$mikrotiks, $selected] = $this->resolveSelectedMikrotik($request);

        $users = [];
        $error = null;

        if ($selected) {
            try {
                $users = $this->factory->make($selected)->getHotspotUsers();
            } catch (MikrotikConnectionException $e) {
                $error = $e->getMessage();
            }
        }

        return view('hotspot.users', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
            'users' => $this->paginateArray($users, $request),
            'error' => $error,
        ]);
    }

    /**
     * Aggregates active HotSpot sessions across every registered router,
     * so both "total active users" and "active users per MikroTik" can be
     * read off a single page (per spec section 8).
     */
    public function active(Request $request): View
    {
        $mikrotiks = Mikrotik::orderBy('name')->get();

        $sessions = [];
        $routerErrors = [];

        foreach ($mikrotiks as $mikrotik) {
            try {
                foreach ($this->factory->make($mikrotik)->getActiveHotspotUsers() as $row) {
                    $sessions[] = ['mikrotik' => $mikrotik, 'row' => $row];
                }
            } catch (MikrotikConnectionException $e) {
                $routerErrors[$mikrotik->id] = $e->getMessage();
            }
        }

        // perMikrotik and the "Total Active Users" stat must reflect every
        // session across every router, not just the current page — compute
        // both from the full aggregate before paginateArray() slices it.
        $perMikrotik = collect($sessions)
            ->groupBy(fn ($session) => $session['mikrotik']->id)
            ->map(fn ($group) => $group->count());

        return view('hotspot.active', [
            'mikrotiks' => $mikrotiks,
            'totalActive' => count($sessions),
            'sessions' => $this->paginateArray($sessions, $request),
            'routerErrors' => $routerErrors,
            'perMikrotik' => $perMikrotik,
        ]);
    }

    public function ipBindings(Request $request): View
    {
        [$mikrotiks, $selected] = $this->resolveSelectedMikrotik($request);

        $bindings = [];
        $error = null;

        if ($selected) {
            try {
                $bindings = $this->factory->make($selected)->getIpBindings();
            } catch (MikrotikConnectionException $e) {
                $error = $e->getMessage();
            }
        }

        return view('hotspot.ip-bindings', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
            'bindings' => $this->paginateArray($bindings, $request),
            'error' => $error,
        ]);
    }

    /**
     * Paginates a plain array built live from RouterOS (hotspot users, or
     * active sessions aggregated across every router) — the router/this
     * controller has no concept of "page" when fetching, so the full
     * result is retrieved in one round trip and sliced here purely for
     * display. Spec section 25 requires pagination on every list; a router
     * with hundreds of static users, or many routers' active sessions
     * combined, would otherwise render one giant table.
     *
     * @param  array<int, mixed>  $items
     * @return LengthAwarePaginator<int, mixed>
     */
    private function paginateArray(array $items, Request $request, int $perPage = 50): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage() ?: 1;

        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $request->url()],
        );

        return $paginator->withQueryString();
    }

    /**
     * @return array{0: Collection<int, Mikrotik>, 1: ?Mikrotik}
     */
    private function resolveSelectedMikrotik(Request $request): array
    {
        $mikrotiks = Mikrotik::orderBy('name')->get();

        $selected = $mikrotiks->firstWhere('id', (int) $request->query('mikrotik'))
            ?? $mikrotiks->first();

        return [$mikrotiks, $selected];
    }
}
