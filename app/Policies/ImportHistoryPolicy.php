<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ImportHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportHistory');
    }

    public function view(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('View:ImportHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportHistory');
    }

    public function update(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('Update:ImportHistory');
    }

    public function delete(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('Delete:ImportHistory');
    }

    public function restore(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('Restore:ImportHistory');
    }

    public function forceDelete(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('ForceDelete:ImportHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportHistory');
    }

    public function replicate(AuthUser $authUser, ImportHistory $importHistory): bool
    {
        return $authUser->can('Replicate:ImportHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportHistory');
    }

}