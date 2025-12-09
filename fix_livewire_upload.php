<?php

echo "=== LIVEWIRE UPLOAD FIX ===\n\n";

$directories = [
    __DIR__ . '/storage/app/livewire-tmp',
    __DIR__ . '/storage/app/public',
    __DIR__ . '/storage/app/public/company',
    __DIR__ . '/storage/framework/cache',
    __DIR__ . '/storage/framework/sessions',
    __DIR__ . '/storage/framework/views',
    __DIR__ . '/storage/logs',
];

foreach ($directories as $dir) {
    echo "Checking: $dir\n";
    
    if (!is_dir($dir)) {
        echo "  Creating...\n";
        mkdir($dir, 0775, true);
    }
    
    echo "  Exists: " . (is_dir($dir) ? 'YES' : 'NO') . "\n";
    echo "  Writable: " . (is_writable($dir) ? 'YES' : 'NO') . "\n";
    echo "  Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    
    // Try to fix permissions
    if (!is_writable($dir)) {
        echo "  Fixing permissions...\n";
        chmod($dir, 0775);
        echo "  Now Writable: " . (is_writable($dir) ? 'YES' : 'NO') . "\n";
    }
    
    echo "\n";
}

echo "=== LIVEWIRE CONFIG ===\n";
$configPath = __DIR__ . '/config/livewire.php';
if (file_exists($configPath)) {
    echo "Config exists: YES\n";
    $config = include $configPath;
    echo "Temporary upload path: " . ($config['temporary_file_upload']['disk'] ?? 'local') . "\n";
    echo "Directory: " . ($config['temporary_file_upload']['directory'] ?? 'livewire-tmp') . "\n";
} else {
    echo "Config exists: NO (using defaults)\n";
    echo "Default disk: local\n";
    echo "Default directory: livewire-tmp\n";
}

echo "\n=== FILESYSTEM CONFIG ===\n";
$filesystemPath = __DIR__ . '/config/filesystems.php';
if (file_exists($filesystemPath)) {
    $config = include $filesystemPath;
    $localDisk = $config['disks']['local'] ?? [];
    echo "Local disk root: " . ($localDisk['root'] ?? 'storage/app') . "\n";
}

echo "\n=== END FIX ===\n";
