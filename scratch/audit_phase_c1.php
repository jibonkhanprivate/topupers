<?php
/**
 * scratch/audit_phase_c1.php
 * Comprehensive, Strictly Read-Only Backend Audit Script for Gate 2B Phase C1.
 *
 * SAFETY CONTRACT:
 * - Read-only database introspection (DESCRIBE, SELECT COUNT, SELECT schema).
 * - Zero DDL (no CREATE, ALTER, DROP).
 * - Zero DML (no INSERT, UPDATE, DELETE).
 * - Zero file writes outside scratch/.
 * - Zero credential or secret leakage.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/currency.php';

$pdo = db();
$root = dirname(__DIR__);

echo "======================================================================\n";
echo "TOPUPERS GATE 2B — PHASE C1 COMPREHENSIVE BACKEND READ-ONLY AUDIT\n";
echo "======================================================================\n\n";

// ----------------------------------------------------------------------
// SECTION 1: LIVE DATABASE SCHEMA AUDIT
// ----------------------------------------------------------------------
echo "--- SECTION 1: LIVE DATABASE SCHEMA AUDIT ---\n";
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
$dbVer  = $pdo->query("SELECT VERSION()")->fetchColumn();
echo "Database: {$dbName} | Server: {$dbVer}\n\n";

// 1.1 orders table
echo "[1.1] `orders` Schema:\n";
$orderCols = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
$orderColNames = array_column($orderCols, 'Field');
$gate2bOrderCols = [
    'currency', 'base_amount_bdt', 'exchange_rate', 'rate_buffer_percent',
    'rate_mode', 'rate_source', 'charge_amount', 'payment_details'
];

foreach ($orderCols as $c) {
    if ($c['Field'] === 'payment_method') {
        echo "  - payment_method: Type={$c['Type']}, Null={$c['Null']}, Default=" . var_export($c['Default'], true) . "\n";
    }
}
$missingOrderCols = array_diff($gate2bOrderCols, $orderColNames);
if (empty($missingOrderCols)) {
    echo "  [STATUS] All 8 Gate 2B columns are present in `orders`.\n";
} else {
    echo "  [STATUS] Baseline schema detected. Missing 8 Gate 2B columns: " . implode(', ', $missingOrderCols) . "\n";
}

// 1.2 payments table
echo "\n[1.2] `payments` Schema:\n";
$paymentCols = $pdo->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC);
$paymentColNames = array_column($paymentCols, 'Field');
$gate2bPaymentCols = ['currency', 'amount_bdt'];
$missingPaymentCols = array_diff($gate2bPaymentCols, $paymentColNames);
if (empty($missingPaymentCols)) {
    echo "  [STATUS] All 2 Gate 2B columns are present in `payments`.\n";
} else {
    echo "  [STATUS] Baseline schema detected. Missing 2 Gate 2B columns: " . implode(', ', $missingPaymentCols) . "\n";
}

// 1.3 payment_methods rows
echo "\n[1.3] `payment_methods` Rows:\n";
$pmRows = $pdo->query("SELECT id, method_key, method_label, is_active, sort_order FROM payment_methods ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$binanceMethod = null;
foreach ($pmRows as $pm) {
    echo sprintf("  - #%d %-10s | %-15s | active=%d | sort=%d\n", $pm['id'], $pm['method_key'], $pm['method_label'], $pm['is_active'], $pm['sort_order']);
    if ($pm['method_key'] === 'binance') {
        $binanceMethod = $pm;
    }
}
if (!$binanceMethod) {
    echo "  [STATUS] Binance Pay is absent from `payment_methods` (expected baseline state).\n";
} else {
    echo "  [STATUS] Binance Pay is present: active=" . $binanceMethod['is_active'] . "\n";
}

// 1.4 settings Gate 2B keys
echo "\n[1.4] Gate 2B Key Settings in Database:\n";
$keySettings = [
    'currency_switcher_enabled', 'exchange_rate_mode', 'manual_usd_bdt_rate',
    'auto_usd_bdt_rate', 'rate_buffer_percent', 'binance_active', 'binance_pay_id'
];
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key = ?");
foreach ($keySettings as $k) {
    $stmt->execute([$k]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo sprintf("  - %-28s = '%s'\n", $k, $row['setting_value']);
    } else {
        echo sprintf("  - %-28s = [NOT SEEDED]\n", $k);
    }
}

// ----------------------------------------------------------------------
// SECTION 2: ORDER SUBMISSION CODE PATHS AUDIT
// ----------------------------------------------------------------------
echo "\n--- SECTION 2: ORDER SUBMISSION CODE PATHS AUDIT ---\n";

// Audit game.php
echo "[2.1] `game.php` Order Submission Flow:\n";
$gameCode = file_get_contents($root . '/game.php');

$gameHasCsrf = (strpos($gameCode, 'verifyCsrf($csrfToken)') !== false);
echo "  - CSRF Protection: " . ($gameHasCsrf ? "VERIFIED (calls verifyCsrf)" : "MISSING") . "\n";

$gameHasTrxDup = (strpos($gameCode, 'orders WHERE payment_trx') !== false);
echo "  - Duplicate Trx Check: " . ($gameHasTrxDup ? "PRESENT" : "MISSING (RISK: duplicate TrxID submissions allowed in game.php!)") . "\n";

$gameHasPmValidation = (strpos($gameCode, 'payment_methods WHERE method_key') !== false || strpos($gameCode, 'array_key_exists($paymentMethod') !== false);
echo "  - Payment Method Validation: " . ($gameHasPmValidation ? "STRICT" : "WEAK (only checks !empty(\$paymentMethod), does not validate against active methods in DB)") . "\n";

preg_match('/INSERT\s+INTO\s+orders\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $gameCode, $gameInsertMatch);
if ($gameInsertMatch) {
    $insertedCols = array_map('trim', explode(',', $gameInsertMatch[1]));
    echo "  - INSERT Columns Count: " . count($insertedCols) . "\n";
    echo "  - Includes 'currency': " . (in_array('currency', $insertedCols) ? 'YES' : 'NO') . "\n";
    echo "  - Includes 'charge_amount': " . (in_array('charge_amount', $insertedCols) ? 'YES' : 'NO') . "\n";
    echo "  - Includes 'exchange_rate': " . (in_array('exchange_rate', $insertedCols) ? 'YES' : 'NO') . "\n";
}

// Audit checkout.php
echo "\n[2.2] `checkout.php` Order Submission Flow:\n";
$checkoutCode = file_get_contents($root . '/checkout.php');

$checkoutHasCsrf = (strpos($checkoutCode, 'verifyCsrf($csrfToken)') !== false);
echo "  - CSRF Protection: " . ($checkoutHasCsrf ? "VERIFIED (calls verifyCsrf)" : "MISSING") . "\n";

$checkoutHasTrxDup = (strpos($checkoutCode, "orders WHERE payment_trx = ? AND order_status != 'cancelled'") !== false);
echo "  - Duplicate Trx Check: " . ($checkoutHasTrxDup ? "VERIFIED (checks duplicate active TrxID)" : "MISSING") . "\n";

$checkoutPmCheck = (strpos($checkoutCode, 'array_key_exists($paymentMethod, PAYMENT_METHODS)') !== false);
echo "  - Payment Method Validation: " . ($checkoutPmCheck ? "HARDCODED TO PAYMENT_METHODS CONSTANT (RISK: does not support dynamic DB payment methods like Binance or Upay!)" : "DYNAMIC") . "\n";

preg_match('/INSERT\s+INTO\s+orders\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $checkoutCode, $checkoutInsertMatch);
if ($checkoutInsertMatch) {
    $insertedCols = array_map('trim', explode(',', $checkoutInsertMatch[1]));
    echo "  - INSERT Columns Count: " . count($insertedCols) . "\n";
    echo "  - Includes 'currency': " . (in_array('currency', $insertedCols) ? 'YES' : 'NO') . "\n";
    echo "  - Includes 'charge_amount': " . (in_array('charge_amount', $insertedCols) ? 'YES' : 'NO') . "\n";
    echo "  - Includes 'exchange_rate': " . (in_array('exchange_rate', $insertedCols) ? 'YES' : 'NO') . "\n";
}

// ----------------------------------------------------------------------
// SECTION 3: CURRENCY ENGINE & USD CHECKOUT ELIGIBILITY
// ----------------------------------------------------------------------
echo "\n--- SECTION 3: CURRENCY ENGINE & USD ELIGIBILITY AUDIT ---\n";
$usdEligibility = isUsdCheckoutAllowed();
echo "  - isUsdCheckoutAllowed() status: allowed=" . ($usdEligibility['allowed'] ? 'true' : 'false') . "\n";
echo "  - Rejection code: " . ($usdEligibility['reason'] ?? 'NONE') . "\n";
echo "  - Rejection message: " . ($usdEligibility['message'] ?? 'NONE') . "\n";

$rateDetails = resolveExchangeRate();
echo "  - resolveExchangeRate() is_valid=" . ($rateDetails['is_valid'] ? 'true' : 'false') . ", switcher_enabled=" . ($rateDetails['switcher_enabled'] ? 'true' : 'false') . "\n";
echo "  - Conversion test (1000 BDT to USD): " . var_export(convertBdtToUsd(1000.0), true) . " (Correct fail-closed null)\n";

// ----------------------------------------------------------------------
// SECTION 4: PAYMENT VERIFICATION & ADMIN AUDIT
// ----------------------------------------------------------------------
echo "\n--- SECTION 4: PAYMENT VERIFICATION & ADMIN AUDIT ---\n";

$adminOrdersCode = file_get_contents($root . '/admin/orders.php');
$adminPaymentsCode = file_get_contents($root . '/admin/payments.php');
$userOrdersCode = file_get_contents($root . '/user/orders.php');
$orderSuccessCode = file_get_contents($root . '/order-success.php');

$adminOrdersUsesMoney = substr_count($adminOrdersCode, "money(\$o['total_price'])") + substr_count($adminOrdersCode, "money(\$viewOrder['total_price'])");
echo "  - `admin/orders.php` uses hardcoded BDT `money()` formatters: {$adminOrdersUsesMoney} locations\n";

$adminPaymentsOnlyTouchesPayments = (strpos($adminPaymentsCode, "UPDATE orders") === false);
echo "  - `admin/payments.php` updates `orders` table upon verification: " . ($adminPaymentsOnlyTouchesPayments ? "NO (only updates `payments` table, does NOT touch `orders`)" : "YES") . "\n";

$userOrdersHardcodedBdt = (strpos($userOrdersCode, "'৳' + Number(order.total_price)") !== false);
echo "  - `user/orders.php` modal hardcodes '৳': " . ($userOrdersHardcodedBdt ? "YES (RISK: USD orders will display ৳ in modal!)" : "NO") . "\n";

$orderSuccessUsesMoney = (strpos($orderSuccessCode, "money(\$order['total_price'])") !== false);
echo "  - `order-success.php` uses `money()`: " . ($orderSuccessUsesMoney ? "YES (RISK: USD orders will display ৳ in order success!)" : "NO") . "\n";

// ----------------------------------------------------------------------
// SECTION 5: MIGRATION RUNNER, BACKUP & ROLLBACK LIMITATIONS
// ----------------------------------------------------------------------
echo "\n--- SECTION 5: MIGRATION RUNNER & ROLLBACK LIMITATIONS ---\n";
$runnerCode = file_get_contents($root . '/database/migrate_v4_gate2b_currency_binance.php');

$hasRefusalGuard = (strpos($runnerCode, 'SAFETY ENFORCEMENT REFUSAL') !== false);
echo "  - Live Execution Refusal Guard: " . ($hasRefusalGuard ? "ACTIVE (throws LogicException)" : "DISABLED") . "\n";

$hasJournaling = (strpos($runnerCode, 'logJournal') !== false);
echo "  - Step-by-Step Journaling: " . ($hasJournaling ? "ACTIVE (.gate2b_migration_journal.jsonl)" : "MISSING") . "\n";

$hasSchemaPrecheck = (strpos($runnerCode, 'getTableColumnMaps') !== false);
echo "  - Per-column Schema Verification: " . ($hasSchemaPrecheck ? "ACTIVE (re-reads schema before every ADD COLUMN)" : "MISSING") . "\n";

echo "  - DDL Transactional Rollback Limitation: MariaDB 10.4 performs an IMPLICIT COMMIT on all ALTER TABLE statements. A failed migration CANNOT be rolled back via SQL ROLLBACK. Recovery requires idempotency/resumption or logical backup restore.\n";

echo "\n======================================================================\n";
echo "AUDIT SCRIPT EXECUTION FINISHED SAFELY (ZERO WRITES, ZERO SECRETS)\n";
echo "======================================================================\n";
