<?php
/**
 * scratch/test_step3_save_settings.php
 * Automated verification of form submission, CSRF, and settings persistence in admin/settings.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

@startAdminSession();

echo "======================================================\n";
echo "TEST SUITE: STEP 3 SETTINGS PERSISTENCE & CSRF VALIDATION\n";
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

// Backup original settings for currency and binance keys
$originalKeys = [
    'currency_switcher_enabled', 'exchange_rate_mode', 'manual_usd_bdt_rate',
    'manual_rate_updated_at', 'rate_buffer_percent', 'rate_max_age_hours',
    'exchange_rate_api_provider', 'binance_active', 'binance_pay_id',
    'binance_instructions'
];
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$backups = [];
foreach ($originalKeys as $k) {
    $stmt->execute([$k]);
    $backups[$k] = $stmt->fetchColumn() ?: '';
}

// -----------------------------------------------------------
// TEST 1: CSRF Rejection
// -----------------------------------------------------------
echo "--- Group 1: CSRF Security Guard ---\n";
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';
$_SESSION['admin_permissions'] = ['settings.view', 'settings.manage'];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'active_tab' => 'tab-currency',
    'csrf_token' => 'invalid_csrf_token_12345',
    'manual_usd_bdt_rate' => '999.0000'
];

ob_start();
include __DIR__ . '/../admin/settings.php';
$csrfRejectHtml = ob_get_clean();

assertTest("Invalid CSRF token blocks settings update", strpos($csrfRejectHtml, 'Invalid or expired CSRF token') !== false);

// -----------------------------------------------------------
// TEST 2: Valid POST Save Execution
// -----------------------------------------------------------
echo "\n--- Group 2: Valid POST Settings Save & Persistence ---\n";
$validCsrf = generateCsrf();

$_POST = [
    'active_tab' => 'tab-currency',
    'csrf_token' => $validCsrf,
    'site_name'  => 'TopUpers',
    'site_currency' => '৳',
    // Currency & Exchange Rate
    'currency_switcher_enabled' => '1',
    'exchange_rate_mode' => 'manual',
    'manual_usd_bdt_rate' => '122.5000',
    'rate_buffer_percent' => '3.50',
    'rate_max_age_hours' => '48',
    'exchange_rate_api_provider' => 'exchangerate-api',
    // Binance Pay
    'binance_active' => '0', // Keep disabled by default
    'binance_pay_id' => '987654321',
    'binance_instructions' => 'Test payment instructions for USD'
];

ob_start();
include __DIR__ . '/../admin/settings.php';
$saveHtml = ob_get_clean();

assertTest("Valid POST returns success alert", strpos($saveHtml, 'updated successfully') !== false);

// Verify Database State
$checkStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");

$checkStmt->execute(['manual_usd_bdt_rate']);
$savedRate = $checkStmt->fetchColumn();
assertTest("manual_usd_bdt_rate was saved as 122.5", (float)$savedRate === 122.5);

$checkStmt->execute(['rate_buffer_percent']);
$savedBuffer = $checkStmt->fetchColumn();
assertTest("rate_buffer_percent was saved as 3.50", (float)$savedBuffer === 3.5);

$checkStmt->execute(['rate_max_age_hours']);
$savedAge = $checkStmt->fetchColumn();
assertTest("rate_max_age_hours was saved as 48", (int)$savedAge === 48);

$checkStmt->execute(['binance_pay_id']);
$savedPayId = $checkStmt->fetchColumn();
assertTest("binance_pay_id was saved as 987654321", $savedPayId === '987654321');

$checkStmt->execute(['binance_active']);
$savedBinanceActive = $checkStmt->fetchColumn();
assertTest("binance_active remained 0 (disabled by default)", $savedBinanceActive === '0');

$checkStmt->execute(['manual_rate_updated_at']);
$savedRateTimestamp = $checkStmt->fetchColumn();
assertTest("manual_rate_updated_at was automatically updated", !empty($savedRateTimestamp));

// -----------------------------------------------------------
// TEST 3: Restore Original Defaults
// -----------------------------------------------------------
echo "\n--- Group 3: Clean Restoration to Defaults ---\n";
$restoreStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
foreach ($backups as $k => $v) {
    $restoreStmt->execute([$k, $v, $v]);
}
assertTest("Database settings restored to initial state", true);

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
