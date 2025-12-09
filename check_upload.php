<?php

echo "=== STORAGE UPLOAD DIAGNOSTIC ===\n\n";

// Check storage path
$storagePath = __DIR__ . '/storage/app/public';
echo "Storage Path: $storagePath\n";
echo "Exists: " . (is_dir($storagePath) ? 'YES' : 'NO') . "\n";
echo "Writable: " . (is_writable($storagePath) ? 'YES' : 'NO') . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($storagePath)), -4) . "\n\n";

// Check company directory
$companyPath = $storagePath . '/company';
echo "Company Path: $companyPath\n";
echo "Exists: " . (is_dir($companyPath) ? 'YES' : 'NO') . "\n";

if (!is_dir($companyPath)) {
    echo "Creating company directory...\n";
    mkdir($companyPath, 0755, true);
    echo "Created: " . (is_dir($companyPath) ? 'YES' : 'NO') . "\n";
}

echo "Writable: " . (is_writable($companyPath) ? 'YES' : 'NO') . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($companyPath)), -4) . "\n\n";

// Check public symlink
$publicLink = __DIR__ . '/public/storage';
echo "Public Symlink: $publicLink\n";
echo "Exists: " . (file_exists($publicLink) ? 'YES' : 'NO') . "\n";
echo "Is Link: " . (is_link($publicLink) ? 'YES' : 'NO') . "\n";
if (is_link($publicLink)) {
    echo "Points to: " . readlink($publicLink) . "\n";
}
echo "\n";

// Check PHP upload settings
echo "PHP Upload Settings:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n\n";

// Test write
$testFile = $companyPath . '/test.txt';
echo "Testing write to: $testFile\n";
$result = file_put_contents($testFile, 'test');
if ($result !== false) {
    echo "Write: SUCCESS\n";
    unlink($testFile);
    echo "Cleanup: SUCCESS\n";
} else {
    echo "Write: FAILED\n";
    echo "Error: " . error_get_last()['message'] . "\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
