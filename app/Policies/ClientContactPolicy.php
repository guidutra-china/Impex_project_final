<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ClientContact;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientContactPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ClientContact');
    }

    public function view(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('View:ClientContact');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ClientContact');
    }

    public function update(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('Update:ClientContact');
    }

    public function delete(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('Delete:ClientContact');
    }

    public function restore(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('Restore:ClientContact');
    }

    public function forceDelete(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('ForceDelete:ClientContact');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ClientContact');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ClientContact');
    }

    public function replicate(AuthUser $authUser, ClientContact $clientContact): bool
    {
        return $authUser->can('Replicate:ClientContact');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ClientContact');
    }

}