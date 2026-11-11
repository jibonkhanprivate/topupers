<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$backupPath = 'C:\\xampp\\htdocs\\RechaX Official\\backups\\backup_phase_c6_0_20261013_202915\\db_rechax_official_c6_0_20261013_202915.sql';

echo "=== VERIFYING BACKUP DUMP CONTENT ===\n";
echo "File: " . $backupPath . "\n";
echo "Size: " . filesize($backupPath) . " bytes\n";
echo "SHA256: " . hash_file('sha256', $backupPath) . "\n\n";

$pdo = db();
$dbTables = $pdo->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA='rechax_official'")->fetchAll(PDO::FETCH_ASSOC);
echo "Live DB total tables in information_schema: " . count($dbTables) . "\n";

$content = file_get_contents($backupPath);

// Check MariaDB header
if (preg_match('/-- MariaDB dump.*Distrib ([0-9\.\-A-Za-z]+)/', $content, $m)) {
    echo "Dump Header: " . $m[0] . "\n";
}

// Check each table in dump
$missingTables = [];
foreach ($dbTables as $t) {
    $tName = $t['TABLE_NAME'];
    if (strpos($content, "CREATE TABLE `{$tName}`") === false && strpos($content, "CREATE TABLE IF NOT EXISTS `{$tName}`") === false) {
        $missingTables[] = $tName;
    }
}

if (empty($missingTables)) {
    echo "ALL " . count($dbTables) . " live database tables have CREATE TABLE definitions in the dump!\n";
} else {
    echo "WARNING: Missing tables: " . implode(', ', $missingTables) . "\n";
}

// Check Order #32 in dump
if (strpos($content, "RX6ACE242D8221B13") !== false) {
    echo "Pending Order #32 (RX6ACE242D8221B13) is VERIFIED PRESENT in the dump!\n";
} else {
    echo "ERROR: Order #32 not found in dump!\n";
}

// Check order columns in CREATE TABLE `orders`
if (preg_match('/CREATE TABLE `orders` \((.*?)\) ENGINE=/s', $content, $mOrder)) {
    echo "CREATE TABLE `orders` extracted successfully.\n";
    $hasTargetCol = (strpos($mOrder[1], '`normalized_payment_trx`') !== false);
    $hasTargetIdx = (strpos($mOrder[1], '`uq_orders_normalized_payment_trx`') !== false);
    echo "Contains normalized_payment_trx: " . ($hasTargetCol ? "YES" : "NO (Clean baseline preserved)") . "\n";
    echo "Contains uq_orders_normalized_payment_trx: " . ($hasTargetIdx ? "YES" : "NO (Clean baseline preserved)") . "\n";
}
