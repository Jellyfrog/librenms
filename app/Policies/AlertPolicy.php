<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlertPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    public function view(User $user, Alert $alert): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($alert->device_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Alert $alert): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Alert $alert): bool
    {
        return $user->isAdmin();
    }
}
