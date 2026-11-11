<?php
require_once __DIR__ . '/../config/database.php';
$db = db();
$stmt = $db->query("SELECT id, order_number, player_id, payment_trx, order_status, created_at FROM orders");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($rows) . "\n";
print_r($rows);
