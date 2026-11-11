<?php
/**
 * scratch/test_step2_header.php
 * Automated verification of Step 2: BDT/USD Currency Switcher in Header.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

@startUserSession();

echo "======================================================\n";
echo "TEST SUITE: STEP 2 HEADER CURRENCY SWITCHER INTEGRATION\n";
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
// TEST 1: Header Rendering in Isolation
// -----------------------------------------------------------
echo "--- Group 1: Header Rendering & Component Presence ---\n";

$_SESSION = [];
$pageTitle = 'Step 2 Test';
$pageDescription = 'Testing currency switcher in header';

ob_start();
include __DIR__ . '/../includes/header.php';
$headerHtml = ob_get_clean();

assertTest("Header rendered non-empty HTML", strlen($headerHtml) > 500);

// Check that NO Desktop or Mobile Currency Switcher exists
$hasDesktopSwitcher = strpos($headerHtml, 'rx-currency-switcher d-none d-lg-inline-flex') !== false;
assertTest("Desktop currency switcher container absent", !$hasDesktopSwitcher, "Desktop switcher still found");

$hasMobileSwitcher = strpos($headerHtml, 'rx-currency-switcher rx-currency-switcher-mobile') !== false;
assertTest("Mobile currency switcher container absent", !$hasMobileSwitcher, "Mobile switcher still found");

// Check that NO Currency Toggle Buttons exist
$hasBdtBtn = preg_match('/<button[^>]+data-currency="BDT"[^>]*>/is', $headerHtml) === 1;
assertTest("BDT currency toggle button absent", !$hasBdtBtn, "BDT button still found");

$hasUsdBtn = preg_match('/<button[^>]+data-currency="USD"[^>]*>/is', $headerHtml) === 1;
assertTest("USD currency toggle button absent", !$hasUsdBtn, "USD button still found");

// Check Active Currency contract
assertTest("Active currency strictly resolves to BDT", getActiveCurrency() === 'BDT');
assertTest("Currency symbol strictly resolves to ৳", getCurrencySymbol() === '৳');

// -----------------------------------------------------------
// TEST 2: JavaScript & Switcher Handlers Absent
// -----------------------------------------------------------
echo "\n--- Group 2: Client-side Switcher Handler Absence ---\n";
assertTest("switchCurrency JavaScript function absent", strpos($headerHtml, 'function switchCurrency(') === false, "switchCurrency still present");
assertTest("Currency switcher event listener absent", strpos($headerHtml, 'currencyBtns.forEach') === false, "currencyBtns listener still present");

// -----------------------------------------------------------
// TEST 3: Live HTTP Verification against Apache (localhost)
// -----------------------------------------------------------
echo "\n--- Group 3: Live Web Server HTTP Integration ---\n";

$homeUrl = 'http://localhost/RechaX%20Official/index.php';
$ctx = stream_context_create([
    'http' => [
        'timeout' => 5,
        'header'  => "User-Agent: AntigravityTestRunner/1.0\r\n"
    ]
]);

$liveHtml = @file_get_contents($homeUrl, false, $ctx);
if ($liveHtml !== false) {
    assertTest("Live homepage returns HTTP 200", true);
    assertTest("Live homepage has NO desktop currency switcher", strpos($liveHtml, 'rx-currency-switcher d-none d-lg-inline-flex') === false);
    assertTest("Live homepage has NO mobile currency switcher", strpos($liveHtml, 'rx-currency-switcher rx-currency-switcher-mobile') === false);
    assertTest("Live homepage does NOT contain currency switch buttons", strpos($liveHtml, 'data-currency="') === false);
} else {
    assertTest("Live homepage reachable", false, "Could not connect to $homeUrl");
}

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
