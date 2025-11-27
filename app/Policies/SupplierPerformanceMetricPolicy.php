<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SupplierPerformanceMetric;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPerformanceMetricPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierPerformanceMetric');
    }

    public function view(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('View:SupplierPerformanceMetric');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierPerformanceMetric');
    }

    public function update(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('Update:SupplierPerformanceMetric');
    }

    public function delete(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('Delete:SupplierPerformanceMetric');
    }

    public function restore(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('Restore:SupplierPerformanceMetric');
    }

    public function forceDelete(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('ForceDelete:SupplierPerformanceMetric');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierPerformanceMetric');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierPerformanceMetric');
    }

    public function replicate(AuthUser $authUser, SupplierPerformanceMetric $supplierPerformanceMetric): bool
    {
        return $authUser->can('Replicate:SupplierPerformanceMetric');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierPerformanceMetric');
    }

}