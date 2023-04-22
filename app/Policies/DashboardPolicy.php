<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view any dashboard.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the dashboard.
     */
    public function view(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id == $user->user_id || $dashboard->access > 0;
    }

    /**
     * Determine whether the user can create dashboards.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the dashboard.
     */
    public function update(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id == $user->user_id || $dashboard->access > 1;
    }

    /**
     * Determine whether the user can delete the dashboard.
     */
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id == $user->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can copy the dashboard.
     */
    public function copy(User $user, Dashboard $dashboard, int $target_user_id): bool
    {
        // user can copy to themselves if they can view, otherwise admins can
        return $user->isAdmin() || ($user->user_id == $target_user_id && $this->view($user, $dashboard));
    }
}
