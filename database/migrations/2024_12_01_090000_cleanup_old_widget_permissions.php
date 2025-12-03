<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if the permissions table exists
        if (!Schema::hasTable('permissions')) {
            return;
        }

        // Remove old widget permissions created by the seeder
        $oldPermissions = [
            'widget_RfqStatsWidget',
            'widget_PurchaseOrderStatsWidget',
            'widget_FinancialOverviewWidget',
        ];

        foreach ($oldPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to restore old permissions
    }
};
