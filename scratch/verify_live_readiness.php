<?php
/**
 * scratch/verify_live_readiness.php
 * Strictly read-only verification of live database state for Gate 2B Readiness Review.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

$pdo = db();

echo "====================================================\n";
echo "GATE 2B READINESS REVIEW: LIVE READ-ONLY AUDIT\n";
echo "====================================================\n";

// 1. Version & Identity
$version = $pdo->query("SELECT VERSION()")->fetchColumn();
$currentDb = $pdo->query("SELECT DATABASE()")->fetchColumn();
echo "MariaDB Version: $version\n";
echo "Current Database: $currentDb\n\n";

// 2. orders table audit
$orderCols = $pdo->query("
    SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = '$currentDb' AND TABLE_NAME = 'orders'
    ORDER BY ORDINAL_POSITION ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "orders Total Columns: " . count($orderCols) . "\n";
$orderColNames = array_column($orderCols, 'COLUMN_NAME');

$gate2bOrderCols = ['currency', 'base_amount_bdt', 'exchange_rate', 'rate_buffer_percent', 'rate_mode', 'rate_source', 'charge_amount', 'payment_details'];
$foundGate2bOrderCols = array_intersect($gate2bOrderCols, $orderColNames);
echo "Gate 2B columns currently present in orders: " . (empty($foundGate2bOrderCols) ? "NONE (Unmodified)" : implode(', ', $foundGate2bOrderCols)) . "\n";

// orders.payment_method inspection
$pmCol = null;
foreach ($orderCols as $c) {
    if ($c['COLUMN_NAME'] === 'payment_method') {
        $pmCol = $c;
        break;
    }
}
echo "orders.payment_method definition: Type={$pmCol['COLUMN_TYPE']}, Nullable={$pmCol['IS_NULLABLE']}, Default={$pmCol['COLUMN_DEFAULT']}, Pos={$pmCol['ORDINAL_POSITION']}\n\n";

// 3. payments table audit
$paymentCols = $pdo->query("
    SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = '$currentDb' AND TABLE_NAME = 'payments'
    ORDER BY ORDINAL_POSITION ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "payments Total Columns: " . count($paymentCols) . "\n";
$paymentColNames = array_column($paymentCols, 'COLUMN_NAME');
$gate2bPaymentCols = ['currency', 'amount_bdt'];
$foundGate2bPaymentCols = array_intersect($gate2bPaymentCols, $paymentColNames);
echo "Gate 2B columns currently present in payments: " . (empty($foundGate2bPaymentCols) ? "NONE (Unmodified)" : implode(', ', $foundGate2bPaymentCols)) . "\n\n";

// 4. payment_methods audit
$pms = $pdo->query("SELECT id, method_key, method_label, is_active, sort_order FROM payment_methods ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "payment_methods Total Rows: " . count($pms) . "\n";
foreach ($pms as $pm) {
    echo "  - [{$pm['method_key']}] Label: '{$pm['method_label']}', Active: {$pm['is_active']}, Sort: {$pm['sort_order']}\n";
}
$binanceExists = (bool)$pdo->query("SELECT COUNT(*) FROM payment_methods WHERE method_key = 'binance'")->fetchColumn();
echo "Binance row exists: " . ($binanceExists ? "YES" : "NO (Unmodified)") . "\n\n";

// 5. cron_jobs audit
$crons = $pdo->query("SELECT id, job_key, name, schedule_expression, is_active FROM cron_jobs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "cron_jobs Total Rows: " . count($crons) . "\n";
foreach ($crons as $cj) {
    echo "  - [{$cj['job_key']}] Name: '{$cj['name']}', Schedule: '{$cj['schedule_expression']}', Active: {$cj['is_active']}\n";
}
$cronExists = (bool)$pdo->query("SELECT COUNT(*) FROM cron_jobs WHERE job_key = 'exchange_rate_sync'")->fetchColumn();
echo "exchange_rate_sync cron row exists: " . ($cronExists ? "YES" : "NO (Unmodified)") . "\n\n";

// 6. settings audit
$allSettings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "settings Total Rows: " . count($allSettings) . "\n";

$canonical18 = Gate2BMigrationRunner::CANONICAL_SETTINGS_18;
$found18 = [];
foreach ($canonical18 as $k => $v) {
    if (array_key_exists($k, $allSettings)) {
        $found18[$k] = $allSettings[$k];
    }
}
echo "Canonical 18 settings currently present in settings: " . (empty($found18) ? "NONE (Unmodified - All 18 pending seeding)" : implode(', ', array_keys($found18))) . "\n";

// Check spurious settings
$spuriousKeys = ['manual_exchange_rate'];
foreach ($spuriousKeys as $sk) {
    if (array_key_exists($sk, $allSettings)) {
        echo "WARNING: Spurious setting key found: $sk = {$allSettings[$sk]}\n";
    }
}

// 7. Table counts
echo "\nTable Row Counts across all tables:\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  - $t: $c rows\n";
}
