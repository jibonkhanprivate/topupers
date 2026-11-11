<?php
require_once __DIR__ . '/../config/database.php';
$db = db();
$tables = ['activity_logs', 'admin_login_logs', 'admins', 'api_keys', 'banners', 'cart', 'coin_transactions', 'coupons', 'games', 'homepage_sections'];
foreach ($tables as $t) {
    echo "\nTABLE: $t\n";
    $cols = $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  - {$c['Field']} ({$c['Type']})\n";
    }
}
