<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();
$ordersCount = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$paymentsCount = (int)$db->query('SELECT COUNT(*) FROM payments')->fetchColumn();
$couponsCount = (int)$db->query('SELECT COUNT(*) FROM coupons')->fetchColumn();
$settings = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('currency_switcher_enabled', 'binance_active')")->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Orders count: $ordersCount\n";
echo "Payments count: $paymentsCount\n";
echo "Coupons count: $couponsCount\n";
echo "Live settings:\n";
foreach ($settings as $k => $v) {
    echo "  $k = $v\n";
}
