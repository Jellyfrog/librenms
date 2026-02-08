<?php

namespace App\Policies;

use App\Facades\Permissions;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRead();
    }

    public function view(User $user, Bill $bill): bool
    {
        return $this->viewAny($user) || Permissions::canAccessBill($bill, $user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->isAdmin();
    }
}
