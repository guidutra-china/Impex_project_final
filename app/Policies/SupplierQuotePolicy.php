<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SupplierQuote;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierQuotePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierQuote');
    }

    public function view(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('View:SupplierQuote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierQuote');
    }

    public function update(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('Update:SupplierQuote');
    }

    public function delete(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('Delete:SupplierQuote');
    }

    public function restore(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('Restore:SupplierQuote');
    }

    public function forceDelete(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('ForceDelete:SupplierQuote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierQuote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierQuote');
    }

    public function replicate(AuthUser $authUser, SupplierQuote $supplierQuote): bool
    {
        return $authUser->can('Replicate:SupplierQuote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierQuote');
    }

}