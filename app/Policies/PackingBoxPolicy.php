<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PackingBox;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackingBoxPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PackingBox');
    }

    public function view(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('View:PackingBox');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PackingBox');
    }

    public function update(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('Update:PackingBox');
    }

    public function delete(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('Delete:PackingBox');
    }

    public function restore(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('Restore:PackingBox');
    }

    public function forceDelete(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('ForceDelete:PackingBox');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PackingBox');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PackingBox');
    }

    public function replicate(AuthUser $authUser, PackingBox $packingBox): bool
    {
        return $authUser->can('Replicate:PackingBox');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PackingBox');
    }

}