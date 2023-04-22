<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any devices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    /**
     * Determine whether the user can view the device.
     */
    public function view(User $user, Device $device): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($device, $user);
    }

    /**
     * Determine whether the user can create devices.
     */
    public function create(User $user): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can update the device.
     */
    public function update(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the device.
     */
    public function delete(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the device.
     */
    public function restore(User $user, Device $device): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can permanently delete the device.
     */
    public function forceDelete(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the stored configuration of the device
     * from Oxidized or Rancid
     *
     * @return mixed
     */
    public function showConfig(User $user, Device $device)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update device notes.
     *
     * @return mixed
     */
    public function updateNotes(User $user, Device $device)
    {
        return $user->isAdmin();
    }
}
