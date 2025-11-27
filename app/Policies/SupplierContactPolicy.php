<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SupplierContact;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierContactPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierContact');
    }

    public function view(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('View:SupplierContact');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierContact');
    }

    public function update(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('Update:SupplierContact');
    }

    public function delete(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('Delete:SupplierContact');
    }

    public function restore(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('Restore:SupplierContact');
    }

    public function forceDelete(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('ForceDelete:SupplierContact');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierContact');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierContact');
    }

    public function replicate(AuthUser $authUser, SupplierContact $supplierContact): bool
    {
        return $authUser->can('Replicate:SupplierContact');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierContact');
    }

}