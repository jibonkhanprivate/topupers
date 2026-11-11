<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$stmt = $pdo->query('SELECT id, name, category, is_featured, is_stock_out, badge, amount, original_price FROM products WHERE game_id = 1 AND is_featured = 1');
echo "=== FEATURED PRODUCTS ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query('SELECT id, name, category, is_stock_out, amount FROM products WHERE game_id = 1 AND is_stock_out = 1');
echo "\n=== STOCK OUT PRODUCTS ===\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
