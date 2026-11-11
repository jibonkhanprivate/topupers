<?php
/**
 * scratch/audit_live_discrepancy.php
 *
 * READ-ONLY query of rechax_official to inspect Order #32, orders table, payments table,
 * payment methods, exchange-rate settings, and lock file.
 */

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=localhost;dbname=rechax_official;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

echo "======================================================================\n";
echo "READ-ONLY AUDIT: rechax_official\n";
echo "======================================================================\n\n";

// 1. Order #32 exact details
echo "[1] ORDER #32 RAW FIELDS:\n";
$o32 = $pdo->query("SELECT * FROM orders WHERE id = 32")->fetch();
if ($o32) {
    foreach ($o32 as $k => $v) {
        echo "  $k: " . var_export($v, true) . "\n";
    }
} else {
    echo "  Order #32 NOT FOUND!\n";
}

// 2. Orders & Payments row counts
echo "\n[2] ROW COUNTS:\n";
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$paymentsCount = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
echo "  orders total rows:   {$ordersCount}\n";
echo "  payments total rows: {$paymentsCount}\n";

// 3. Schema & columns of orders and payments
echo "\n[3] SCHEMA COLUMNS:\n";
$ordersCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
echo "  orders column count: " . count($ordersCols) . "\n";
$ordersColNames = array_column($ordersCols, 'Field');
echo "  orders columns: " . implode(', ', $ordersColNames) . "\n";

$paymentsCols = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll();
echo "  payments column count: " . count($paymentsCols) . "\n";
$paymentsColNames = array_column($paymentsCols, 'Field');
echo "  payments columns: " . implode(', ', $paymentsColNames) . "\n";

// 4. Indexes
echo "\n[4] INDEXES:\n";
$ordersIndexes = $pdo->query("SHOW INDEX FROM orders")->fetchAll();
echo "  orders indexes:\n";
foreach ($ordersIndexes as $idx) {
    echo "    - Key_name: {$idx['Key_name']}, Column_name: {$idx['Column_name']}, Non_unique: {$idx['Non_unique']}\n";
}

// 5. Payment Methods
echo "\n[5] PAYMENT METHODS IN DB:\n";
$pms = $pdo->query("SELECT id, method_key, method_label, is_active, sort_order FROM payment_methods ORDER BY sort_order ASC")->fetchAll();
foreach ($pms as $pm) {
    echo "  - ID {$pm['id']}: key='{$pm['method_key']}', label='{$pm['method_label']}', is_active={$pm['is_active']}, sort_order={$pm['sort_order']}\n";
}

// 6. Exchange Rate Settings
echo "\n[6] EXCHANGE RATE & BINANCE SETTINGS:\n";
$settingsStmt = $pdo->query("
    SELECT setting_key, setting_value FROM settings 
    WHERE setting_key LIKE '%currency%' 
       OR setting_key LIKE '%rate%' 
       OR setting_key LIKE '%binance%'
    ORDER BY setting_key ASC
");
$stRows = $settingsStmt->fetchAll();
foreach ($stRows as $st) {
    echo "  - {$st['setting_key']}: " . var_export($st['setting_value'], true) . "\n";
}

// 7. Write Freeze Lock existence
echo "\n[7] PRODUCTION WRITE FREEZE LOCK FILE:\n";
$lockPath = dirname(__DIR__) . '/config/write_freeze.lock';
echo "  Path: {$lockPath}\n";
echo "  Exists: " . (file_exists($lockPath) ? "YES (ACTIVE)" : "NO (INACTIVE)") . "\n";

echo "\n======================================================================\n";
