<?php
/**
 * scratch/test_phase_b2_fixes.php
 * Targeted Verification Suite for Phase B.2 Minimal Safety Fixes
 * 1. Currency State Synchronization (getActiveCurrency() reset & persistence)
 * 2. Header and AJAX currency agreement with formatted prices
 * 3. JavaScript and PHP Round Half-Up Mathematical Matching across boundary cases
 * 4. BDT Checkout Behavior Integrity
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

@startUserSession();

$total = 0;
$passed = 0;
$failed = 0;

function assertCheck(string $desc, bool $condition, string $detail = ''): void {
    global $total, $passed, $failed;
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$desc}" . ($detail ? " -- Detail: {$detail}" : "") . "\n";
    }
}

echo "============================================================\n";
echo "TopUpers Gate 2B — Phase B.2 Targeted Safety Fixes Verification\n";
echo "============================================================\n\n";

// -----------------------------------------------------------
// TEST GROUP 1: Currency State Synchronization
// -----------------------------------------------------------
echo "--- Group 1: Currency State Synchronization ---\n";

// 1.1 Disabled/invalid USD session resets to BDT
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']); // Ensure live unconfigured state
$_SESSION['currency'] = 'USD';
$retrievedCurrency = getActiveCurrency();
assertCheck("Disabled/unconfigured USD session resets to BDT", $retrievedCurrency === 'BDT', "Got '{$retrievedCurrency}'");
assertCheck("Session currency was updated to BDT in \$_SESSION", ($_SESSION['currency'] ?? '') === 'BDT');

// 1.2 Non-existent/invalid currency code resets to BDT
$_SESSION['currency'] = 'XYZ';
$invalidCodeResult = getActiveCurrency();
assertCheck("Arbitrary invalid currency 'XYZ' resets to BDT", $invalidCodeResult === 'BDT');
assertCheck("Session currency was updated to BDT for invalid code", ($_SESSION['currency'] ?? '') === 'BDT');

// 1.3 Valid USD session remains USD when switcher is enabled and rate is positive
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'switcher_enabled' => true,
    'manual_rate'      => 120.0000,
    'mode'             => 'manual',
    'buffer_percent'   => 5.00,
];
$_SESSION['currency'] = 'USD';
$validUsdResult = getActiveCurrency();
assertCheck("Valid USD session remains USD when switcher enabled & rate valid", $validUsdResult === 'USD', "Got '{$validUsdResult}'");
assertCheck("Session currency remains 'USD' in \$_SESSION", ($_SESSION['currency'] ?? '') === 'USD');

// 1.4 Stale auto-rate without manual fallback resets USD to BDT
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'switcher_enabled'  => true,
    'mode'              => 'auto',
    'auto_rate'         => 120.0,
    'auto_last_updated' => date('Y-m-d H:i:s', time() - 30 * 3600), // 30 hours old (> 24h max)
    'auto_status'       => 'success',
    'manual_rate'       => 0.0, // No manual fallback
    'max_age_hours'     => 24,
];
$_SESSION['currency'] = 'USD';
$staleUsdResult = getActiveCurrency();
assertCheck("Stale auto rate without fallback resets USD session to BDT", $staleUsdResult === 'BDT', "Got '{$staleUsdResult}'");
assertCheck("Session currency was safely reset to BDT upon stale rate", ($_SESSION['currency'] ?? '') === 'BDT');

// Restore live unconfigured state
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']);
$_SESSION['currency'] = 'BDT';

// -----------------------------------------------------------
// TEST GROUP 2: Header & AJAX Agreement with Formatted Price
// -----------------------------------------------------------
echo "\n--- Group 2: Header & AJAX Agreement with Formatted Price ---\n";

// 2.1 In live unconfigured state:
$_SESSION['currency'] = 'USD'; // Attempt to simulate a stale USD session
$activeCurr = getActiveCurrency(); // Triggers sync
$activeSym  = getCurrencySymbol();
$formatted1000 = formatPriceByCurrency(1000.0);

assertCheck("Active currency is BDT in unconfigured state", $activeCurr === 'BDT');
assertCheck("Active currency symbol is ৳", $activeSym === '৳');
assertCheck("Formatted price is in BDT ('৳ 1,000')", $formatted1000 === '৳ 1,000');

// Header rendering check
ob_start();
include __DIR__ . '/../includes/header.php';
$headerHtml = ob_get_clean();

$hasBdtActive = (strpos($headerHtml, 'data-currency="BDT" aria-pressed="true"') !== false)
             || (strpos($headerHtml, 'rx-currency-btn active" data-currency="BDT"') !== false);
$hasUsdInactive = (strpos($headerHtml, 'data-currency="USD" aria-pressed="false"') !== false)
               || (strpos($headerHtml, 'rx-currency-btn " data-currency="USD"') !== false);

assertCheck("Header switcher renders BDT as active", $hasBdtActive);
assertCheck("Header switcher renders USD as inactive", $hasUsdInactive);

// AJAX search check via helper
$phpBin = 'C:\\xampp\\php\\php.exe';
$helper = __DIR__ . '/test_helper_ajax.php';
$searchOut = shell_exec("{$phpBin} \"{$helper}\" search diamond");
$searchData = json_decode((string)$searchOut, true);
assertCheck("AJAX search returned valid response", !empty($searchData['success']));
if (!empty($searchData['products'])) {
    $firstProd = $searchData['products'][0];
    assertCheck("AJAX product currency matches getActiveCurrency() ('BDT')", $firstProd['currency'] === 'BDT');
    assertCheck("AJAX product formatted_price uses active BDT symbol (৳)", strpos($firstProd['formatted_price'], '৳') !== false);
}

// -----------------------------------------------------------
// TEST GROUP 3: JavaScript vs PHP Rounding Half-Up Consistency
// -----------------------------------------------------------
echo "\n--- Group 3: JavaScript vs PHP Rounding Half-Up Consistency ---\n";

// Array of half-cent boundary edge cases and representative financial values
$boundaryTestValues = [
    1.005,
    1.004,
    1.015,
    1.025,
    1.035,
    1.045,
    1.055,
    1.065,
    1.075,
    1.085,
    1.095,
    0.005,
    0.004,
    1.255,
    2.505,
    3.755,
    8.750,
    10.500,
    9.669387755, // 115 BDT at 122.50 rate with 3% buffer
    1.575,       // 180 BDT at 120.00 rate with 5% buffer
    0.0875,      // 10 BDT at 120.00 rate with 5% buffer
    0.4375,      // 50 BDT at 120.00 rate with 5% buffer
];

// Execute JS rounding script using cscript
$jsTestRunner = __DIR__ . '/test_rounding_runner.js';
$jsCode = "// test_rounding_runner.js\n"
        . "function roundHalfUp(num, decimals) {\n"
        . "  if (!decimals) decimals = 2;\n"
        . "  var absVal = Math.abs(num);\n"
        . "  var shifted = Number(absVal + 'e' + decimals);\n"
        . "  var rounded = Math.round(shifted);\n"
        . "  var result = Number(rounded + 'e-' + decimals);\n"
        . "  return (num < 0 ? -1 : 1) * result;\n"
        . "}\n"
        . "var values = " . json_encode($boundaryTestValues) . ";\n"
        . "var results = [];\n"
        . "for (var i = 0; i < values.length; i++) {\n"
        . "  results.push(roundHalfUp(values[i], 2).toFixed(2));\n"
        . "}\n"
        . "WScript.Echo(results.join(','));\n";

file_put_contents($jsTestRunner, $jsCode);
$jsOutput = trim((string)shell_exec("cscript //nologo \"{$jsTestRunner}\""));
$jsResults = explode(',', $jsOutput);
@unlink($jsTestRunner);

$roundingMismatches = 0;
foreach ($boundaryTestValues as $idx => $val) {
    $phpExpected = number_format(round($val, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    $jsGot = $jsResults[$idx] ?? '';
    if ($phpExpected !== $jsGot) {
        $roundingMismatches++;
        echo "  [MISMATCH] Value: {$val} | PHP: {$phpExpected} | JS: {$jsGot}\n";
    }
}

assertCheck("100% of half-cent boundary and financial values match between PHP and JS (" . count($boundaryTestValues) . " cases)", $roundingMismatches === 0);
assertCheck("Specific half-cent test '1.005' rounds to '1.01' in both PHP and JS", ($jsResults[0] ?? '') === '1.01' && number_format(round(1.005, 2, PHP_ROUND_HALF_UP), 2) === '1.01');
assertCheck("Specific half-cent test '1.004' rounds to '1.00' in both PHP and JS", ($jsResults[1] ?? '') === '1.00' && number_format(round(1.004, 2, PHP_ROUND_HALF_UP), 2) === '1.00');

// -----------------------------------------------------------
// TEST GROUP 4: BDT Checkout Behavior Preservation
// -----------------------------------------------------------
echo "\n--- Group 4: BDT Checkout Behavior Preservation ---\n";
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']);
$_SESSION['currency'] = 'BDT';

assertCheck("formatPriceByCurrency(180, 'BDT') produces '৳ 180'", formatPriceByCurrency(180.0, 'BDT') === '৳ 180');
assertCheck("formatPriceByCurrency(200, 'BDT') produces '৳ 200'", formatPriceByCurrency(200.0, 'BDT') === '৳ 200');

$packageHtml = renderModernPackageCard([
    'id'             => 101,
    'name'           => 'Weekly Pass',
    'amount'         => 180.00,
    'original_price' => 200.00,
    'category'       => 'pass',
    'badge_text'     => 'POPULAR',
    'image'          => 'assets/img/pass.png',
], false);

assertCheck("Package card displays BDT price", strpos($packageHtml, '৳ 180') !== false);
assertCheck("Package card data-currency is BDT", strpos($packageHtml, 'data-currency="BDT"') !== false);
assertCheck("Package card data-price is canonical 180", strpos($packageHtml, 'data-price="180"') !== false);

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n============================================================\n";
echo "Phase B.2 Summary: Total: {$total} | Passed: {$passed} | Failed: {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
