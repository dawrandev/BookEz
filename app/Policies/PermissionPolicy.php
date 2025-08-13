<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-any Permission');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view Permission');
    }

    public function create(User $user): bool
    {
        return $user->can('create Permission');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('update Permission');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete Permission');
    }
}
