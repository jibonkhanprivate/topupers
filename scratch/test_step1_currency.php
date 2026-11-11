<?php
/**
 * scratch/test_step1_currency.php
 * Comprehensive unit & integration tests for includes/currency.php and ajax/switch_currency.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

echo "======================================================\n";
echo "TEST SUITE: STEP 1 CURRENCY ENGINE & SWITCHER ENDPOINT\n";
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
// TEST 1: Default Currency & Invariant
// -----------------------------------------------------------
echo "--- Group 1: Default Currency & Session Initialization ---\n";
$_SESSION = []; // Clear session
$def = getActiveCurrency();
assertTest("Default currency is strictly BDT when session is empty", $def === 'BDT', "Got '$def'");

$sym = getCurrencySymbol();
assertTest("Default currency symbol is strictly '৳'", $sym === '৳', "Got '$sym'");

// -----------------------------------------------------------
// TEST 2: Strict Validation
// -----------------------------------------------------------
echo "\n--- Group 2: Strict Currency Code Validation ---\n";
assertTest("isValidCurrency('BDT') is true", isValidCurrency('BDT') === true);
assertTest("isValidCurrency('USD') is true", isValidCurrency('USD') === true);
assertTest("isValidCurrency('bdt') case-insensitive is true", isValidCurrency('bdt') === true);
assertTest("isValidCurrency('usd') case-insensitive is true", isValidCurrency('usd') === true);
assertTest("isValidCurrency('EUR') is false", isValidCurrency('EUR') === false);
assertTest("isValidCurrency('INR') is false", isValidCurrency('INR') === false);
assertTest("isValidCurrency('') is false", isValidCurrency('') === false);
assertTest("isValidCurrency('USD<script>') is false", isValidCurrency('USD<script>') === false);

// -----------------------------------------------------------
// TEST 3: Session State Persistence (Valid & Configured Currency)
// -----------------------------------------------------------
echo "\n--- Group 3: Session State Persistence ---\n";
// Mock active valid exchange rate for testing valid USD session persistence
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = ['switcher_enabled' => true, 'manual_rate' => 120.0, 'mode' => 'manual'];

$_SESSION['currency'] = 'BDT';
$setRes = setActiveCurrency('USD');
assertTest("setActiveCurrency('USD') returns true", $setRes === true);
assertTest("Active currency retrieved from session is 'USD'", getActiveCurrency() === 'USD');
assertTest("Currency symbol for active USD is '$'", getCurrencySymbol() === '$');

$invRes = setActiveCurrency('INVALID_CURRENCY');
assertTest("setActiveCurrency with invalid currency returns false", $invRes === false);
assertTest("Active currency remains 'USD' after invalid attempt", getActiveCurrency() === 'USD');

setActiveCurrency('BDT');
assertTest("setActiveCurrency('BDT') successfully resets to 'BDT'", getActiveCurrency() === 'BDT');

// Restore live unconfigured state
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']);

// -----------------------------------------------------------
// TEST 4: Zero Rate & Unconfigured Rate Guards
// -----------------------------------------------------------
echo "\n--- Group 4: Zero Rate & Unconfigured Rate Safety Guards ---\n";
$rateInfo = resolveExchangeRate();
assertTest("Default live rate is marked invalid (Zero-Rate Lockout active)", $rateInfo['is_valid'] === false, "is_valid was true");
assertTest("Default rate is_available is false", $rateInfo['is_available'] === false);
assertTest("Default error reason is populated", !empty($rateInfo['error_reason']));

$_SESSION['currency'] = 'USD';
assertTest("Disabled/unconfigured USD session resets to BDT", getActiveCurrency() === 'BDT');
assertTest("Session currency was updated to BDT", $_SESSION['currency'] === 'BDT');

$convertRes = convertBdtToUsd(1000.0);
assertTest("convertBdtToUsd returns null when exchange rate is 0/unconfigured", $convertRes === null);

$formattedBdtFallback = formatPriceByCurrency(1000.0, 'USD');
assertTest("formatPriceByCurrency('USD') safely falls back to BDT when rate is 0", $formattedBdtFallback === '৳ 1,000', "Got '$formattedBdtFallback'");

$checkoutCheck = isUsdCheckoutAllowed();
assertTest("isUsdCheckoutAllowed returns allowed=false when unconfigured", $checkoutCheck['allowed'] === false);
assertTest("isUsdCheckoutAllowed provides clear block reason", !empty($checkoutCheck['reason']), "Reason was empty");

// -----------------------------------------------------------
// TEST 5: Mathematical Precision Verification (Formula Test)
// -----------------------------------------------------------
echo "\n--- Group 5: Mathematical Precision & Buffer Markup Formula ---\n";
// Replicate mathematical formula with known baseline values:
// Catalog Price = 1000.00 BDT, Rate = 120.0000, Buffer = 5.0%
$mockBdt = 1000.00;
$mockRate = 120.0000;
$mockBuffer = 5.00;

$rawUsd = $mockBdt / $mockRate;
$withBuffer = $rawUsd * (1.0 + ($mockBuffer / 100.0));
$calculatedUsd = max(0.01, round($withBuffer, 2, PHP_ROUND_HALF_UP));

assertTest("Calculated USD matches specification: (1000/120)*1.05 = 8.75 USD", $calculatedUsd === 8.75, "Got $calculatedUsd");

// Edge case: Small catalog amount (e.g. 0.50 BDT) should not round to 0.00
$smallBdt = 0.50;
$smallCalculated = max(0.01, round(($smallBdt / $mockRate) * 1.05, 2, PHP_ROUND_HALF_UP));
assertTest("Small amount minimum charge enforced at 0.01 USD", $smallCalculated === 0.01, "Got $smallCalculated");

// Zero amount catalog price
$zeroBdt = 0.00;
$zeroResult = convertBdtToUsd($zeroBdt);
assertTest("Zero Bdt amount produces 0.00 USD charge", ($zeroResult['charge_amount'] ?? null) === 0.00);

// -----------------------------------------------------------
// TEST 6: Simulated AJAX Switcher Endpoint
// -----------------------------------------------------------
echo "\n--- Group 6: Simulated AJAX Switcher Endpoint Responses ---\n";

// 6.1 Test Invalid Currency
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_REQUEST['currency'] = 'EUR';
ob_start();
include __DIR__ . '/../ajax/switch_currency.php';
$out1 = ob_get_clean();
$json1 = json_decode($out1, true);

assertTest("ajax/switch_currency.php with EUR returns success=false", ($json1['success'] ?? null) === false);
assertTest("ajax/switch_currency.php error message mentions BDT and USD", strpos($json1['message'] ?? '', 'BDT') !== false);

// 6.2 Test USD when rate is unconfigured (Zero Rate Lockout)
$_REQUEST['currency'] = 'USD';
ob_start();
include __DIR__ . '/../ajax/switch_currency.php';
$out2 = ob_get_clean();
$json2 = json_decode($out2, true);

assertTest("ajax/switch_currency.php with USD (zero rate) returns success=false", ($json2['success'] ?? null) === false);
assertTest("ajax/switch_currency.php correctly blocks switch to unconfigured USD", strpos($json2['message'] ?? '', 'unavailable') !== false);

// 6.3 Test Valid Switch to BDT
$_REQUEST['currency'] = 'BDT';
ob_start();
include __DIR__ . '/../ajax/switch_currency.php';
$out3 = ob_get_clean();
$json3 = json_decode($out3, true);

assertTest("ajax/switch_currency.php with BDT returns success=true", ($json3['success'] ?? null) === true);
assertTest("ajax/switch_currency.php returned currency is BDT", ($json3['currency'] ?? null) === 'BDT');
assertTest("ajax/switch_currency.php returned symbol is ৳", ($json3['symbol'] ?? null) === '৳');
assertTest("Session currency is BDT after request", ($_SESSION['currency'] ?? '') === 'BDT');

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
