<?php

namespace App\Policies;

use App\Models\PollerCluster;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PollerClusterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any poller clusters.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can view the poller cluster.
     */
    public function view(User $user, PollerCluster $pollerCluster): bool
    {
        //
    }

    /**
     * Determine whether the user can create poller clusters.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the poller cluster.
     */
    public function update(User $user, PollerCluster $pollerCluster): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the poller cluster.
     */
    public function delete(User $user, PollerCluster $pollerCluster): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the poller cluster.
     */
    public function restore(User $user, PollerCluster $pollerCluster): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the poller cluster.
     */
    public function forceDelete(User $user, PollerCluster $pollerCluster): bool
    {
        //
    }

    /**
     * Determine whether the user can manage the poller cluster.
     *
     * @return mixed
     */
    public function manage(User $user)
    {
        return $user->isAdmin();
    }
}
