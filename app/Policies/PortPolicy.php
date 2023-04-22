<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Port;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PortPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any ports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    /**
     * Determine whether the user can view the port.
     */
    public function view(User $user, Port $port): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($port->device_id, $user) || Permissions::canAccessPort($port, $user);
    }

    /**
     * Determine whether the user can create ports.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the port.
     */
    public function update(User $user, Port $port): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can delete the port.
     */
    public function delete(User $user, Port $port): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can restore the port.
     */
    public function restore(User $user, Port $port): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can permanently delete the port.
     */
    public function forceDelete(User $user, Port $port): bool
    {
        return $user->hasGlobalAdmin();
    }
}
