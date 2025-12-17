<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PortalTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first client (or create one if doesn't exist)
        $client = Client::first();
        
        if (!$client) {
            $this->command->error('No clients found! Please create a client first.');
            return;
        }

        // Ensure portal roles exist
        $purchasingRole = Role::firstOrCreate(['name' => 'purchasing', 'guard_name' => 'web']);
        $financeRole = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);

        // Create purchasing user
        $purchasingUser = User::firstOrCreate(
            ['email' => 'purchasing@test.com'],
            [
                'name' => 'Test Purchasing User',
                'password' => bcrypt('password'),
                'client_id' => $client->id,
                'status' => 'active',
            ]
        );
        
        if (!$purchasingUser->hasRole('purchasing')) {
            $purchasingUser->assignRole('purchasing');
        }

        // Create finance user
        $financeUser = User::firstOrCreate(
            ['email' => 'finance@test.com'],
            [
                'name' => 'Test Finance User',
                'password' => bcrypt('password'),
                'client_id' => $client->id,
                'status' => 'active',
            ]
        );
        
        if (!$financeUser->hasRole('finance')) {
            $financeUser->assignRole('finance');
        }

        $this->command->info('Portal test users created successfully!');
        $this->command->info('');
        $this->command->info('Purchasing User:');
        $this->command->info('  Email: purchasing@test.com');
        $this->command->info('  Password: password');
        $this->command->info('  Client: ' . $client->name);
        $this->command->info('');
        $this->command->info('Finance User:');
        $this->command->info('  Email: finance@test.com');
        $this->command->info('  Password: password');
        $this->command->info('  Client: ' . $client->name);
        $this->command->info('');
        $this->command->info('Access portal at: /portal/login');
    }
}
