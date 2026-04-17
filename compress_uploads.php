<?php

require_once 'image_optimizer.php';

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    echo "Uploads directory not found: {$uploadsDir}\n";
    exit(1);
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$iterator = new DirectoryIterator($uploadsDir);

$processed = 0;
$optimized = 0;
$bytesBefore = 0;
$bytesAfter = 0;

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isDot() || !$fileInfo->isFile()) {
        continue;
    }

    $ext = strtolower($fileInfo->getExtension());
    if (!in_array($ext, $allowedExtensions, true)) {
        continue;
    }

    $processed++;
    $path = $fileInfo->getPathname();
    clearstatcache(true, $path);
    $before = filesize($path) ?: 0;
    $bytesBefore += $before;

    $ok = optimizeImageInPlace($path, 1920);
    clearstatcache(true, $path);
    $after = filesize($path) ?: 0;
    $bytesAfter += $after;

    if ($ok && $after <= $before) {
        $optimized++;
    }
}

$saved = max(0, $bytesBefore - $bytesAfter);
$pct = $bytesBefore > 0 ? round(($saved / $bytesBefore) * 100, 2) : 0;

echo "Processed: {$processed}\n";
echo "Optimized: {$optimized}\n";
echo "Before: {$bytesBefore} bytes\n";
echo "After: {$bytesAfter} bytes\n";
echo "Saved: {$saved} bytes ({$pct}%)\n";

