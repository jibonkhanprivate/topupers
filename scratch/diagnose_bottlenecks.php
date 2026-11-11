<?php
// scratch/diagnose_bottlenecks.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// 1. Measure direct HTTP response time
$t0 = microtime(true);
$ch = curl_init('http://localhost/RechaX%20Official/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
$t1 = microtime(true);

echo "=== 1. SERVER RESPONSE & TTFB ===\n";
echo "HTTP Status Code: " . $info['http_code'] . "\n";
echo "Total Request Time: " . number_format($info['total_time'] * 1000, 2) . " ms\n";
echo "Connect Time: " . number_format($info['connect_time'] * 1000, 2) . " ms\n";
echo "Start Transfer Time (TTFB): " . number_format($info['starttransfer_time'] * 1000, 2) . " ms\n";
echo "--- 1b. TABLE INDEXES AUDIT ---\n";
$db = db();
foreach (['banners', 'games', 'products', 'settings', 'payment_methods'] as $t) {
    echo "Table '$t':\n";
    foreach ($db->query("SHOW INDEX FROM `$t`")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
        echo "  - Index: {$idx['Key_name']} on column {$idx['Column_name']}\n";
    }
}
echo "\n";


// 2. Measure Database Queries & Profiling
echo "--- 2. DATABASE QUERIES AUDIT ---\n";
// Track all queries
class DBQueryLogger {
    public static $queries = [];
    public static function log($sql, $params, $time) {
        self::$queries[] = ['sql' => $sql, 'params' => $params, 'time' => $time];
    }
}

// Inspect query count by simulating index.php logic
$db = db();
$db->exec("SET profiling = 1");

$qStart = microtime(true);
// Settings cache test
$settings = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$heroBanners = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();
$featuredGames = $db->query("SELECT * FROM games WHERE is_active = 1 AND is_featured = 1 AND (category = 'game' OR category IS NULL OR category = '') ORDER BY sort_order ASC")->fetchAll();
$allGames = $db->query("SELECT * FROM games WHERE is_active = 1 AND (category = 'game' OR category IS NULL OR category = '') ORDER BY sort_order ASC")->fetchAll();
$popularProducts = $db->query("SELECT p.*, g.name AS game_name, g.slug AS game_slug FROM products p JOIN games g ON g.id = p.game_id WHERE p.is_popular = 1 AND p.is_active = 1 LIMIT 6")->fetchAll();
$paymentMethods = $db->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
$qEnd = microtime(true);

echo "Simulated homepage DB queries time: " . number_format(($qEnd - $qStart) * 1000, 2) . " ms\n";

$profiles = $db->query("SHOW PROFILES")->fetchAll(PDO::FETCH_ASSOC);
foreach ($profiles as $p) {
    echo "  Query: " . substr($p['Query'], 0, 70) . "... Duration: " . number_format($p['Duration'] * 1000, 3) . " ms\n";
}
echo "\n";

// 3. Inspect Image Sizes Loaded on Homepage
echo "--- 3. IMAGE ASSET SIZES (CRITICAL BOTTLENECK) ---\n";
$totalImgSize = 0;
// Hero banners
echo "Hero Banners:\n";
foreach ($heroBanners as $b) {
    $f = APP_ROOT . '/' . $b['image'];
    if (file_exists($f)) {
        $size = filesize($f);
        $totalImgSize += $size;
        $dims = @getimagesize($f);
        $dimStr = $dims ? "{$dims[0]}x{$dims[1]}" : "unknown";
        echo "  - " . basename($f) . ": " . number_format($size / 1024, 1) . " KB ({$dimStr}, mime: " . ($dims['mime'] ?? 'unknown') . ")\n";
    }
}

// Game icons
echo "Games on Homepage:\n";
foreach ($allGames as $g) {
    if (!empty($g['icon'])) {
        $f = APP_ROOT . '/uploads/games/' . $g['icon'];
        if (file_exists($f)) {
            $size = filesize($f);
            $totalImgSize += $size;
            if ($size > 100 * 1024) {
                echo "  - LARGE ICON: " . basename($f) . ": " . number_format($size / 1024, 1) . " KB\n";
            }
        }
    }
}
echo "Total image weight for initial page: " . number_format($totalImgSize / 1024 / 1024, 2) . " MB\n\n";

// 4. CSS & JS Bundle Sizes
echo "--- 4. CSS & JS WEIGHT ---\n";
$cssFile = APP_ROOT . '/assets/css/style.css';
$jsFile = APP_ROOT . '/assets/js/main.js';
echo "style.css: " . number_format(filesize($cssFile) / 1024, 2) . " KB\n";
echo "main.js: " . number_format(filesize($jsFile) / 1024, 2) . " KB\n\n";

// 5. Check caching and compression headers
echo "--- 5. CACHING & COMPRESSION AUDIT ---\n";
$ch = curl_init('http://localhost/RechaX%20Official/assets/css/style.css');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$cssHeaders = curl_exec($ch);
curl_close($ch);

echo "style.css Response Headers:\n";
foreach (explode("\r\n", $cssHeaders) as $h) {
    if (stripos($h, 'cache-control') !== false || stripos($h, 'expires') !== false || stripos($h, 'content-encoding') !== false || stripos($h, 'etag') !== false) {
        echo "  $h\n";
    }
}
