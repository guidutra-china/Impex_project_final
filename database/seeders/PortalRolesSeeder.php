<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PortalRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Create portal roles
        $roles = [
            [
                'name' => 'purchasing',
                'guard_name' => 'web',
            ],
            [
                'name' => 'finance',
                'guard_name' => 'web',
            ],
            [
                'name' => 'logistics',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']]
            );
        }

        $this->command->info('Portal roles created successfully!');
    }
}
