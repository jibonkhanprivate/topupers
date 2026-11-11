<?php
require_once __DIR__ . '/../config/database.php';
$users = db()->query("SELECT id, username, email, full_name, phone, wallet_balance, is_verified, created_at FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "USERS COUNT: " . count($users) . "\n";
print_r($users);

$orders = db()->query("SELECT id, order_number, user_id, total_price, payment_method, payment_status, order_status, created_at FROM orders LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "\nORDERS COUNT: " . count($orders) . "\n";
print_r($orders);
