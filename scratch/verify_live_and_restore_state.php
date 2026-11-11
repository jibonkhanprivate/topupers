<?php
$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');

echo "=== VERIFYING LIVE DATABASE (rechax_official) ===\n";
$o32 = $pdo->query("SELECT id, order_number, payment_trx, order_status, total_price FROM `rechax_official`.`orders` WHERE id = 32")->fetch(PDO::FETCH_ASSOC);
echo "Order #32: " . json_encode($o32) . "\n";
$colCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders'")->fetchColumn();
echo "Column Count: $colCount\n";
$idxCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'uq_orders_normalized_payment_trx'")->fetchColumn();
echo "Unique Index Exists: " . ($idxCount > 0 ? "YES (VIOLATION)" : "NO (CORRECT)") . "\n";

echo "\n=== VERIFYING C6.1 RESTORE DATABASE (rechax_restore_test_c6_1_20261014_035114) ===\n";
$rstO32 = $pdo->query("SELECT id, order_number, payment_trx, order_status, total_price FROM `rechax_restore_test_c6_1_20261014_035114`.`orders` WHERE id = 32")->fetch(PDO::FETCH_ASSOC);
echo "Order #32: " . json_encode($rstO32) . "\n";
$rstColCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'rechax_restore_test_c6_1_20261014_035114' AND TABLE_NAME = 'orders'")->fetchColumn();
echo "Column Count: $rstColCount\n";

echo "\n=== VERIFYING NO LEFTOVER REHEARSAL DATABASES ===\n";
$dbs = $pdo->query("SHOW DATABASES LIKE 'rechax_rehearsal%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Rehearsal DBs remaining: " . (empty($dbs) ? "NONE (CLEAN)" : implode(', ', $dbs)) . "\n";
