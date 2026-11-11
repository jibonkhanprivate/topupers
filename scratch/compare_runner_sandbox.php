<?php
/**
 * scratch/compare_runner_sandbox.php
 * Programmatically compare constants and DDL in runner vs sandbox validation script.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

$sandboxScript = "C:/Users/TalhaX/.gemini/antigravity-ide/brain/4f6b5f1c-214b-490a-8405-2411942abe16/scratch/run_sandbox_validation.php";
$sandboxContent = file_get_contents($sandboxScript);

echo "=== COMPARISON: RUNNER DEFINITIONS VS SANDBOX VALIDATION EXECUTION ===\n\n";

// 1. Compare Orders Columns
echo "1. ORDERS COLUMNS:\n";
$runnerOrdersCols = Gate2BMigrationRunner::CANONICAL_ORDERS_COLUMNS;
echo "Runner defines " . count($runnerOrdersCols) . " columns for orders:\n";
foreach ($runnerOrdersCols as $c) {
    echo "  - {$c['name']} ({$c['type']}, Nullable={$c['nullable']}, Default=" . var_export($c['default'], true) . ", After={$c['after']})\n";
    // Check if sandbox executed this exact DDL
    $pattern = "ALTER TABLE orders ADD COLUMN {$c['name']} ";
    if (strpos($sandboxContent, $pattern) !== false) {
        echo "    [MATCH IN SANDBOX]\n";
    } else {
        echo "    [WARNING: NOT FOUND VERBATIM IN SANDBOX]\n";
    }
}

// 2. Compare Payments Columns
echo "\n2. PAYMENTS COLUMNS:\n";
$runnerPaymentsCols = Gate2BMigrationRunner::CANONICAL_PAYMENTS_COLUMNS;
echo "Runner defines " . count($runnerPaymentsCols) . " columns for payments:\n";
foreach ($runnerPaymentsCols as $c) {
    echo "  - {$c['name']} ({$c['type']}, Nullable={$c['nullable']}, Default=" . var_export($c['default'], true) . ", After={$c['after']})\n";
    $pattern = "ALTER TABLE payments ADD COLUMN {$c['name']} ";
    if (strpos($sandboxContent, $pattern) !== false) {
        echo "    [MATCH IN SANDBOX]\n";
    } else {
        echo "    [WARNING: NOT FOUND VERBATIM IN SANDBOX]\n";
    }
}

// 3. Compare orders.payment_method
echo "\n3. ORDERS.PAYMENT_METHOD:\n";
if (strpos($sandboxContent, "ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(50) NULL DEFAULT 'bkash'") !== false) {
    echo "  [MATCH IN SANDBOX]: orders.payment_method modified to VARCHAR(50) NULL DEFAULT 'bkash'\n";
} else {
    echo "  [WARNING: NOT FOUND IN SANDBOX]\n";
}

// 4. Compare Settings
echo "\n4. SETTINGS KEYS:\n";
$runnerSettings = Gate2BMigrationRunner::CANONICAL_SETTINGS_18;
echo "Runner defines " . count($runnerSettings) . " settings keys:\n";
foreach ($runnerSettings as $k => $v) {
    echo "  - $k => " . var_export($v, true) . "\n";
}
if (strpos($sandboxContent, "Gate2BMigrationRunner::CANONICAL_SETTINGS_18") !== false) {
    echo "  [MATCH IN SANDBOX]: Sandbox iterated over Gate2BMigrationRunner::CANONICAL_SETTINGS_18 directly.\n";
}

// 5. Compare Binance DML
echo "\n5. AUXILIARY BINANCE DML:\n";
if (strpos($sandboxContent, "VALUES ('binance', 'Binance Pay', '', '', 0, 5)") !== false) {
    echo "  [MATCH IN SANDBOX]: Binance row inserted with ('binance', 'Binance Pay', '', '', 0, 5)\n";
} else {
    echo "  [WARNING: NOT FOUND IN SANDBOX]\n";
}

// 6. Compare Cron DML
echo "\n6. AUXILIARY CRON DML:\n";
if (strpos($sandboxContent, "VALUES ('exchange_rate_sync', 'Exchange Rate Daily & Auto Sync', '0 */4 * * *', NULL, 'success', NULL, 0)") !== false) {
    echo "  [MATCH IN SANDBOX]: Cron row inserted with ('exchange_rate_sync', 'Exchange Rate Daily & Auto Sync', '0 */4 * * *', NULL, 'success', NULL, 0)\n";
} else {
    echo "  [WARNING: NOT FOUND IN SANDBOX]\n";
}
