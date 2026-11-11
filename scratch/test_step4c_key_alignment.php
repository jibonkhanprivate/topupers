<?php
/**
 * scratch/test_step4c_key_alignment.php
 * Automated verification of Step 4C settings-key alignment, runner safety guard, and DB integrity.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';
require_once __DIR__ . '/../includes/currency.php';

echo "======================================================\n";
echo "TEST SUITE: STEP 4C SETTINGS-KEY ALIGNMENT & SAFETY GUARDS\n";
echo "======================================================\n\n";

$passes = 0;
$fails = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passes, $fails;
    if ($condition) {
        echo "  [PASS] $desc\n";
        $passes++;
    } else {
        echo "  [FAIL] $desc: $detail\n";
        $fails++;
    }
}

$db = db();

// -----------------------------------------------------------
// Group 1: Migration Runner Canonical Settings Alignment
// -----------------------------------------------------------
echo "--- Group 1: Migration Runner Canonical Settings List ---\n";
$canonical = Gate2BMigrationRunner::CANONICAL_SETTINGS_18;
assertTest("CANONICAL_SETTINGS_18 has exactly 18 keys", count($canonical) === 18, "Count is " . count($canonical));
assertTest("Canonical key 'exchange_rate_api_provider' is present", array_key_exists('exchange_rate_api_provider', $canonical));
assertTest("Canonical key 'binance_instructions' is present", array_key_exists('binance_instructions', $canonical));
assertTest("Deprecated key 'auto_rate_provider' is absent", !array_key_exists('auto_rate_provider', $canonical));
assertTest("Deprecated key 'binance_instruction' is absent", !array_key_exists('binance_instruction', $canonical));
assertTest("Distinct key 'auto_rate_api_url' is preserved", array_key_exists('auto_rate_api_url', $canonical));

// Safe defaults
assertTest("currency_switcher_enabled default is '0'", $canonical['currency_switcher_enabled'] === '0');
assertTest("binance_active default is '0'", $canonical['binance_active'] === '0');
assertTest("manual_usd_bdt_rate default is '0.0000'", $canonical['manual_usd_bdt_rate'] === '0.0000');
assertTest("exchange_rate_mode default is 'manual'", $canonical['exchange_rate_mode'] === 'manual');
assertTest("exchange_rate_api_provider default is 'exchangerate-api'", $canonical['exchange_rate_api_provider'] === 'exchangerate-api');

// -----------------------------------------------------------
// Group 2: includes/currency.php Alignment
// -----------------------------------------------------------
echo "\n--- Group 2: includes/currency.php Key Alignment & Safety ---\n";
$cfg = getExchangeRateConfig();
assertTest("getExchangeRateConfig exposes binance_instructions", array_key_exists('binance_instructions', $cfg));
assertTest("getExchangeRateConfig exposes api_provider", array_key_exists('api_provider', $cfg));
assertTest("getExchangeRateConfig exposes auto_api_url", array_key_exists('auto_api_url', $cfg));
assertTest("getBinanceInstructions returns non-empty string", !empty(getBinanceInstructions()));
assertTest("getBinanceInstructions contains Binance Pay instructions", strpos(getBinanceInstructions(), 'Binance Pay') !== false);

$usdGate = isUsdCheckoutAllowed();
assertTest("USD checkout remains locked under default settings", $usdGate['allowed'] === false);

// -----------------------------------------------------------
// Group 3: admin/settings.php Alignment
// -----------------------------------------------------------
echo "\n--- Group 3: admin/settings.php Template Alignment ---\n";
$adminContent = file_get_contents(__DIR__ . '/../admin/settings.php');
assertTest("admin/settings.php uses exchange_rate_api_provider in \$keys", strpos($adminContent, "'exchange_rate_api_provider'") !== false);
assertTest("admin/settings.php uses binance_instructions in \$keys", strpos($adminContent, "'binance_instructions'") !== false);
assertTest("admin/settings.php renders select with name='exchange_rate_api_provider'", strpos($adminContent, 'name="exchange_rate_api_provider"') !== false);
assertTest("admin/settings.php renders textarea with name='binance_instructions'", strpos($adminContent, 'name="binance_instructions"') !== false);
assertTest("admin/settings.php handles fallback when binance_instructions is empty in DB", strpos($adminContent, "!empty(\$rows['binance_instructions'])") !== false);

// -----------------------------------------------------------
// Group 4: Migration Runner Dry-Run Execution & Safety Refusal
// -----------------------------------------------------------
echo "\n--- Group 4: Migration Runner Dry-Run & Safety Guard ---\n";
// Dry-run mode
$runnerDry = new Gate2BMigrationRunner($db, false);
ob_start();
$runnerDry->execute();
$dryOutput = ob_get_clean();
assertTest("Dry-run execute succeeds without exception", true);
assertTest("Dry-run output confirms PREFLIGHT / DRY-RUN MODE", strpos($dryOutput, "RUNNING IN PREFLIGHT / DRY-RUN MODE") !== false);
assertTest("Dry-run output confirms NO LIVE DATABASE WRITES", strpos($dryOutput, "NO LIVE DATABASE WRITES WILL BE EXECUTED") !== false);

// Live mode execution refusal check
$runnerLive = new Gate2BMigrationRunner($db, true);
$refusalCaught = false;
try {
    $runnerLive->execute();
} catch (LogicException $e) {
    if (strpos($e->getMessage(), "SAFETY ENFORCEMENT REFUSAL") !== false) {
        $refusalCaught = true;
    }
}
assertTest("Migration runner actively refuses live execution via safety guard", $refusalCaught);

// -----------------------------------------------------------
// Group 5: Live Database Invariant & Zero-Modification Verification
// -----------------------------------------------------------
echo "\n--- Group 5: Live Database Zero-Modification Invariant ---\n";
$settingsCount = (int)$db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
$ordersCount = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$paymentsCount = (int)$db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$pmCount = (int)$db->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
$cronCount = (int)$db->query("SELECT COUNT(*) FROM cron_jobs")->fetchColumn();

assertTest("Live settings table count remains unchanged (61 rows)", $settingsCount === 61, "Got $settingsCount");
assertTest("Live orders table count remains 0", $ordersCount === 0, "Got $ordersCount");
assertTest("Live payments table count remains 0", $paymentsCount === 0, "Got $paymentsCount");
assertTest("Live payment_methods table count remains 4 (no binance inserted)", $pmCount === 4, "Got $pmCount");
assertTest("Live cron_jobs table count remains 5 (no exchange_rate_sync inserted)", $cronCount === 5, "Got $cronCount");

// Verify orders.payment_method is still baseline enum
$orderCols = $db->query("
    SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method'
")->fetch(PDO::FETCH_ASSOC);

assertTest("orders.payment_method is still baseline enum", ($orderCols['COLUMN_TYPE'] ?? '') === "enum('bkash','nagad','rocket','wallet','stripe')");

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
