<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * SyncShieldPermissions Command
 * 
 * Automatically generates Shield permissions for all Filament resources
 * and assigns them to super_admin role.
 * 
 * This command should be run:
 * - After migrations in deployment
 * - When new resources are created
 * - Periodically to ensure permissions are in sync
 */
class SyncShieldPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shield:sync 
                            {--force : Force regeneration even if permissions exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically sync Shield permissions for all Filament resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Syncing Shield permissions...');
        $this->newLine();

        try {
            // Generate permissions for all resources
            $this->info('📝 Generating permissions for all resources...');
            Artisan::call('shield:generate', [
                '--all' => true,
                '--option' => '2', // Generate for all resources
            ], $this->output);

            $this->newLine();
            $this->info('✅ Permissions generated successfully!');
            $this->newLine();

            // Assign all permissions to super_admin role
            $this->info('👑 Assigning all permissions to super_admin role...');
            
            $superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
            
            if ($superAdminRole) {
                // Get all permissions
                $allPermissions = \Spatie\Permission\Models\Permission::all();
                
                // Sync all permissions to super_admin
                $superAdminRole->syncPermissions($allPermissions);
                
                $this->info('✅ Super admin now has ' . $allPermissions->count() . ' permissions');
            } else {
                $this->warn('⚠️  Super admin role not found. Skipping permission assignment.');
            }

            $this->newLine();
            $this->info('🎉 Shield permissions sync completed successfully!');
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to sync Shield permissions:');
            $this->error('   ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }
}
