<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RoleConfigurationSeeder
 * 
 * Configures the can_see_all field for existing roles.
 * This seeder ensures that critical roles have proper visibility settings.
 */
class RoleConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles that should see all records (admin-level roles)
        $rolesWithFullAccess = [
            'super_admin',
            'admin',
        ];

        // Roles that should only see their own records (user-level roles)
        $rolesWithLimitedAccess = [
            'panel_user',
            'user',
        ];

        // Update roles with full access
        foreach ($rolesWithFullAccess as $roleName) {
            DB::table('roles')
                ->where('name', $roleName)
                ->update(['can_see_all' => true]);
        }

        // Update roles with limited access
        foreach ($rolesWithLimitedAccess as $roleName) {
            DB::table('roles')
                ->where('name', $roleName)
                ->update(['can_see_all' => false]);
        }

        $this->command->info('Role visibility settings configured successfully.');
    }
}
