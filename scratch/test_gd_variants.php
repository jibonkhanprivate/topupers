<?php
// Quick test: simulate what happens when a banner is uploaded
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== GD + generateBannerVariants() Test ===\n\n";

// Check GD
echo "PHP GD loaded: " . (function_exists('imagecreatefromjpeg') ? "✓ YES" : "✗ NO") . "\n";
echo "PNG support  : " . (function_exists('imagecreatefrompng')  ? "✓ YES" : "✗ NO") . "\n";
echo "JPEG support : " . (function_exists('imagecreatefromjpeg') ? "✓ YES" : "✗ NO") . "\n\n";

// Pick one existing original banner as the test source
$bannerDir = APP_ROOT . '/assets/img/banners/';
$testSrc   = $bannerDir . '1788191826_Honor of Kings4.jpg';

if (!file_exists($testSrc)) {
    echo "Test source not found: $testSrc\n";
    exit(1);
}

// Remove existing variants to test re-generation
$variants = ['opt', 'tablet', 'mobile'];
$base     = pathinfo($testSrc, PATHINFO_FILENAME);
foreach ($variants as $v) {
    $vPath = $bannerDir . $base . '-' . $v . '.jpg';
    if (file_exists($vPath)) {
        unlink($vPath);
        echo "Removed existing: $base-$v.jpg\n";
    }
}

echo "\nRunning generateBannerVariants()...\n";
generateBannerVariants($testSrc);

echo "\nResult:\n";
foreach ($variants as $v) {
    $vPath = $bannerDir . $base . '-' . $v . '.jpg';
    if (file_exists($vPath)) {
        printf("  ✓ %-15s %s KB\n", $v . '.jpg', round(filesize($vPath)/1024, 1));
    } else {
        echo "  ✗ $v.jpg — NOT generated\n";
    }
}

echo "\nsrcset output:\n";
echo "  " . bannerSrcset('assets/img/banners/1788191826_Honor of Kings4.jpg') . "\n";
