<?php
require_once __DIR__ . '/../config/database.php';
$db = db();
$cols = $db->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
echo 'Orders columns count: ' . count($cols) . PHP_EOL;
echo 'Orders column list: ' . implode(', ', $cols) . PHP_EOL;
echo 'Orders count: ' . $db->query('SELECT COUNT(*) FROM orders')->fetchColumn() . PHP_EOL;
echo 'Payments count: ' . $db->query('SELECT COUNT(*) FROM payments')->fetchColumn() . PHP_EOL;
echo 'Tables count: ' . $db->query('SHOW TABLES')->rowCount() . PHP_EOL;
$binanceMethod = $db->query("SELECT * FROM payment_methods WHERE method_key = 'binance'")->fetch(PDO::FETCH_ASSOC);
echo 'Binance method in DB: ' . ($binanceMethod ? 'Exists' : 'Does not exist') . PHP_EOL;
$switcher = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'currency_switcher_enabled'")->fetchColumn();
echo 'Currency switcher setting: ' . ($switcher !== false ? $switcher : 'Does not exist') . PHP_EOL;
