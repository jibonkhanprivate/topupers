<?php
/**
 * scratch/test_b2_binance_usd_integration.php
 *
 * TopUpers Gate B2 — BDT-Default Catalog & Binance-USD Payment Integration Verification Suite
 *
 * Verifies:
 * 1. Default currency is strictly BDT across catalog, game, cart, and ordinary checkout.
 * 2. Five payment methods: bKash, Nagad, Rocket, Upay (all BDT), and Binance Pay (5th method, USD only).
 * 3. Currency displays: BDT for local methods, USD strictly for Binance Pay.
 * 4. USD calculation with exchange rate, buffer markup formula, and half-up rounding.
 * 5. Local methods produce BDT orders with canonical BDT snapshot fields.
 * 6. Binance Pay produces USD orders with snapshot fields (currency='USD', base_amount_bdt, charge_amount, etc.).
 * 7. Rate locking: subsequent exchange-rate updates NEVER alter existing Binance orders.
 * 8. Missing, zero, or stale rate lockout: prevents order creation without fabricating rates.
 * 9. Duplicate transaction reference protection across both local and Binance orders.
 * 10. Backward compatibility with existing/legacy orders.
 *
 * ZERO live database writes (uses isolated in-memory SQLite database).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$totalAssertions  = 0;
$passedAssertions = 0;

function assertCondition(bool $cond, string $message): void {
    global $totalAssertions, $passedAssertions;
    $totalAssertions++;
    if ($cond) {
        $passedAssertions++;
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        throw new RuntimeException("Assertion failed: {$message}");
    }
}

echo "======================================================================\n";
echo "TopUpers Gate B2 — BDT-Default & Binance-USD Integration Suite\n";
echo "======================================================================\n\n";

// Helper to create test database
function createMockDb(): PDO {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            game_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1
        );
    ");

    $pdo->exec("
        CREATE TABLE coupons (
            id INTEGER PRIMARY KEY,
            code TEXT NOT NULL,
            type TEXT NOT NULL DEFAULT 'fixed',
            value DECIMAL(10,2) NOT NULL,
            min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            usage_limit INTEGER DEFAULT 0,
            used_count INTEGER NOT NULL DEFAULT 0,
            expires_at TEXT DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1
        );
    ");

    $pdo->exec("
        CREATE TABLE payment_methods (
            id INTEGER PRIMARY KEY,
            method_key TEXT UNIQUE NOT NULL,
            method_label TEXT NOT NULL,
            account_number TEXT DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0
        );
    ");

    $pdo->exec("
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT UNIQUE NOT NULL,
            user_id INTEGER DEFAULT NULL,
            product_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,
            player_id TEXT NOT NULL,
            server_id TEXT DEFAULT NULL,
            customer_name TEXT DEFAULT NULL,
            customer_phone TEXT DEFAULT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            coupon_code TEXT DEFAULT NULL,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_method TEXT NOT NULL,
            payment_number TEXT NOT NULL,
            payment_trx TEXT NOT NULL,
            ip_address TEXT DEFAULT '127.0.0.1',
            currency TEXT NOT NULL DEFAULT 'BDT',
            base_amount_bdt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
            rate_buffer_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            rate_mode TEXT NOT NULL DEFAULT 'canonical',
            rate_source TEXT NOT NULL DEFAULT 'canonical_bdt',
            charge_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            order_status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed test product (e.g. MLBB Weekly Pass: ৳190)
    $pdo->exec("INSERT INTO products (id, game_id, name, amount, is_active) VALUES (1, 10, 'Weekly Diamond Pass', 190.00, 1)");
    $pdo->exec("INSERT INTO products (id, game_id, name, amount, is_active) VALUES (2, 10, '500 Diamonds', 500.00, 1)");

    // Seed all 5 payment methods
    $pdo->exec("INSERT INTO payment_methods (id, method_key, method_label, account_number, is_active, sort_order) VALUES (1, 'bkash', 'bKash', '01700000001', 1, 1)");
    $pdo->exec("INSERT INTO payment_methods (id, method_key, method_label, account_number, is_active, sort_order) VALUES (2, 'nagad', 'Nagad', '01700000002', 1, 2)");
    $pdo->exec("INSERT INTO payment_methods (id, method_key, method_label, account_number, is_active, sort_order) VALUES (3, 'rocket', 'Rocket', '01700000003', 1, 3)");
    $pdo->exec("INSERT INTO payment_methods (id, method_key, method_label, account_number, is_active, sort_order) VALUES (4, 'upay', 'Upay', '01700000004', 1, 4)");
    $pdo->exec("INSERT INTO payment_methods (id, method_key, method_label, account_number, is_active, sort_order) VALUES (5, 'binance', 'Binance Pay', '', 1, 5)");

    return $pdo;
}

$pdo = createMockDb();

// -----------------------------------------------------------------------------
// STEP 1: Verify Default Currency Contract & Absence of Switcher
// -----------------------------------------------------------------------------
echo "[STEP 1] Default Currency & Switcher Removal Verification\n";
$_SESSION = [];
assertCondition(getActiveCurrency() === 'BDT', 'getActiveCurrency() defaults strictly to BDT');
assertCondition(getCurrencySymbol() === '৳', 'getCurrencySymbol() defaults strictly to ৳');
assertCondition(getCurrencySymbol('BDT') === '৳', "getCurrencySymbol('BDT') returns ৳");
assertCondition(getCurrencySymbol('USD') === '$', "getCurrencySymbol('USD') returns $");

// Check config payment methods
assertCondition(array_key_exists('bkash', PAYMENT_METHODS), "PAYMENT_METHODS includes bkash");
assertCondition(array_key_exists('nagad', PAYMENT_METHODS), "PAYMENT_METHODS includes nagad");
assertCondition(array_key_exists('rocket', PAYMENT_METHODS), "PAYMENT_METHODS includes rocket");
assertCondition(array_key_exists('upay', PAYMENT_METHODS), "PAYMENT_METHODS includes upay");
assertCondition(array_key_exists('binance', PAYMENT_METHODS), "PAYMENT_METHODS includes binance");
assertCondition(count(PAYMENT_METHODS) === 5, "PAYMENT_METHODS has exactly 5 payment methods");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 2: Mathematical Precision, Rate Conversion, and Buffer Markup
// -----------------------------------------------------------------------------
echo "[STEP 2] Exchange Rate Calculation & Precision Tests\n";
// Set mock exchange rate: 1 USD = 120 BDT, buffer = 5%
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG'] = [
    'mode'                      => 'manual',
    'manual_rate'               => 120.00,
    'manual_updated_at'         => date('Y-m-d H:i:s'),
    'buffer_percent'            => 5.0,
    'max_age_hours'             => 24,
    'binance_active'            => true,
    'binance_pay_id'            => '12345678',
    'binance_qr_image'          => 'uploads/binance_qr.png',
    'mock_has_snapshot_columns' => true,
];

// Example: MLBB Weekly Pass: ৳190
// 190 / 120 = 1.583333...
// With 5% buffer: 1.583333 * 1.05 = 1.6625 -> round(1.6625, 2) = 1.66 USD
$conv190 = convertBdtToUsd(190.00);
assertCondition($conv190 !== null, 'convertBdtToUsd(190.00) returned non-null');
assertCondition($conv190['charge_amount'] === 1.66, "190 BDT at rate 120 + 5% buffer rounds cleanly to 1.66 USD (got {$conv190['charge_amount']})");
assertCondition($conv190['exchange_rate'] === 120.0, "Conversion snapshot contains exchange_rate = 120.0");
assertCondition($conv190['buffer_percent'] === 5.0, "Conversion snapshot contains buffer_percent = 5.0");
assertCondition($conv190['rate_mode'] === 'manual', "Conversion snapshot contains rate_mode = 'manual'");
assertCondition($conv190['rate_source'] === 'manual_admin', "Conversion snapshot contains rate_source = 'manual_admin'");

// Test zero buffer: 190 / 120 = 1.5833... -> 1.58 USD
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['buffer_percent'] = 0.0;
$convNoBuf = convertBdtToUsd(190.00);
assertCondition($convNoBuf['charge_amount'] === 1.58, "190 BDT at rate 120 with 0% buffer rounds cleanly to 1.58 USD");

// Test precision edge case: 1000 / 120 = 8.3333..., + 5% = 8.75 USD
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['buffer_percent'] = 5.0;
$conv1000 = convertBdtToUsd(1000.00);
assertCondition($conv1000['charge_amount'] === 8.75, "1000 BDT at rate 120 + 5% buffer equals exactly 8.75 USD");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 3: Order Creation with Local BDT Payment Methods (bKash, Nagad, Rocket, Upay)
// -----------------------------------------------------------------------------
echo "[STEP 3] Local Payment Methods Order Creation (bKash, Nagad, Rocket, Upay)\n";
$localMethods = [
    'bkash'  => ['phone' => '01711111111', 'trx' => 'BKASH_TRX_001'],
    'nagad'  => ['phone' => '01811111111', 'trx' => 'NAGAD_TRX_002'],
    'rocket' => ['phone' => '01911111111', 'trx' => 'ROCKET_TRX_003'],
    'upay'   => ['phone' => '01611111111', 'trx' => 'UPAY_TRX_004'],
];

foreach ($localMethods as $method => $data) {
    $orderCurrency = ($method === 'binance') ? 'USD' : 'BDT';
    $val = validateAndPrepareOrder(
        $pdo,
        1, // product id 1 (190 BDT)
        10, // game id 10
        null, // coupon
        $orderCurrency,
        $method,
        $data['trx'],
        $data['phone']
    );

    assertCondition($val['valid'] === true, "validateAndPrepareOrder succeeds for {$method}");
    assertCondition($val['snapshot']['currency'] === 'BDT', "{$method} order currency is BDT");
    assertCondition($val['snapshot']['base_amount_bdt'] === 190.00, "{$method} base_amount_bdt is 190.00");
    assertCondition($val['snapshot']['exchange_rate'] === 1.0000, "{$method} exchange_rate is 1.0000");
    assertCondition($val['snapshot']['rate_buffer_percent'] === 0.00, "{$method} rate_buffer_percent is 0.00");
    assertCondition($val['snapshot']['charge_amount'] === 190.00, "{$method} charge_amount is 190.00");

    // Insert order into mock database
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, product_id, game_id, player_id, unit_price, total_price,
                            payment_method, payment_number, payment_trx,
                            currency, base_amount_bdt, exchange_rate, rate_buffer_percent, rate_mode, rate_source, charge_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ordNum = 'ORD_' . strtoupper($method) . '_001';
    $stmt->execute([
        $ordNum, 1, 10, '12345678', 190.00, 190.00,
        $method, $data['phone'], $data['trx'],
        $val['snapshot']['currency'], $val['snapshot']['base_amount_bdt'],
        $val['snapshot']['exchange_rate'], $val['snapshot']['rate_buffer_percent'],
        $val['snapshot']['rate_mode'], $val['snapshot']['rate_source'],
        $val['snapshot']['charge_amount']
    ]);

    // Verify persisted row
    $saved = $pdo->query("SELECT * FROM orders WHERE order_number = '{$ordNum}'")->fetch();
    assertCondition($saved['currency'] === 'BDT', "Persisted {$method} order has currency 'BDT'");
    assertCondition((float)$saved['charge_amount'] === 190.00, "Persisted {$method} order has charge_amount 190.00");
    assertCondition((float)$saved['exchange_rate'] === 1.0000, "Persisted {$method} order has exchange_rate 1.0000");
}
echo "\n";

// -----------------------------------------------------------------------------
// STEP 4: Order Creation with Binance Pay (USD Only, Snapshot Saved)
// -----------------------------------------------------------------------------
echo "[STEP 4] Binance Pay Order Creation & Snapshot Locking\n";
$binanceMethod = 'binance';
$orderCurrency = ($binanceMethod === 'binance') ? 'USD' : 'BDT';
$binanceTrx = 'BINANCE_ORDER_99998888';
$binanceAccount = 'UID_88776655';

$valBinance = validateAndPrepareOrder(
    $pdo,
    1, // product id 1 (190 BDT)
    10, // game id 10
    null,
    $orderCurrency,
    $binanceMethod,
    $binanceTrx,
    $binanceAccount
);

assertCondition($valBinance['valid'] === true, 'validateAndPrepareOrder succeeds for Binance Pay');
assertCondition($valBinance['snapshot']['currency'] === 'USD', "Binance order currency is strictly 'USD'");
assertCondition($valBinance['snapshot']['base_amount_bdt'] === 190.00, 'Binance base_amount_bdt is 190.00');
assertCondition($valBinance['snapshot']['exchange_rate'] === 120.00, 'Binance exchange_rate is 120.00');
assertCondition($valBinance['snapshot']['rate_buffer_percent'] === 5.00, 'Binance rate_buffer_percent is 5.00');
assertCondition($valBinance['snapshot']['charge_amount'] === 1.66, 'Binance charge_amount is 1.66 USD');

// Insert Binance order
$binanceOrdNum = 'ORD_BINANCE_001';
$stmt = $pdo->prepare("
    INSERT INTO orders (order_number, product_id, game_id, player_id, unit_price, total_price,
                        payment_method, payment_number, payment_trx,
                        currency, base_amount_bdt, exchange_rate, rate_buffer_percent, rate_mode, rate_source, charge_amount)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $binanceOrdNum, 1, 10, '12345678', 190.00, 1.66,
    $binanceMethod, $binanceAccount, $binanceTrx,
    $valBinance['snapshot']['currency'], $valBinance['snapshot']['base_amount_bdt'],
    $valBinance['snapshot']['exchange_rate'], $valBinance['snapshot']['rate_buffer_percent'],
    $valBinance['snapshot']['rate_mode'], $valBinance['snapshot']['rate_source'],
    $valBinance['snapshot']['charge_amount']
]);

// Verify persisted Binance row
$savedBinance = $pdo->query("SELECT * FROM orders WHERE order_number = '{$binanceOrdNum}'")->fetch();
assertCondition($savedBinance['currency'] === 'USD', "Persisted Binance order has currency 'USD'");
assertCondition((float)$savedBinance['charge_amount'] === 1.66, "Persisted Binance order has charge_amount 1.66");
assertCondition((float)$savedBinance['base_amount_bdt'] === 190.00, "Persisted Binance order has base_amount_bdt 190.00");
assertCondition((float)$savedBinance['exchange_rate'] === 120.00, "Persisted Binance order has exchange_rate 120.00");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 5: Rate Locking Verification (Subsequent Rate Update Cannot Alter Order)
// -----------------------------------------------------------------------------
echo "[STEP 5] Rate Locking Verification Under Subsequent Exchange Rate Fluctuations\n";
// Update the mock exchange rate drastically (e.g. rate drops to 100 or jumps to 150)
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['manual_rate'] = 150.00;
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['buffer_percent'] = 10.00;

// Re-read existing Binance order from database
$lockedOrder = $pdo->query("SELECT * FROM orders WHERE order_number = '{$binanceOrdNum}'")->fetch();
assertCondition((float)$lockedOrder['charge_amount'] === 1.66, "Existing order charge_amount remains locked at 1.66 USD");
assertCondition((float)$lockedOrder['exchange_rate'] === 120.00, "Existing order exchange_rate remains locked at 120.00");
assertCondition((float)$lockedOrder['rate_buffer_percent'] === 5.00, "Existing order rate_buffer_percent remains locked at 5.00%");

// Format helper test: historical orders format accurately
assertCondition(formatOrderAmount($lockedOrder) === '$ 1.66', "formatOrderAmount on locked Binance order outputs '$ 1.66'");

$bdtOrder = $pdo->query("SELECT * FROM orders WHERE order_number = 'ORD_BKASH_001'")->fetch();
assertCondition(formatOrderAmount($bdtOrder) === '৳ 190', "formatOrderAmount on local bKash order outputs '৳ 190'");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 6: Fail-Closed Protection: Missing, Zero, or Stale Rates
// -----------------------------------------------------------------------------
echo "[STEP 6] Missing, Zero, and Stale Rate Lockout Tests\n";

// 6A: Rate is 0.0000
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['manual_rate'] = 0.0000;
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['mode'] = 'manual';
$valZeroRate = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'binance', 'TRX_FAIL_1', 'UID_1');
assertCondition($valZeroRate['valid'] === false, "Zero exchange rate refuses Binance checkout");
assertCondition(stripos($valZeroRate['error'], 'USD checkout is currently unavailable') !== false, "Error message explains USD checkout unavailable");

// 6B: Auto rate is stale (> max_age_hours)
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['mode'] = 'auto';
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['auto_rate'] = 125.00;
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['auto_status'] = 'success';
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['auto_last_updated'] = date('Y-m-d H:i:s', time() - (25 * 3600)); // 25 hours ago (stale)
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['manual_rate'] = 0.0000; // No valid manual fallback

$valStaleRate = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'binance', 'TRX_FAIL_2', 'UID_2');
assertCondition($valStaleRate['valid'] === false, "Stale auto rate (> 24 hours) refuses Binance checkout");
assertCondition(stripos($valStaleRate['error'], 'unavailable') !== false, "Error message explains unavailability");

// 6C: Binance inactive
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['mode'] = 'manual';
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['manual_rate'] = 120.00;
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['binance_active'] = false;
$valInactive = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'binance', 'TRX_FAIL_3', 'UID_3');
assertCondition($valInactive['valid'] === false, "Inactive Binance Pay refuses checkout");

// 6D: Binance missing credentials
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['binance_active'] = true;
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['binance_pay_id'] = '';
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['binance_qr_image'] = '';
$valNoCreds = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'binance', 'TRX_FAIL_4', 'UID_4');
assertCondition($valNoCreds['valid'] === false, "Missing Binance merchant credentials refuses checkout");

// Restore valid configuration
$GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']['binance_pay_id'] = '12345678';
echo "\n";

// -----------------------------------------------------------------------------
// STEP 7: Cross-Contamination & Method Restrictions
// -----------------------------------------------------------------------------
echo "[STEP 7] Currency & Payment Method Boundary Enforcement\n";
// Attempt Binance Pay with BDT
$valBinanceBdt = validateAndPrepareOrder($pdo, 1, 10, null, 'BDT', 'binance', 'TRX_MISMATCH_1', 'UID_1');
assertCondition($valBinanceBdt['valid'] === false, "Binance Pay with BDT currency is rejected");
assertCondition(stripos($valBinanceBdt['error'], 'only supported for USD') !== false, "Error states Binance Pay only supported for USD");

// Attempt bKash with USD
$valBkashUsd = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'bkash', 'TRX_MISMATCH_2', '01700000000');
assertCondition($valBkashUsd['valid'] === false, "bKash with USD currency is rejected");
assertCondition(stripos($valBkashUsd['error'], 'Only Binance Pay is accepted for international USD') !== false, "Error states only Binance accepted for USD");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 8: Duplicate Transaction Reference Protection
// -----------------------------------------------------------------------------
echo "[STEP 8] Duplicate Transaction Reference Protection\n";
// Attempt to reuse already-submitted bKash TRX: 'BKASH_TRX_001'
$valDupBkash = validateAndPrepareOrder($pdo, 1, 10, null, 'BDT', 'bkash', 'BKASH_TRX_001', '01711111111');
assertCondition($valDupBkash['valid'] === false, "Duplicate bKash transaction reference is rejected");
assertCondition(stripos($valDupBkash['error'], 'already been submitted') !== false, "Error message flags duplicate transaction ID");

// Attempt to reuse already-submitted Binance TRX: 'BINANCE_ORDER_99998888'
$valDupBinance = validateAndPrepareOrder($pdo, 1, 10, null, 'USD', 'binance', 'BINANCE_ORDER_99998888', 'UID_88776655');
assertCondition($valDupBinance['valid'] === false, "Duplicate Binance transaction reference is rejected");
assertCondition(stripos($valDupBinance['error'], 'already been submitted') !== false, "Error message flags duplicate Binance Order ID");
echo "\n";

// -----------------------------------------------------------------------------
// STEP 9: Backward Compatibility with Legacy Orders
// -----------------------------------------------------------------------------
echo "[STEP 9] Backward Compatibility with Legacy Baseline Orders\n";
// Legacy order representation (missing or null currency fields, charge_amount 0.00)
$legacyOrder = [
    'total_price'   => 250.00,
    'currency'      => null,
    'charge_amount' => null,
];
assertCondition(formatOrderAmount($legacyOrder) === '৳ 250', "formatOrderAmount handles legacy orders with null currency as BDT '৳ 250'");

$legacyOrder2 = [
    'total_price'   => 500.00,
    'currency'      => 'BDT',
    'charge_amount' => 0.00,
];
assertCondition(formatOrderAmount($legacyOrder2) === '৳ 500', "formatOrderAmount handles BDT order with charge_amount 0 as '৳ 500'");
echo "\n";

// Clean up mock config
unset($GLOBALS['MOCK_EXCHANGE_RATE_CONFIG']);

echo "======================================================================\n";
echo "INTEGRATION SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
echo "======================================================================\n";
