<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';

$pdo = db();
$testDb = 'rechax_disposable_c59_test';

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDb}`");
$pdo->exec("USE `{$testDb}`");

$pdo->exec("
    CREATE TABLE `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(50) NOT NULL,
        `order_status` VARCHAR(20) NOT NULL,
        `payment_trx` VARCHAR(100) DEFAULT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$sqlCol = StandaloneUniquePaymentTrxRunner::getAddColumnSql();
$pdo->exec($sqlCol);

$sqlIdx = StandaloneUniquePaymentTrxRunner::getAddIndexSql();
$pdo->exec($sqlIdx);

$cStmt = $pdo->prepare("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE, IS_NULLABLE, EXTRA, COLLATION_NAME, GENERATION_EXPRESSION
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'normalized_payment_trx'
");
$cStmt->execute([$testDb]);
$colInfo = $cStmt->fetch(PDO::FETCH_ASSOC);

$iStmt = $pdo->prepare("
    SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX, COLLATION, CARDINALITY, INDEX_TYPE
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND INDEX_NAME = 'uq_orders_normalized_payment_trx'
");
$iStmt->execute([$testDb]);
$idxInfo = $iStmt->fetchAll(PDO::FETCH_ASSOC);

echo "COL INFO:\n" . json_encode($colInfo, JSON_PRETTY_PRINT) . "\n\n";
echo "INDEX INFO:\n" . json_encode($idxInfo, JSON_PRETTY_PRINT) . "\n\n";

$pdo->exec("USE `rechax_official`");
$pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
echo "Cleaned up disposable database.\n";
