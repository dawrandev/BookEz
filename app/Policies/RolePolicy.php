<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-any Role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('view Role');
    }

    public function create(User $user): bool
    {
        return $user->can('create Role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('update Role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete Role');
    }
}
