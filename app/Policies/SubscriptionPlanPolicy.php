<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any SubscriptionPlan');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('view SubscriptionPlan');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create SubscriptionPlan');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('update SubscriptionPlan');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('delete SubscriptionPlan');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any SubscriptionPlan');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('restore SubscriptionPlan');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any SubscriptionPlan');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('replicate SubscriptionPlan');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder SubscriptionPlan');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SubscriptionPlan $subscriptionplan): bool
    {
        return $user->checkPermissionTo('force-delete SubscriptionPlan');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any SubscriptionPlan');
    }
}
