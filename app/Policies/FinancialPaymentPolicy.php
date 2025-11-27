<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FinancialPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialPayment');
    }

    public function view(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('View:FinancialPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialPayment');
    }

    public function update(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('Update:FinancialPayment');
    }

    public function delete(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('Delete:FinancialPayment');
    }

    public function restore(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('Restore:FinancialPayment');
    }

    public function forceDelete(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('ForceDelete:FinancialPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinancialPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinancialPayment');
    }

    public function replicate(AuthUser $authUser, FinancialPayment $financialPayment): bool
    {
        return $authUser->can('Replicate:FinancialPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinancialPayment');
    }

}