<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$cols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders'")->fetchColumn();
$hasCol = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders' AND COLUMN_NAME='normalized_payment_trx'")->fetchColumn();
$hasIdx = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders' AND INDEX_NAME='uq_orders_normalized_payment_trx'")->fetchColumn();
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$order = $pdo->query("SELECT order_number, order_status, payment_trx FROM orders LIMIT 1")->fetch(PDO::FETCH_ASSOC);

echo "Total orders columns: " . $cols . "\n";
echo "Has normalized_payment_trx column: " . ($hasCol ? "YES" : "NO") . "\n";
echo "Has uq_orders_normalized_payment_trx index: " . ($hasIdx ? "YES" : "NO") . "\n";
echo "Total orders: " . $cnt . "\n";
if ($order) {
    echo "Order number: " . $order['order_number'] . "\n";
    echo "Order status: " . $order['order_status'] . "\n";
    echo "Trx reference length: " . (isset($order['payment_trx']) ? strlen($order['payment_trx']) : 'NULL') . "\n";
}
