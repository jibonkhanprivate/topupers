<?php
/**
 * scratch/test_phase_b_frontend.php
 * Comprehensive Verification Test for Phase B: Frontend Currency Integration & Shared Pricing
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$desc}" . ($detail ? " -- Detail: {$detail}" : "") . "\n";
    }
}

echo "============================================================\n";
echo "TopUpers Gate 2B — Phase B Frontend Currency Verification\n";
echo "============================================================\n\n";

// -----------------------------------------------------------
// TEST GROUP 1: BDT Default & Session State
// -----------------------------------------------------------
echo "--- Group 1: Default Currency & Session State ---\n";
unset($_SESSION['currency']);
assertTest("BDT is strictly the default active currency", getActiveCurrency() === 'BDT');
assertTest("Currency symbol for default active currency is ৳", getCurrencySymbol() === '৳');

setActiveCurrency('BDT');
assertTest("Active currency remains BDT after explicit setActiveCurrency('BDT')", getActiveCurrency() === 'BDT');

// -----------------------------------------------------------
// TEST GROUP 2: formatPriceByCurrency BDT Standard Behavior
// -----------------------------------------------------------
echo "\n--- Group 2: formatPriceByCurrency Standard BDT Behavior ---\n";
assertTest("1000 BDT formatted in BDT produces '৳ 1,000'", formatPriceByCurrency(1000.0, 'BDT') === '৳ 1,000');
assertTest("50.50 BDT formatted in BDT produces '৳ 51'", formatPriceByCurrency(50.50, 'BDT') === '৳ 51');
assertTest("0 BDT formatted in BDT produces '৳ 0'", formatPriceByCurrency(0.0, 'BDT') === '৳ 0');

// -----------------------------------------------------------
// TEST GROUP 3: Fail-Closed Guard for USD (Disabled or Zero Rate)
// -----------------------------------------------------------
echo "\n--- Group 3: Fail-Closed Guard (No False USD Pricing) ---\n";
// Currently in live settings, currency_switcher_enabled is '0' and manual_usd_bdt_rate is '0.0000'
$rateInfo = resolveExchangeRate();
assertTest("Live settings have switcher disabled or rate zero", !$rateInfo['switcher_enabled'] || !$rateInfo['is_valid']);

// When USD is requested while disabled or zero-rate, formatPriceByCurrency MUST fallback to BDT
$fallbackResult = formatPriceByCurrency(1000.0, 'USD');
assertTest("formatPriceByCurrency('USD') safely falls back to BDT '৳ 1,000' when unconfigured/disabled", $fallbackResult === '৳ 1,000', "Got '$fallbackResult'");

// convertBdtToUsd should return null
$nullResult = convertBdtToUsd(1000.0);
assertTest("convertBdtToUsd returns null when rate is unconfigured or zero", $nullResult === null);

// -----------------------------------------------------------
// TEST GROUP 4: Package Card Rendering (renderModernPackageCard)
// -----------------------------------------------------------
echo "\n--- Group 4: Package Card Rendering Currency Awareness ---\n";
$mockPackage = [
    'id'             => 9999,
    'name'           => '86 Diamonds (Test)',
    'amount'         => 180.00,
    'original_price' => 200.00,
    'category'       => 'diamonds',
    'badge_text'     => 'HOT',
    'image'          => 'assets/img/diamond.png',
];

$cardHtmlBdt = renderModernPackageCard($mockPackage, false);
assertTest("Package card contains data-currency attribute", strpos($cardHtmlBdt, 'data-currency="BDT"') !== false);
assertTest("Package card contains canonical data-price='180'", strpos($cardHtmlBdt, 'data-price="180"') !== false);
assertTest("Package card contains canonical data-orig-price='200'", strpos($cardHtmlBdt, 'data-orig-price="200"') !== false);
assertTest("Package card contains formatted selling price '৳ 180'", strpos($cardHtmlBdt, '৳ 180') !== false);
assertTest("Package card contains formatted strike price '৳ 200'", strpos($cardHtmlBdt, '৳ 200') !== false);

// -----------------------------------------------------------
// TEST GROUP 5: AJAX Endpoints Currency Awareness
// -----------------------------------------------------------
echo "\n--- Group 5: AJAX Endpoints Currency Awareness ---\n";

// 5.1 selector_products.php via CLI sub-process
$phpBin = 'C:\\xampp\\php\\php.exe';
$helper = __DIR__ . '/test_helper_ajax.php';
$selectorOutput = shell_exec("{$phpBin} \"{$helper}\" selector 1");
$selectorJson = json_decode((string)$selectorOutput, true);
assertTest("selector_products.php responded with valid JSON", is_array($selectorJson));
if (!empty($selectorJson['products'])) {
    $firstProd = $selectorJson['products'][0];
    assertTest("selector_products.php returns 'formatted_price'", isset($firstProd['formatted_price']));
    assertTest("selector_products.php formatted_price begins with ৳", strpos($firstProd['formatted_price'], '৳') === 0, "Got " . ($firstProd['formatted_price'] ?? 'null'));
    assertTest("selector_products.php returns currency 'BDT'", ($firstProd['currency'] ?? null) === 'BDT');
} else {
    assertTest("selector_products.php handled option lookup safely", isset($selectorJson['success']));
}

// 5.2 search.php via CLI sub-process
$searchOutput = shell_exec("{$phpBin} \"{$helper}\" search diamond");
$searchJson = json_decode((string)$searchOutput, true);

assertTest("search.php returns valid JSON response", is_array($searchJson) && !empty($searchJson['success']));
if (!empty($searchJson['products'])) {
    $firstSearchProd = $searchJson['products'][0];
    assertTest("search.php product contains 'formatted_price'", isset($firstSearchProd['formatted_price']));
    assertTest("search.php product formatted_price uses active currency symbol (৳)", strpos($firstSearchProd['formatted_price'], '৳') !== false);
    assertTest("search.php product contains currency key 'BDT'", ($firstSearchProd['currency'] ?? null) === 'BDT');
}

// -----------------------------------------------------------
// TEST GROUP 6: Database Integrity Check (Preserve DB Prices)
// -----------------------------------------------------------
echo "\n--- Group 6: Database Price Preservation ---\n";
$stmt = db()->query("SELECT COUNT(*) as cnt, MIN(amount) as min_amt, MAX(amount) as max_amt FROM products");
$stats = $stmt->fetch();
assertTest("Products table count > 0", (int)$stats['cnt'] > 0);
assertTest("Product amounts are positive canonical values", (float)$stats['min_amt'] > 0 && (float)$stats['max_amt'] > 0);

// -----------------------------------------------------------
// Summary
// -----------------------------------------------------------
echo "\n============================================================\n";
echo "Phase B Test Summary: Total: {$totalTests} | Passed: {$passedTests} | Failed: {$failedTests}\n";
echo "============================================================\n";

if ($failedTests > 0) {
    exit(1);
}
