<?php
/**
 * scratch/test_complete_currency_payment.php
 *
 * TopUpers — Comprehensive Automatic Currency & Payment Verification Suite
 * Focused, safe verification of:
 * 1. Country detection & trusted Cloudflare proxy validation
 * 2. Canonical product pricing & 3% buffer formula
 * 3. Currency / Payment method isolation (BD: bkash, nagad, rocket, upay | International: binance)
 * 4. Stale/missing rate lockout for USD while BDT continues unaffected
 * 5. Immutable order pricing snapshots & canonical BDT total_price
 * 6. Historical order display fallback
 * 7. Removal of customer-facing currency switcher
 * 8. Migration safety guards verification
 *
 * ZERO live database writes (uses in-memory SQLite for order simulation).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$passed = 0;
$failed = 0;

function it(string $description, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$description}" . ($detail ? " ({$detail})" : '') . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$description}" . ($detail ? " ({$detail})" : '') . "\n";
    }
}

echo "======================================================================\n";
echo "TOPUPERS — COMPLETE AUTOMATIC CURRENCY & PAYMENT VERIFICATION\n";
echo "======================================================================\n\n";

// ======================================================================
// 1. COUNTRY DETECTION & TRUSTED PROXY VALIDATION
// ======================================================================
echo "--- 1. Country Detection & Trusted Cloudflare Proxy ---\n";

// 1A. Local development / default without detection enabled -> Safe 'BD'
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'country_detection_enabled' => 0,
    'trusted_proxies'           => '',
];
unset($_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_CF_IPCOUNTRY']);
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
it("Default fallback returns 'BD' when country detection is disabled", detectCustomerCountry() === 'BD');
it("getActiveCurrency() returns 'BDT' when country is 'BD'", getActiveCurrency() === 'BDT');

// 1B. Client tries to forge CF-IPCountry header directly from untrusted IP
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['country_detection_enabled'] = 1;
$_SERVER['REMOTE_ADDR'] = '203.0.113.195'; // Arbitrary public untrusted IP
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'US'; // Spoofed header
it("Untrusted client IP spoofing CF-IPCountry is rejected and defaults to 'BD'", detectCustomerCountry() === 'BD');

// 1C. Request coming from official Cloudflare IPv4 proxy (e.g. 173.245.48.50 in 173.245.48.0/20)
$_SERVER['REMOTE_ADDR'] = '173.245.48.50';
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
it("Trusted Cloudflare IPv4 proxy allows CF-IPCountry 'US'", detectCustomerCountry() === 'US');
it("International 'US' customer resolves to active currency 'USD'", getActiveCurrency() === 'USD');

// 1D. Request coming from Cloudflare with Bangladesh IP country
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'BD';
it("Cloudflare proxy with CF-IPCountry 'BD' resolves to 'BD'", detectCustomerCountry() === 'BD');
it("Bangladesh customer resolves to active currency 'BDT'", getActiveCurrency() === 'BDT');

// 1E. Request coming from custom configured trusted reverse proxy
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['trusted_proxies'] = '10.0.0.1, 192.168.1.0/24';
$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'GB';
it("Custom trusted proxy subnet accepts CF-IPCountry 'GB'", detectCustomerCountry() === 'GB');

// Cleanup server mock headers
unset($_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_CF_IPCOUNTRY']);
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
echo "\n";

// ======================================================================
// 2. CANONICAL PRODUCT PRICING & 3% BUFFER FORMULA
// ======================================================================
echo "--- 2. Canonical Product Pricing & 3% Buffer Formula ---\n";

// Formula: USD amount = round((final BDT amount / valid BDT-per-USD rate) × 1.03, 2)
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'mode'                      => 'manual',
    'manual_rate'               => 120.0000,
    'buffer_percent'            => 3.00,
    'decimal_precision'         => 2,
    'binance_active'            => true,
    'binance_pay_id'            => '12345678',
    'mock_has_snapshot_columns' => true,
];

// Product price ৳190:
// 190 / 120 = 1.5833333333333333...
// 1.5833333333333333 * 1.03 = 1.6308333333333333...
// round(1.630833..., 2) = 1.63
$conv190 = convertBdtToUsd(190.00);
it("convertBdtToUsd(190.00) produces exactly 1.63 USD with 3% buffer", $conv190 !== null && $conv190['usd_amount'] === 1.63, "Got " . ($conv190['usd_amount'] ?? 'null'));
it("formatPriceByCurrency(190.00, 'USD') produces '$ 1.63'", formatPriceByCurrency(190.00, 'USD') === '$ 1.63');
it("formatPriceByCurrency(190.00, 'BDT') produces '৳ 190'", formatPriceByCurrency(190.00, 'BDT') === '৳ 190');

// Product price ৳1000:
// 1000 / 120 * 1.03 = 8.58333...
$conv1000 = convertBdtToUsd(1000.00);
it("convertBdtToUsd(1000.00) produces exactly 8.58 USD", $conv1000 !== null && $conv1000['usd_amount'] === 8.58, "Got " . ($conv1000['usd_amount'] ?? 'null'));
echo "\n";

// ======================================================================
// 3. PAYMENT METHOD ISOLATION & CURRENCY COMPATIBILITY
// ======================================================================
echo "--- 3. Payment Method Isolation & Currency Compatibility ---\n";

it("isPaymentMethodAllowedForCurrency('bkash', 'BDT') is true", isPaymentMethodAllowedForCurrency('bkash', 'BDT') === true);
it("isPaymentMethodAllowedForCurrency('nagad', 'BDT') is true", isPaymentMethodAllowedForCurrency('nagad', 'BDT') === true);
it("isPaymentMethodAllowedForCurrency('rocket', 'BDT') is true", isPaymentMethodAllowedForCurrency('rocket', 'BDT') === true);
it("isPaymentMethodAllowedForCurrency('upay', 'BDT') is true", isPaymentMethodAllowedForCurrency('upay', 'BDT') === true);
it("isPaymentMethodAllowedForCurrency('binance', 'BDT') is false (Binance rejected for BDT)", isPaymentMethodAllowedForCurrency('binance', 'BDT') === false);

it("isPaymentMethodAllowedForCurrency('binance', 'USD') is true", isPaymentMethodAllowedForCurrency('binance', 'USD') === true);
it("isPaymentMethodAllowedForCurrency('bkash', 'USD') is false (bKash rejected for USD)", isPaymentMethodAllowedForCurrency('bkash', 'USD') === false);
it("isPaymentMethodAllowedForCurrency('nagad', 'USD') is false (Nagad rejected for USD)", isPaymentMethodAllowedForCurrency('nagad', 'USD') === false);
it("isPaymentMethodAllowedForCurrency('rocket', 'USD') is false (Rocket rejected for USD)", isPaymentMethodAllowedForCurrency('rocket', 'USD') === false);
it("isPaymentMethodAllowedForCurrency('upay', 'USD') is false (Upay rejected for USD)", isPaymentMethodAllowedForCurrency('upay', 'USD') === false);
echo "\n";

// ======================================================================
// 4. OFFLINE IN-MEMORY ORDER CREATION & SNAPSHOT VALIDATION
// ======================================================================
echo "--- 4. Offline Order Creation & Snapshot Validation ---\n";

$mockPdo = new PDO('sqlite::memory:');
$mockPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mockPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$mockPdo->exec("
    CREATE TABLE products (
        id INTEGER PRIMARY KEY,
        game_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE coupons (
        id INTEGER PRIMARY KEY,
        code TEXT NOT NULL UNIQUE,
        type TEXT NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        max_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        usage_limit INTEGER NULL,
        used_count INTEGER NOT NULL DEFAULT 0,
        expires_at TEXT NULL,
        is_active INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE payment_methods (
        id INTEGER PRIMARY KEY,
        method_key TEXT NOT NULL UNIQUE,
        method_label TEXT NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        sort_order INTEGER NOT NULL DEFAULT 0,
        account_number TEXT NULL,
        icon TEXT NULL,
        color TEXT NULL,
        instructions TEXT NULL
    );
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        order_number TEXT NOT NULL UNIQUE,
        total_price DECIMAL(10,2) NOT NULL,
        currency TEXT NOT NULL DEFAULT 'BDT',
        base_amount_bdt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
        rate_buffer_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        rate_mode TEXT NOT NULL DEFAULT 'canonical',
        rate_source TEXT NOT NULL DEFAULT 'canonical_bdt',
        charge_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_details TEXT NULL,
        payment_trx TEXT NULL,
        payment_method TEXT NOT NULL,
        order_status TEXT NOT NULL DEFAULT 'pending',
        created_at TEXT NOT NULL
    );
");

// Seed test product & methods
$mockPdo->exec("
    INSERT INTO products (id, game_id, name, amount, is_active) VALUES (1, 10, 'Weekly Diamond Pass', 190.00, 1);
    INSERT INTO coupons (id, code, type, value, is_active) VALUES (1, 'SAVE10', 'percent', 10.00, 1);
    INSERT INTO payment_methods (method_key, method_label, is_active, sort_order) VALUES
        ('bkash', 'bKash', 1, 1),
        ('nagad', 'Nagad', 1, 2),
        ('rocket', 'Rocket', 1, 3),
        ('upay', 'Upay', 1, 4),
        ('binance', 'Binance Pay', 1, 5);
");

// 4A. BDT Order with bKash
$bdtRes = validateAndPrepareOrder($mockPdo, 1, 10, null, 'BDT', 'bkash', 'TRX_BD_001', '01700000000');
it("validateAndPrepareOrder succeeds for BDT bKash order", $bdtRes['valid'] === true);
it("BDT order snapshot currency is 'BDT'", $bdtRes['snapshot']['currency'] === 'BDT');
it("BDT order snapshot base_amount_bdt equals canonical 190.00", (float)$bdtRes['snapshot']['base_amount_bdt'] === 190.00);
it("BDT order snapshot charge_amount equals payable 190.00", (float)$bdtRes['snapshot']['charge_amount'] === 190.00);

// 4B. International USD Order with Binance Pay
$usdRes = validateAndPrepareOrder($mockPdo, 1, 10, null, 'USD', 'binance', 'BINANCE_TRX_001', 'UID_998877');
it("validateAndPrepareOrder succeeds for USD Binance Pay order", $usdRes['valid'] === true);
it("USD order snapshot currency is strictly 'USD'", $usdRes['snapshot']['currency'] === 'USD');
it("USD order preserves canonical BDT amount 190.00 in base_amount_bdt", (float)$usdRes['snapshot']['base_amount_bdt'] === 190.00);
it("USD order snapshot charge_amount is computed 1.63 USD", (float)$usdRes['snapshot']['charge_amount'] === 1.63);
it("USD order snapshot records exchange_rate 120.00", (float)$usdRes['snapshot']['exchange_rate'] === 120.00);
it("USD order snapshot records rate_buffer_percent 3.00%", (float)$usdRes['snapshot']['rate_buffer_percent'] === 3.00);

// 4C. USD Order with coupon discount (10% off ৳190 = ৳171 BDT):
// USD calculated on discounted BDT: 171 / 120 * 1.03 = 1.467825 -> 1.47 USD
$usdCouponRes = validateAndPrepareOrder($mockPdo, 1, 10, 'SAVE10', 'USD', 'binance', 'BINANCE_TRX_002', 'UID_998877');
it("validateAndPrepareOrder succeeds for USD order with valid coupon", $usdCouponRes['valid'] === true);
it("Coupon discount is calculated in BDT first (19.00 BDT)", (float)$usdCouponRes['discount_amount'] === 19.00);
it("Discounted base_amount_bdt is 171.00 BDT", (float)$usdCouponRes['snapshot']['base_amount_bdt'] === 171.00);
it("USD charge_amount is calculated from discounted BDT (1.47 USD)", (float)$usdCouponRes['snapshot']['charge_amount'] === 1.47);

// 4D. Cross-currency boundary rejection
$rejectBdtBinance = validateAndPrepareOrder($mockPdo, 1, 10, null, 'BDT', 'binance', 'TRX_ERR_1', '01700000000');
it("Binance Pay is rejected for BDT checkout", $rejectBdtBinance['valid'] === false);

$rejectUsdBkash = validateAndPrepareOrder($mockPdo, 1, 10, null, 'USD', 'bkash', 'TRX_ERR_2', '01700000000');
it("bKash is rejected for USD checkout", $rejectUsdBkash['valid'] === false);

// 4E. Duplicate transaction reference protection
$mockPdo->exec("
    INSERT INTO orders (order_number, total_price, currency, base_amount_bdt, charge_amount, payment_method, payment_trx, created_at)
    VALUES ('ORD-TEST-1', 190.00, 'BDT', 190.00, 190.00, 'bkash', 'TRX_ALREADY_USED', '2026-10-16 12:00:00');
");
$dupRes = validateAndPrepareOrder($mockPdo, 1, 10, null, 'BDT', 'bkash', 'TRX_ALREADY_USED', '01700000000');
it("Duplicate transaction reference 'TRX_ALREADY_USED' is rejected", $dupRes['valid'] === false);
echo "\n";

// ======================================================================
// 5. STALE / MISSING RATE LOCKOUT & BDT RESILIENCE
// ======================================================================
echo "--- 5. Stale / Missing Rate Lockout & BDT Resilience ---\n";

// Set auto mode with stale rate (> 24 hours old) and no manual fallback
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'mode'                => 'auto',
    'auto_rate'           => 120.0000,
    'auto_status'         => 'success',
    'auto_last_updated'   => date('Y-m-d H:i:s', time() - 90000), // ~25 hours ago
    'rate_max_age_hours'  => 24,
    'manual_rate'         => 0.0000,
    'binance_active'      => true,
    'binance_pay_id'      => '12345678',
];

$staleUsd = validateAndPrepareOrder($mockPdo, 1, 10, null, 'USD', 'binance', 'TRX_STALE_1', 'UID_1');
it("Stale automated rate locks USD checkout", $staleUsd['valid'] === false);
it("Stale rate provides clear customer-friendly error message", stripos($staleUsd['error'], 'unavailable') !== false);

// Crucial: BDT checkout must continue unaffected during rate staleness!
$freshBdt = validateAndPrepareOrder($mockPdo, 1, 10, null, 'BDT', 'bkash', 'TRX_FRESH_BDT', '01700000000');
it("BDT checkout succeeds normally when USD exchange rate is stale", $freshBdt['valid'] === true);
echo "\n";

// ======================================================================
// 6. HISTORICAL ORDER DISPLAY FALLBACK
// ======================================================================
echo "--- 6. Historical Order Display Fallback ---\n";

// 6A. Legacy order pre-Gate 2B (currency null, charge_amount null)
$legacyOrderA = [
    'order_number' => 'ORD-OLD-001',
    'total_price'  => 350.00,
    'currency'     => null,
    'charge_amount'=> null,
];
it("formatOrderAmount handles legacy order with null currency as '৳ 350'", formatOrderAmount($legacyOrderA) === '৳ 350');

// 6B. Legacy order with currency='BDT' and charge_amount=0.00
$legacyOrderB = [
    'order_number' => 'ORD-OLD-002',
    'total_price'  => 1250.00,
    'currency'     => 'BDT',
    'charge_amount'=> 0.00,
];
it("formatOrderAmount handles BDT order as '৳ 1,250'", formatOrderAmount($legacyOrderB) === '৳ 1,250');

// 6C. Gate 2B USD order
$gate2bUsdOrder = [
    'order_number'  => 'ORD-USD-001',
    'total_price'   => 190.00, // Canonical BDT
    'currency'      => 'USD',
    'charge_amount' => 1.63,   // Payable USD
];
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['decimal_precision'] = 2;
it("formatOrderAmount handles USD order as '$ 1.63'", formatOrderAmount($gate2bUsdOrder) === '$ 1.63');
echo "\n";

// ======================================================================
// 7. STATIC VERIFICATION: SWITCHER REMOVED & ENDPOINT DISABLED
// ======================================================================
echo "--- 7. Static Verification: Switcher Removed & Endpoint Disabled ---\n";

$headerHtml = file_get_contents(__DIR__ . '/../includes/header.php');
it("header.php does not contain .rx-currency-switcher CSS", stripos($headerHtml, '.rx-currency-switcher') === false);
it("header.php does not contain showCurrencyToast function", stripos($headerHtml, 'function showCurrencyToast') === false);

$switchEndpoint = file_get_contents(__DIR__ . '/../ajax/switch_currency.php');
it("ajax/switch_currency.php returns 403 disabling manual switching", stripos($switchEndpoint, '403') !== false);
it("ajax/switch_currency.php explains currency is automatically configured", stripos($switchEndpoint, 'automatically configured') !== false);
echo "\n";

// ======================================================================
// 8. MIGRATION SAFETY GUARDS VERIFICATION
// ======================================================================
echo "--- 8. Migration Safety Guards Verification ---\n";

$migrationContent = file_get_contents(__DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php');
it("Migration contains CLI-only guard (php_sapi_name() !== 'cli')", stripos($migrationContent, "php_sapi_name() !== 'cli'") !== false);
it("Migration requires explicit confirmation token GATE2B_EXECUTE_LIVE", stripos($migrationContent, "GATE2B_EXECUTE_LIVE") !== false);
it("Migration defaults to dry-run mode", stripos($migrationContent, "PREFLIGHT / DRY-RUN") !== false);

// Verify live database tables and order #32 are untouched
$livePdo = db();
$liveTables = $livePdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
it("Live MariaDB connected safely and has existing tables", count($liveTables) > 0);

$o32 = $livePdo->query("SELECT id, order_number, total_price FROM orders WHERE id = 32")->fetch(PDO::FETCH_ASSOC);
if ($o32) {
    it("Order #32 exists with intact canonical total_price", (float)$o32['total_price'] === 490.00, "total_price = {$o32['total_price']}");
}

// Clean up mock config
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']);

echo "\n======================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "======================================================================\n";

if ($failed > 0) {
    exit(1);
}
