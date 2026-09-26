<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Placeholder for the not-yet-built system settings module (spec: "Admin
 * dapat ... mengatur sistem"). Gated to Admin here at the route level —
 * previously this was a plain Route::view() reachable by any
 * authenticated user via direct URL, even though it was hidden from the
 * Operator sidebar.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('coming-soon', [
            'module' => 'Settings',
            'description' => 'Pengaturan umum aplikasi.',
        ]);
    }
}
