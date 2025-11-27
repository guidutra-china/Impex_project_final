<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FinancialTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialTransaction');
    }

    public function view(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('View:FinancialTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialTransaction');
    }

    public function update(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('Update:FinancialTransaction');
    }

    public function delete(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('Delete:FinancialTransaction');
    }

    public function restore(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('Restore:FinancialTransaction');
    }

    public function forceDelete(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('ForceDelete:FinancialTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinancialTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinancialTransaction');
    }

    public function replicate(AuthUser $authUser, FinancialTransaction $financialTransaction): bool
    {
        return $authUser->can('Replicate:FinancialTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinancialTransaction');
    }

}