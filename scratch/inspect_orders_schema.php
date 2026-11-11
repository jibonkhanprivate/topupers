<?php
require_once __DIR__ . '/../config/database.php';
$db = db();

echo "=== TABLE STATUS ===\n";
$status = $db->query("SHOW TABLE STATUS LIKE 'orders'")->fetch(PDO::FETCH_ASSOC);
echo "Engine: " . ($status['Engine'] ?? 'N/A') . "\n";
echo "Row Format: " . ($status['Row_format'] ?? 'N/A') . "\n";
echo "Collation: " . ($status['Collation'] ?? 'N/A') . "\n\n";

echo "=== SHOW COLUMNS payment_trx, order_status ===\n";
$cols = $db->query("SHOW FULL COLUMNS FROM orders WHERE Field IN ('payment_trx', 'order_status')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "Field: {$c['Field']} | Type: {$c['Type']} | Null: {$c['Null']} | Default: {$c['Default']} | Collation: {$c['Collation']}\n";
}

echo "\n=== INDEXES ON orders ===\n";
$indexes = $db->query("SHOW INDEXES FROM orders")->fetchAll(PDO::FETCH_ASSOC);
foreach ($indexes as $idx) {
    echo "Key: {$idx['Key_name']} | Column: {$idx['Column_name']} | Unique: " . ($idx['Non_unique'] ? 'NO' : 'YES') . "\n";
}

echo "\n=== MARIADB VERSION ===\n";
echo $db->query("SELECT VERSION()")->fetchColumn() . "\n";
