<?php

namespace App\Policies;

use App\Models\ServiceTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any service templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    /**
     * Determine whether the user can view the service template.
     */
    public function view(User $user, ServiceTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create service templates.
     */
    public function create(User $user): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can update the service template.
     */
    public function update(User $user, ServiceTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the service template.
     */
    public function delete(User $user, ServiceTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the service template.
     */
    public function restore(User $user, ServiceTemplate $template): bool
    {
        return $user->hasGlobalAdmin();
    }

    /**
     * Determine whether the user can permanently delete the service template.
     */
    public function forceDelete(User $user, ServiceTemplate $template): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the stored configuration of the service template
     * from Oxidized or Rancid
     *
     * @return mixed
     */
    public function showConfig(User $user, ServiceTemplate $template)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update service template notes.
     *
     * @return mixed
     */
    public function updateNotes(User $user, ServiceTemplate $template)
    {
        return $user->isAdmin();
    }
}
