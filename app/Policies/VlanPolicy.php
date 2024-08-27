<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Vlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vlans.
     *
     * @param  \App\Models\User  $user
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    /**
     * Determine whether the user can view the vlan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vlan  $vlan
     */
    public function view(User $user, Vlan $vlan): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($vlan->device_id, $user);
    }

    /**
     * Determine whether the user can create vlans.
     *
     * @param  \App\Models\User  $user
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the vlan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vlan  $vlan
     */
    public function update(User $user, Vlan $vlan): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can delete the vlan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vlan  $vlan
     */
    public function delete(User $user, Vlan $vlan): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can restore the vlan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vlan  $vlan
     */
    public function restore(User $user, Vlan $vlan): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can permanently delete the vlan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vlan  $vlan
     */
    public function forceDelete(User $user, Vlan $vlan): bool
    {
        return $user->hasGlobalAdmin();
    }

    public function viewDevice(User $user, Vlan $vlan): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($vlan->device_id, $user);
    }

    public function viewDevices(User $user, Vlan $vlan): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($vlan->device_id, $user);
    }
}
