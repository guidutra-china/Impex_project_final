<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContainerType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContainerTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContainerType');
    }

    public function view(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('View:ContainerType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContainerType');
    }

    public function update(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('Update:ContainerType');
    }

    public function delete(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('Delete:ContainerType');
    }

    public function restore(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('Restore:ContainerType');
    }

    public function forceDelete(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('ForceDelete:ContainerType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContainerType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContainerType');
    }

    public function replicate(AuthUser $authUser, ContainerType $containerType): bool
    {
        return $authUser->can('Replicate:ContainerType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContainerType');
    }

}