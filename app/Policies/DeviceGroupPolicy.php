<?php

namespace App\Policies;

use App\Models\DeviceGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeviceGroupPolicy
{
    use HandlesAuthorization;

    public function before($user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can manage device groups.
     */
    public function manage(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the device group.
     */
    public function view(User $user, DeviceGroup $deviceGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view any device group.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create device groups.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the device group.
     */
    public function update(User $user, DeviceGroup $deviceGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the device group.
     */
    public function delete(User $user, DeviceGroup $deviceGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the device group.
     */
    public function restore(User $user, DeviceGroup $deviceGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the device group.
     */
    public function forceDelete(User $user, DeviceGroup $deviceGroup): bool
    {
        return false;
    }
}
