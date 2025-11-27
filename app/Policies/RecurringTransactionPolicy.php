<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecurringTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecurringTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecurringTransaction');
    }

    public function view(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('View:RecurringTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecurringTransaction');
    }

    public function update(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('Update:RecurringTransaction');
    }

    public function delete(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('Delete:RecurringTransaction');
    }

    public function restore(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('Restore:RecurringTransaction');
    }

    public function forceDelete(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('ForceDelete:RecurringTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecurringTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecurringTransaction');
    }

    public function replicate(AuthUser $authUser, RecurringTransaction $recurringTransaction): bool
    {
        return $authUser->can('Replicate:RecurringTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecurringTransaction');
    }

}