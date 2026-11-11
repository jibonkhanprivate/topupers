<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();

echo "======================================================================\n";
echo "DUPLICATE TRANSACTION-REFERENCE PREFLIGHT AUDIT (READ ONLY)\n";
echo "======================================================================\n\n";

// 1. MariaDB version
$ver = $db->query("SELECT VERSION()")->fetchColumn();
echo "Database Version: $ver\n\n";

// 2. Column types, charset, collation for orders.payment_trx and orders.order_status
$stmt = $db->query("
    SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('payment_trx', 'order_status', 'id')
");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "--- Column Metadata in `orders` ---\n";
foreach ($cols as $c) {
    echo sprintf("%-15s %-15s Null:%-4s Charset:%-10s Collation:%-20s Default:%s\n",
        $c['COLUMN_NAME'], $c['COLUMN_TYPE'], $c['IS_NULLABLE'], $c['CHARACTER_SET_NAME'] ?? 'N/A', $c['COLLATION_NAME'] ?? 'N/A', var_export($c['COLUMN_DEFAULT'], true));
}
echo "\n";

// 3. Existing total rows and duplicate query on payment_trx
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
echo "Total orders in table: $totalOrders\n";

// Query for normalized duplicate payment_trx across active orders (excluding cancelled)
$dupActiveQuery = "
    SELECT 
        UPPER(TRIM(payment_trx)) AS norm_trx,
        COUNT(*) AS count_total,
        GROUP_CONCAT(order_number SEPARATOR ', ') AS order_numbers,
        GROUP_CONCAT(order_status SEPARATOR ', ') AS order_statuses
    FROM orders
    WHERE payment_trx IS NOT NULL 
      AND TRIM(payment_trx) != ''
      AND order_status != 'cancelled'
    GROUP BY UPPER(TRIM(payment_trx))
    HAVING COUNT(*) > 1
";
$dupActive = $db->query($dupActiveQuery)->fetchAll(PDO::FETCH_ASSOC);
echo "Active duplicate normalized references (excluding 'cancelled'): " . count($dupActive) . "\n";
if (!empty($dupActive)) {
    foreach ($dupActive as $d) {
        echo "  - Ref: '{$d['norm_trx']}', Count: {$d['count_total']}, Orders: {$d['order_numbers']}, Statuses: {$d['order_statuses']}\n";
    }
}

// Query for normalized duplicate payment_trx across active orders (excluding cancelled AND failed)
$dupActiveNoFailedQuery = "
    SELECT 
        UPPER(TRIM(payment_trx)) AS norm_trx,
        COUNT(*) AS count_total,
        GROUP_CONCAT(order_number SEPARATOR ', ') AS order_numbers,
        GROUP_CONCAT(order_status SEPARATOR ', ') AS order_statuses
    FROM orders
    WHERE payment_trx IS NOT NULL 
      AND TRIM(payment_trx) != ''
      AND order_status NOT IN ('cancelled', 'failed')
    GROUP BY UPPER(TRIM(payment_trx))
    HAVING COUNT(*) > 1
";
$dupActiveNoFailed = $db->query($dupActiveNoFailedQuery)->fetchAll(PDO::FETCH_ASSOC);
echo "Active duplicate normalized references (excluding 'cancelled' AND 'failed'): " . count($dupActiveNoFailed) . "\n";
if (!empty($dupActiveNoFailed)) {
    foreach ($dupActiveNoFailed as $d) {
        echo "  - Ref: '{$d['norm_trx']}', Count: {$d['count_total']}, Orders: {$d['order_numbers']}, Statuses: {$d['order_statuses']}\n";
    }
}

// Query for ALL duplicate normalized payment_trx regardless of status
$dupAllQuery = "
    SELECT 
        UPPER(TRIM(payment_trx)) AS norm_trx,
        COUNT(*) AS count_total,
        GROUP_CONCAT(order_number SEPARATOR ', ') AS order_numbers,
        GROUP_CONCAT(order_status SEPARATOR ', ') AS order_statuses
    FROM orders
    WHERE payment_trx IS NOT NULL 
      AND TRIM(payment_trx) != ''
    GROUP BY UPPER(TRIM(payment_trx))
    HAVING COUNT(*) > 1
";
$dupAll = $db->query($dupAllQuery)->fetchAll(PDO::FETCH_ASSOC);
echo "Total duplicate normalized references across ALL statuses: " . count($dupAll) . "\n";
if (!empty($dupAll)) {
    foreach ($dupAll as $d) {
        echo "  - Ref: '{$d['norm_trx']}', Count: {$d['count_total']}, Orders: {$d['order_numbers']}, Statuses: {$d['order_statuses']}\n";
    }
}

// Count empty, NULL, or whitespace-only payment_trx
$emptyCount = (int)$db->query("
    SELECT COUNT(*) 
    FROM orders 
    WHERE payment_trx IS NULL OR TRIM(payment_trx) = ''
")->fetchColumn();
echo "Orders with NULL, empty, or whitespace-only payment_trx: $emptyCount\n";

// 4. Existing Indexes on `orders`
$indexes = $db->query("SHOW INDEX FROM orders")->fetchAll(PDO::FETCH_ASSOC);
echo "\n--- Existing Indexes on `orders` ---\n";
$indexMap = [];
foreach ($indexes as $idx) {
    $indexMap[$idx['Key_name']][] = $idx['Column_name'];
}
foreach ($indexMap as $name => $cols) {
    echo "  - Index: $name (" . implode(', ', $cols) . ")\n";
}
