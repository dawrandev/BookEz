<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\SocialNetworks;
use App\Models\User;

class SocialNetworksPolicy
{
    public function before(User $user, $ability)
    {
        return true;
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any SocialNetworks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('view SocialNetworks');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create SocialNetworks');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('update SocialNetworks');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('delete SocialNetworks');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any SocialNetworks');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('restore SocialNetworks');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any SocialNetworks');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('replicate SocialNetworks');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder SocialNetworks');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SocialNetworks $socialnetworks): bool
    {
        return $user->checkPermissionTo('force-delete SocialNetworks');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any SocialNetworks');
    }
}
