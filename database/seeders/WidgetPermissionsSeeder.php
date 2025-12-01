<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WidgetPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define widget permissions
        $widgets = [
            'widget_RfqStatsWidget',
            'widget_PurchaseOrderStatsWidget',
            'widget_FinancialOverviewWidget',
        ];

        // Create permissions if they don't exist
        foreach ($widgets as $widget) {
            Permission::firstOrCreate(
                ['name' => $widget],
                ['guard_name' => 'web']
            );
        }

        // Give all widget permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($widgets);
            $this->command->info('✅ Widget permissions granted to super_admin');
        }

        // Optionally give to panel_user (you can customize this)
        $panelUser = Role::where('name', 'panel_user')->first();
        if ($panelUser) {
            $panelUser->givePermissionTo($widgets);
            $this->command->info('✅ Widget permissions granted to panel_user');
        }

        $this->command->info('✅ Widget permissions created successfully!');
    }
}
