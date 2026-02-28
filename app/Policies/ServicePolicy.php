<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    public function view(User $user, Service $service): bool
    {
        return $this->viewAny($user) || Permissions::canAccessDevice($service->device_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
