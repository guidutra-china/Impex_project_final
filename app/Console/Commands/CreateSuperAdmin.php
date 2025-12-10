<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * CreateSuperAdmin Command
 * 
 * Interactive command to create a super admin user for the system.
 * Useful for initial setup or when you need to create an emergency admin account.
 */
class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin 
                            {--name= : The name of the super admin}
                            {--email= : The email of the super admin}
                            {--password= : The password of the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user for the system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔐 Creating Super Admin User');
        $this->newLine();

        // Get user input
        $name = $this->option('name') ?: $this->ask('Enter the admin name');
        $email = $this->option('email') ?: $this->ask('Enter the admin email');
        $password = $this->option('password') ?: $this->secret('Enter the admin password (min 8 characters)');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  • ' . $error);
            }
            return self::FAILURE;
        }

        // Check if super_admin role exists
        $superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->error('❌ The super_admin role does not exist in the database.');
            $this->info('💡 Run the following commands first:');
            $this->info('   php artisan migrate:fresh --seed');
            $this->info('   php artisan shield:install');
            return self::FAILURE;
        }

        // Create user
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            // Assign super_admin role
            $user->assignRole('super_admin');

            $this->newLine();
            $this->info('✅ Super admin user created successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Role', 'super_admin'],
                    ['Status', 'active'],
                ]
            );
            $this->newLine();
            $this->info('🚀 You can now login to the panel with these credentials.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to create super admin user:');
            $this->error('   ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
