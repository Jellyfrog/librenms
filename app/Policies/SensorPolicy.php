<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SensorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    public function view(User $user, Sensor $sensor): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($sensor->device_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Sensor $sensor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Sensor $sensor): bool
    {
        return $user->isAdmin();
    }
}
