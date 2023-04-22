<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can manage users.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() || $target->is($user);
    }

    /**
     * Determine whether the user can view any user.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() || $target->is($user);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin();
    }
}
