<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

startUserSession();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'johnnyislam112';
$_SERVER['REQUEST_URI'] = '/user/dashboard.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

foreach (['dashboard.php', 'orders.php', 'profile.php'] as $file) {
    ob_start();
    include __DIR__ . '/../user/' . $file;
    $html = ob_get_clean();

    echo "=== CHECKING user/$file ===\n";

    // 1. Check tp-sidebar occurrences
    preg_match_all('/class="[^"]*tp-sidebar[^"]*"/', $html, $sbMatches);
    echo "  tp-sidebar count: " . count($sbMatches[0]) . "\n";

    // 2. Check tp-mobile-nav occurrences
    preg_match_all('/class="[^"]*tp-mobile-nav[^"]*"/', $html, $mnMatches);
    echo "  tp-mobile-nav count: " . count($mnMatches[0]) . "\n";

    // 3. Check duplicate IDs
    preg_match_all('/id="([^"]+)"/', $html, $idMatches);
    $ids = $idMatches[1];
    $idCounts = array_count_values($ids);
    $dupIds = array_filter($idCounts, fn($cnt) => $cnt > 1);
    if (!empty($dupIds)) {
        echo "  DUPLICATE IDs found:\n";
        foreach ($dupIds as $id => $cnt) {
            echo "    #$id ($cnt times)\n";
        }
    } else {
        echo "  Duplicate IDs: NONE\n";
    }

    echo "\n";
}
