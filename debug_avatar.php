<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

echo "🔍 Avatar Debug Script\n";
echo "=====================\n\n";

// Get first user
$user = User::first();

if (!$user) {
    echo "❌ No users found in database\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Check 1: User has avatar field
echo "1. Checking avatar field in database:\n";
echo "   Avatar value: " . ($user->avatar ?: 'NULL') . "\n";
echo "   " . ($user->avatar ? "✅ Has avatar" : "❌ No avatar") . "\n\n";

// Check 2: getFilamentAvatarUrl method exists
echo "2. Checking getFilamentAvatarUrl() method:\n";
if (method_exists($user, 'getFilamentAvatarUrl')) {
    echo "   ✅ Method exists\n";
    $avatarUrl = $user->getFilamentAvatarUrl();
    echo "   Avatar URL: {$avatarUrl}\n";
    
    // Check if it's a storage URL or UI Avatars
    if (str_contains($avatarUrl, 'ui-avatars.com')) {
        echo "   ℹ️  Using UI Avatars fallback (no avatar uploaded)\n";
    } else {
        echo "   ✅ Using uploaded avatar\n";
        
        // Check if file exists
        if ($user->avatar) {
            $fullPath = storage_path('app/public/' . $user->avatar);
            echo "   File path: {$fullPath}\n";
            echo "   File exists: " . (file_exists($fullPath) ? "✅ Yes" : "❌ No") . "\n";
        }
    }
} else {
    echo "   ❌ Method does NOT exist\n";
}
echo "\n";

// Check 3: Storage link
echo "3. Checking storage symlink:\n";
$symlinkPath = public_path('storage');
if (is_link($symlinkPath)) {
    echo "   ✅ Symlink exists\n";
    echo "   Points to: " . readlink($symlinkPath) . "\n";
} else {
    echo "   ❌ Symlink does NOT exist\n";
    echo "   Run: php artisan storage:link\n";
}
echo "\n";

// Check 4: Avatars directory
echo "4. Checking avatars directory:\n";
$avatarsPath = storage_path('app/public/avatars');
if (is_dir($avatarsPath)) {
    echo "   ✅ Directory exists\n";
    $files = glob($avatarsPath . '/*');
    echo "   Files in directory: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "   Files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            echo "     - " . basename($file) . "\n";
        }
    }
} else {
    echo "   ❌ Directory does NOT exist\n";
    echo "   Will be created on first upload\n";
}
echo "\n";

// Check 5: FilamentUser interface
echo "5. Checking FilamentUser interface:\n";
if ($user instanceof \Filament\Models\Contracts\FilamentUser) {
    echo "   ✅ User implements FilamentUser\n";
} else {
    echo "   ❌ User does NOT implement FilamentUser\n";
}
echo "\n";

// Check 6: Test all users
echo "6. Testing all users:\n";
$users = User::all();
foreach ($users as $u) {
    $avatar = $u->avatar ?: 'none';
    $url = method_exists($u, 'getFilamentAvatarUrl') ? $u->getFilamentAvatarUrl() : 'N/A';
    $type = str_contains($url, 'ui-avatars.com') ? 'fallback' : 'uploaded';
    echo "   - {$u->name}: avatar={$avatar}, type={$type}\n";
}
echo "\n";

echo "=====================\n";
echo "✅ Debug complete!\n\n";

echo "📋 Summary:\n";
echo "- User model: " . (method_exists($user, 'getFilamentAvatarUrl') ? "✅" : "❌") . " Has getFilamentAvatarUrl()\n";
echo "- Storage link: " . (is_link($symlinkPath) ? "✅" : "❌") . " Symlink exists\n";
echo "- Avatar uploaded: " . ($user->avatar ? "✅" : "❌") . " User has avatar\n";
echo "\n";

if (!is_link($symlinkPath)) {
    echo "⚠️  ACTION REQUIRED: Run 'php artisan storage:link'\n";
}

if (!method_exists($user, 'getFilamentAvatarUrl')) {
    echo "⚠️  ACTION REQUIRED: Add getFilamentAvatarUrl() to User model\n";
}

if (!$user->avatar) {
    echo "ℹ️  INFO: Upload an avatar in the Profile page to test\n";
}
