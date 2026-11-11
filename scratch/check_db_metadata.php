<?php
require_once __DIR__ . '/../config/database.php';
$db = db();

$dbName = $db->query("SELECT DATABASE()")->fetchColumn();
$version = $db->query("SELECT VERSION()")->fetchColumn();
$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$tableInfo = $db->query("
    SELECT TABLE_NAME, ENGINE, ROW_FORMAT, TABLE_COLLATION 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
")->fetch(PDO::FETCH_ASSOC);

$cols = $db->query("
    SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
")->fetchAll(PDO::FETCH_ASSOC);

$colNames = array_column($cols, 'COLUMN_NAME');

$gate2bCols = ['currency', 'base_amount_bdt', 'exchange_rate', 'rate_buffer_percent', 'rate_mode', 'rate_source', 'charge_amount', 'payment_details'];
$existingGate2bCols = array_intersect($gate2bCols, $colNames);

$hasNormalizedCol = in_array('normalized_payment_trx', $colNames);

$indexes = $db->query("SHOW INDEXES FROM orders")->fetchAll(PDO::FETCH_ASSOC);
$indexNames = array_unique(array_column($indexes, 'Key_name'));
$hasUniqueIndex = in_array('uq_orders_normalized_payment_trx', $indexNames);

echo "Database Name: $dbName\n";
echo "Database Version: $version\n";
echo "Order Count: $orderCount\n";
echo "Order Table Engine: {$tableInfo['ENGINE']}\n";
echo "Order Table Row Format: {$tableInfo['ROW_FORMAT']}\n";
echo "Order Table Total Columns: " . count($colNames) . "\n";
echo "Gate 2B Migration Columns Present: " . (empty($existingGate2bCols) ? "NONE (0/8)" : implode(', ', $existingGate2bCols)) . "\n";
echo "Generated Column `normalized_payment_trx` Present: " . ($hasNormalizedCol ? "YES" : "NO") . "\n";
echo "Unique Index `uq_orders_normalized_payment_trx` Present: " . ($hasUniqueIndex ? "YES" : "NO") . "\n";
echo "Existing Indexes on `orders`: " . implode(', ', $indexNames) . "\n";
