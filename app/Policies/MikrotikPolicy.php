<?php

namespace App\Policies;

use App\Models\Mikrotik;
use App\Models\User;

/**
 * Both roles can view routers and their status (spec: Operator "melihat
 * status MikroTik"). Only Admin can add/edit/remove a router — that is
 * the "mengelola MikroTik" privilege reserved for Admin.
 */
class MikrotikPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Mikrotik $mikrotik): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Mikrotik $mikrotik): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Mikrotik $mikrotik): bool
    {
        return $user->isAdmin();
    }

    /**
     * Triggering a connection test/refresh is treated as part of viewing
     * status, so both roles may do it.
     */
    public function testConnection(User $user, Mikrotik $mikrotik): bool
    {
        return true;
    }
}
