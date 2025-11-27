<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompanySetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanySettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompanySetting');
    }

    public function view(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('View:CompanySetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompanySetting');
    }

    public function update(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('Update:CompanySetting');
    }

    public function delete(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('Delete:CompanySetting');
    }

    public function restore(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('Restore:CompanySetting');
    }

    public function forceDelete(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('ForceDelete:CompanySetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompanySetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompanySetting');
    }

    public function replicate(AuthUser $authUser, CompanySetting $companySetting): bool
    {
        return $authUser->can('Replicate:CompanySetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompanySetting');
    }

}