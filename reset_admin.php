<?php

/**
 * Reset Admin User Script
 * 
 * This script resets the admin@impex.ltd user with:
 * - New password: "password"
 * - super_admin role
 * - is_admin flag set to 1
 * 
 * Run: php artisan tinker < reset_admin.php
 */

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "🔧 Resetting admin user...\n\n";

// Find or create the user
$user = User::where('email', 'admin@impex.ltd')->first();

if (!$user) {
    echo "❌ User admin@impex.ltd not found!\n";
    echo "Creating new admin user...\n\n";
    
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@impex.ltd',
        'password' => bcrypt('password'),
        'is_admin' => 1,
    ]);
    
    echo "✅ User created!\n";
} else {
    echo "✅ User found!\n";
    echo "Updating user...\n\n";
    
    // Update password
    $user->password = bcrypt('password');
    $user->is_admin = 1;
    $user->save();
    
    echo "✅ Password updated!\n";
}

// Ensure super_admin role exists
$superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
echo "✅ super_admin role exists\n";

// Remove all roles first
$user->roles()->detach();
echo "✅ Removed old roles\n";

// Assign super_admin role
$user->assignRole('super_admin');
echo "✅ Assigned super_admin role\n\n";

// Display info
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 Admin user reset successfully!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "📧 Email:    admin@impex.ltd\n";
echo "🔑 Password: password\n";
echo "👑 Role:     super_admin\n";
echo "✅ is_admin: 1\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "You can now login to the admin panel!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
