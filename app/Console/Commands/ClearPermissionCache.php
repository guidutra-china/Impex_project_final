<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class ClearPermissionCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:cache-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear permission cache (useful after changing role permissions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->info('✅ Permission cache cleared successfully!');
        $this->info('💡 Users need to logout and login again to see changes.');
        
        return Command::SUCCESS;
    }
}
