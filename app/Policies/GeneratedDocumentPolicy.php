<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GeneratedDocument;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeneratedDocumentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GeneratedDocument');
    }

    public function view(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('View:GeneratedDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GeneratedDocument');
    }

    public function update(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('Update:GeneratedDocument');
    }

    public function delete(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('Delete:GeneratedDocument');
    }

    public function restore(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('Restore:GeneratedDocument');
    }

    public function forceDelete(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('ForceDelete:GeneratedDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GeneratedDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GeneratedDocument');
    }

    public function replicate(AuthUser $authUser, GeneratedDocument $generatedDocument): bool
    {
        return $authUser->can('Replicate:GeneratedDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GeneratedDocument');
    }

}