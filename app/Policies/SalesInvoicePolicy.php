<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SalesInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalesInvoice');
    }

    public function view(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('View:SalesInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalesInvoice');
    }

    public function update(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('Update:SalesInvoice');
    }

    public function delete(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('Delete:SalesInvoice');
    }

    public function restore(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('Restore:SalesInvoice');
    }

    public function forceDelete(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('ForceDelete:SalesInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalesInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalesInvoice');
    }

    public function replicate(AuthUser $authUser, SalesInvoice $salesInvoice): bool
    {
        return $authUser->can('Replicate:SalesInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalesInvoice');
    }

}