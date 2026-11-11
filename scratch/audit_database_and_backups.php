<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/write_freeze.php';

echo str_repeat("=", 80) . "\n";
echo "GATE B2 INDEPENDENT AUDIT — READ-ONLY DATABASE & BACKUP VERIFICATION\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Connection (read-only verification)
$pdo = new PDO(
    'mysql:host=localhost;dbname=rechax_official;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$version = $pdo->query('SELECT VERSION()')->fetchColumn();
$currentDb = $pdo->query('SELECT DATABASE()')->fetchColumn();
echo "MariaDB Version: {$version}\n";
echo "Connected Database: {$currentDb}\n\n";

// 2. Orders Table Schema
echo "[ORDERS TABLE AUDIT]\n";
$ordersCols = $pdo->query("
    SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders'
    ORDER BY ORDINAL_POSITION
")->fetchAll();

echo "Total columns on `orders`: " . count($ordersCols) . " (Baseline expected: 43)\n";

$colNames = array_column($ordersCols, 'COLUMN_NAME');
$hasNormCol = in_array('normalized_payment_trx', $colNames, true);
$hasCurrency = in_array('currency', $colNames, true);
$hasBaseAmt = in_array('base_amount_bdt', $colNames, true);
echo "Has normalized_payment_trx column: " . ($hasNormCol ? "YES (MUTATED!)" : "NO (BASELINE INTACT)") . "\n";
echo "Has currency column: " . ($hasCurrency ? "YES (MUTATED!)" : "NO (BASELINE INTACT)") . "\n";
echo "Has base_amount_bdt column: " . ($hasBaseAmt ? "YES (MUTATED!)" : "NO (BASELINE INTACT)") . "\n";

// Indexes on orders
$orderIndexes = $pdo->query("
    SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders'
")->fetchAll();

$indexNames = array_unique(array_column($orderIndexes, 'INDEX_NAME'));
$hasUniqueTrx = in_array('uq_orders_normalized_payment_trx', $indexNames, true);
echo "Has uq_orders_normalized_payment_trx index: " . ($hasUniqueTrx ? "YES (MUTATED!)" : "NO (BASELINE INTACT)") . "\n";
echo "Indexes present: " . implode(', ', $indexNames) . "\n\n";

// 3. Payments Table Schema
echo "[PAYMENTS TABLE AUDIT]\n";
$paymentsCols = $pdo->query("
    SELECT COLUMN_NAME, COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'payments'
    ORDER BY ORDINAL_POSITION
")->fetchAll();
echo "Total columns on `payments`: " . count($paymentsCols) . "\n";
$payColNames = array_column($paymentsCols, 'COLUMN_NAME');
$hasProvTrx = in_array('provider_transfer_id', $payColNames, true);
echo "Has provider_transfer_id: " . ($hasProvTrx ? "YES (MUTATED!)" : "NO (BASELINE INTACT)") . "\n\n";

// 4. Order Records & Order #32 Verification
echo "[ORDER RECORDS AUDIT]\n";
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
echo "Total rows in `orders`: {$totalOrders} (Baseline expected: 1)\n";

$o32 = $pdo->query("SELECT * FROM `orders` WHERE id = 32")->fetch();
if (!$o32) {
    echo "ERROR: Order #32 NOT FOUND!\n";
} else {
    echo "Order #32 Found:\n";
    echo "  order_number:   {$o32['order_number']}\n";
    echo "  user_id:        " . var_export($o32['user_id'], true) . "\n";
    echo "  product_id:     {$o32['product_id']}\n";
    echo "  order_status:   {$o32['order_status']}\n";
    echo "  payment_status: {$o32['payment_status']}\n";
    echo "  payment_method: {$o32['payment_method']}\n";
    echo "  payment_trx:    {$o32['payment_trx']}\n";
    echo "  total_price:    {$o32['total_price']}\n";
    echo "  created_at:     {$o32['created_at']}\n";
}
echo "\n";

// 5. Payment Methods & Settings Audit
echo "[PAYMENT METHODS & SETTINGS AUDIT]\n";
$binanceMethod = $pdo->query("SELECT * FROM `payment_methods` WHERE method_key = 'binance'")->fetch();
echo "Binance payment method present: " . ($binanceMethod ? "YES (MUTATED!)" : "NO (CLEAN BASELINE)") . "\n";

$rateSetting = $pdo->query("SELECT * FROM `settings` WHERE setting_key = 'exchange_rate_usd_bdt'")->fetch();
echo "Exchange rate setting present: " . ($rateSetting ? "YES (MUTATED!)" : "NO (CLEAN BASELINE)") . "\n\n";

// 6. Write Freeze Lock File Status
echo "[WRITE FREEZE LOCK FILE AUDIT]\n";
$prodLock = dirname(__DIR__) . '/config/write_freeze.lock';
$lockExists = file_exists($prodLock);
echo "Production lock file exists: " . ($lockExists ? "YES (ACTIVE!)" : "NO (INACTIVE/UNLOCKED)") . "\n";
echo "rxWriteFreezeActive(): " . (rxWriteFreezeActive() ? "TRUE" : "FALSE") . "\n\n";

// 7. Backup Checksums Audit
echo "[DATABASE BACKUP FILES & SHA-256 AUDIT]\n";
$backupDir = dirname(__DIR__) . '/backups';
$sqlFiles = glob($backupDir . '/*/*.sql');
foreach ($sqlFiles as $sql) {
    $rel = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $sql);
    $size = filesize($sql);
    $sha256 = hash_file('sha256', $sql);
    echo "  File: {$rel}\n";
    echo "  Size: {$size} bytes\n";
    echo "  SHA256: {$sha256}\n\n";
}

// 8. Pre-edit backup verification
echo "[PRE-EDIT FILES SHA-256 VERIFICATION]\n";
$preEditSumFile = $backupDir . '/backup_gate_b2_20261016_080402/SHA256SUMS_pre_edit.txt';
if (file_exists($preEditSumFile)) {
    $lines = file($preEditSumFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', trim($line), 2);
        if (count($parts) === 2) {
            $expectedHash = strtoupper($parts[0]);
            $relPath = $parts[1];
            $realPath = dirname(__DIR__) . '/' . str_replace('\\', '/', $relPath);
            if (file_exists($realPath)) {
                $actualHash = strtoupper(hash_file('sha256', $realPath));
                $match = ($expectedHash === $actualHash);
                echo "  {$relPath}: " . ($match ? "UNMODIFIED (Pre-edit hash)" : "MODIFIED for B2 hardening (Current: $actualHash)") . "\n";
            }
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "AUDIT SCRIPT COMPLETE\n";
echo str_repeat("=", 80) . "\n";
