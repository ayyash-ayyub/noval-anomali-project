<?php

namespace App\Http\Controllers;

use App\Models\Mikrotik;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Read-only proxy views over live RouterOS data (HotSpot profiles, static
 * hotspot users, and active sessions). Nothing here is stored locally —
 * these pages always reflect what each router reports at request time.
 */
class HotspotController extends Controller
{
    public function __construct(private readonly MikrotikServiceFactory $factory)
    {
    }

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
            'users' => $users,
            'error' => $error,
        ]);
    }

    /**
     * Aggregates active HotSpot sessions across every registered router,
     * so both "total active users" and "active users per MikroTik" can be
     * read off a single page (per spec section 8).
     */
    public function active(): View
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

        $perMikrotik = collect($sessions)
            ->groupBy(fn ($session) => $session['mikrotik']->id)
            ->map(fn ($group) => $group->count());

        return view('hotspot.active', [
            'mikrotiks' => $mikrotiks,
            'sessions' => $sessions,
            'routerErrors' => $routerErrors,
            'perMikrotik' => $perMikrotik,
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, Mikrotik>, 1: ?Mikrotik}
     */
    private function resolveSelectedMikrotik(Request $request): array
    {
        $mikrotiks = Mikrotik::orderBy('name')->get();

        $selected = $mikrotiks->firstWhere('id', (int) $request->query('mikrotik'))
            ?? $mikrotiks->first();

        return [$mikrotiks, $selected];
    }
}
