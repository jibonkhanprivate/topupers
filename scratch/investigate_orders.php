<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();

echo "=== ORDERS TABLE INVESTIGATION (READ-ONLY) ===\n";

$rows = $pdo->query("SELECT id, order_number, order_status, created_at FROM orders")->fetchAll(PDO::FETCH_ASSOC);
echo "Total rows count: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | Order Number: " . $r['order_number'] . " | Status: " . $r['order_status'] . " | Created At: " . $r['created_at'] . "\n";
}

$status = $pdo->query("SHOW TABLE STATUS WHERE Name = 'orders'")->fetch(PDO::FETCH_ASSOC);
echo "Auto_increment value: " . ($status['Auto_increment'] ?? 'N/A') . "\n";
echo "Information schema table rows estimate: " . ($status['Rows'] ?? 'N/A') . "\n";

// Check all tables in DB
$allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "All tables in DB: " . implode(', ', $allTables) . "\n";
