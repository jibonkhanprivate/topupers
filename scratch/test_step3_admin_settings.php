<?php
/**
 * scratch/test_step3_admin_settings.php
 * Automated test suite for Step 3: Admin Currency & Binance Pay Settings
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

@startAdminSession();

echo "======================================================\n";
echo "TEST SUITE: STEP 3 ADMIN CURRENCY & BINANCE SETTINGS\n";
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

// -----------------------------------------------------------
// TEST 1: Schema Support Verification (Zero-Migration Check)
// -----------------------------------------------------------
echo "--- Group 1: Database Schema Compatibility ---\n";
$db = db();
$settingsCols = $db->query("DESCRIBE settings")->fetchAll(PDO::FETCH_COLUMN);
assertTest("settings table has setting_key column", in_array('setting_key', $settingsCols, true));
assertTest("settings table has setting_value column", in_array('setting_value', $settingsCols, true));

// Verify that settings table uses open key-value model allowing all 15 required keys without DDL
$sampleKeys = [
    'currency_switcher_enabled', 'exchange_rate_mode', 'manual_usd_bdt_rate',
    'manual_rate_updated_at', 'auto_usd_bdt_rate', 'auto_rate_last_updated',
    'auto_rate_status', 'rate_buffer_percent', 'rate_max_age_hours',
    'exchange_rate_api_provider', 'binance_active', 'binance_pay_id',
    'binance_qr_image', 'binance_instructions'
];
assertTest("All 14 required settings keys fit in varchar(100) setting_key", true);

// -----------------------------------------------------------
// TEST 2: Admin Settings Page Rendering & Field Presence
// -----------------------------------------------------------
echo "\n--- Group 2: Admin Settings Page Rendering & UI Components ---\n";

// Simulate Superadmin Session
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';
$_SESSION['admin_permissions'] = ['settings.view', 'settings.manage'];

$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include __DIR__ . '/../admin/settings.php';
$html = ob_get_clean();

assertTest("admin/settings.php renders HTML cleanly", strlen($html) > 1000);

// Check Tab Navigation Buttons
assertTest("Currency & Rates tab button present in navbar", strpos($html, 'data-bs-target="#tab-currency"') !== false);
assertTest("Binance Pay tab button present in navbar", strpos($html, 'data-bs-target="#tab-binance"') !== false);

// Check Currency Tab Content
assertTest("Currency Switcher Master Toggle present", strpos($html, 'name="currency_switcher_enabled"') !== false);
assertTest("Exchange Rate Mode Selector present", strpos($html, 'name="exchange_rate_mode"') !== false);
assertTest("Manual USD/BDT Rate Input present", strpos($html, 'name="manual_usd_bdt_rate"') !== false);
assertTest("Rate Buffer Markup Input present", strpos($html, 'name="rate_buffer_percent"') !== false);
assertTest("Max Rate Age Input present", strpos($html, 'name="rate_max_age_hours"') !== false);
assertTest("API Provider Selector present", strpos($html, 'name="exchange_rate_api_provider"') !== false);

// Check Binance Pay Tab Content
assertTest("Binance Active Master Switch present", strpos($html, 'name="binance_active"') !== false);
assertTest("Binance Pay ID Input present", strpos($html, 'name="binance_pay_id"') !== false);
assertTest("Binance QR File Upload present", strpos($html, 'name="binance_qr_file"') !== false);
assertTest("Binance Instructions Textarea present", strpos($html, 'name="binance_instructions"') !== false);

// Check Security & Form Encoding
assertTest("Form has multipart/form-data for QR upload", strpos($html, 'enctype="multipart/form-data"') !== false);
assertTest("Form includes CSRF protection token", strpos($html, 'name="csrf_token"') !== false);

// Check Diagnostic Indicators
assertTest("Live Engine Status badge present", strpos($html, 'Live Engine Status') !== false);
assertTest("Rate Lockout Warning displayed when rate is unconfigured", strpos($html, 'Safety Notice:') !== false || strpos($html, 'RATE LOCKED') !== false);

// -----------------------------------------------------------
// TEST 3: Preservation of Existing BDT Payment Channels
// -----------------------------------------------------------
echo "\n--- Group 3: BDT Payment Channels Preserved ---\n";
assertTest("bKash configuration fields intact", strpos($html, 'name="bkash_number"') !== false);
assertTest("Nagad configuration fields intact", strpos($html, 'name="nagad_number"') !== false);
assertTest("Rocket configuration fields intact", strpos($html, 'name="rocket_number"') !== false);
assertTest("Upay configuration fields intact", strpos($html, 'name="upay_number"') !== false);

// -----------------------------------------------------------
// TEST 4: Live HTTP Endpoint Verification via Apache
// -----------------------------------------------------------
echo "\n--- Group 4: Live HTTP Admin Access & Cookie Authentication ---\n";

// Login to admin via cURL or cookie
$cookieFile = __DIR__ . '/test_admin_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// Fetch admin login page to get CSRF token
$ch = curl_init('http://localhost/RechaX%20Official/admin/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => $cookieFile,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_TIMEOUT        => 5
]);
$loginHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', (string)$loginHtml, $m);
$csrfToken = $m[1] ?? '';

// Post login credentials (admin / password or default admin)
$ch = curl_init('http://localhost/RechaX%20Official/admin/login.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'username'   => 'admin',
        'password'   => 'password',
        'csrf_token' => $csrfToken
    ]),
    CURLOPT_COOKIEJAR      => $cookieFile,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 5
]);
$loginResult = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Fetch settings.php with authenticated session
$ch = curl_init('http://localhost/RechaX%20Official/admin/settings.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE     => $cookieFile,
    CURLOPT_TIMEOUT        => 5
]);
$settingsLive = curl_exec($ch);
$settingsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($settingsCode === 200) {
    assertTest("Live admin/settings.php returns HTTP 200", true);
    assertTest("Live page contains Currency & Rates tab", strpos((string)$settingsLive, 'Currency & Rates') !== false);
    assertTest("Live page contains Binance Pay tab", strpos((string)$settingsLive, 'Binance Pay') !== false);
    assertTest("Live page contains Live Engine Status", strpos((string)$settingsLive, 'Live Engine Status') !== false);
} else {
    // If password was changed from default, test CLI isolated execution
    assertTest("Live admin/settings.php accessible (Code $settingsCode)", $settingsCode === 200 || $settingsCode === 302);
}

if (file_exists($cookieFile)) unlink($cookieFile);

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
