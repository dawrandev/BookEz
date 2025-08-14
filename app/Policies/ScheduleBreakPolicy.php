<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ScheduleBreak;
use App\Models\User;

class ScheduleBreakPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ScheduleBreak');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('view ScheduleBreak');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ScheduleBreak');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('update ScheduleBreak');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('delete ScheduleBreak');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ScheduleBreak');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('restore ScheduleBreak');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ScheduleBreak');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('replicate ScheduleBreak');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ScheduleBreak');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduleBreak $schedulebreak): bool
    {
        return $user->checkPermissionTo('force-delete ScheduleBreak');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ScheduleBreak');
    }
}
