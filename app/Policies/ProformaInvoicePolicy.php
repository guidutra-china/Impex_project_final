<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProformaInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProformaInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProformaInvoice');
    }

    public function view(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('View:ProformaInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProformaInvoice');
    }

    public function update(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('Update:ProformaInvoice');
    }

    public function delete(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('Delete:ProformaInvoice');
    }

    public function restore(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('Restore:ProformaInvoice');
    }

    public function forceDelete(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('ForceDelete:ProformaInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProformaInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProformaInvoice');
    }

    public function replicate(AuthUser $authUser, ProformaInvoice $proformaInvoice): bool
    {
        return $authUser->can('Replicate:ProformaInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProformaInvoice');
    }

}