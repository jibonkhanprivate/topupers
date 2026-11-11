<?php
/**
 * scratch/test_phase_c2_offline.php
 *
 * TopUpers Gate 2B — Phase C2 Pure Offline Unit & Validation Logic Test Suite
 * STRICT SAFETY: Zero database writes, zero DDL, zero modifications to production files.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/currency.php';

echo "======================================================================\n";
echo "TOPUPERS GATE 2B — PHASE C2 OFFLINE TEST SUITE\n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}" . ($detail ? " — {$detail}" : '') . "\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name}" . ($detail ? " — {$detail}" : '') . "\n";
    }
}

// ----------------------------------------------------------------------
// 1. Proposed Helper: formatOrderAmount() with Historical Compatibility
// ----------------------------------------------------------------------
function formatOrderAmount(array $order): string {
    $curr = strtoupper(trim((string)($order['currency'] ?? 'BDT')));

    if ($curr === 'USD') {
        $usdAmount = isset($order['charge_amount']) && (float)$order['charge_amount'] > 0
            ? (float)$order['charge_amount']
            : (float)($order['total_price'] ?? 0);
        return '$ ' . number_format($usdAmount, 2, '.', ',');
    }

    // Default / Historical BDT
    $bdtAmount = isset($order['base_amount_bdt']) && (float)$order['base_amount_bdt'] > 0
        ? (float)$order['base_amount_bdt']
        : (float)($order['total_price'] ?? 0);
    return '৳ ' . number_format($bdtAmount, 0, '.', ',');
}

// ----------------------------------------------------------------------
// 2. Proposed Shared Validator: validateOrderSubmission()
// ----------------------------------------------------------------------
function validateOrderSubmission(
    array $product,
    ?array $coupon,
    string $currency,
    string $paymentMethod,
    string $paymentTrx,
    array $activePaymentMethods,
    array $existingNonCancelledTrxIds
): array {
    $errors = [];

    // 1. Validate Product
    if (empty($product) || empty($product['is_active'])) {
        return ['valid' => false, 'error' => 'Invalid or inactive product.'];
    }
    $basePriceBdt = (float)$product['amount'];
    if ($basePriceBdt <= 0.0) {
        return ['valid' => false, 'error' => 'Product price must be greater than zero.'];
    }

    // 2. Validate Coupon in Canonical BDT
    $discountBdt = 0.0;
    $usedCouponCode = null;
    if ($coupon !== null) {
        if (empty($coupon['is_active'])) {
            return ['valid' => false, 'error' => 'Promo code is inactive.'];
        }
        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Promo code has expired.'];
        }
        if (!empty($coupon['usage_limit']) && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            return ['valid' => false, 'error' => 'Promo code usage limit reached.'];
        }
        $minPurchase = (float)($coupon['min_purchase'] ?? 0);
        if ($minPurchase > 0 && $basePriceBdt < $minPurchase) {
            return ['valid' => false, 'error' => 'Minimum spend for coupon not met.'];
        }

        // Calculate discount
        if (($coupon['type'] ?? 'fixed') === 'percent') {
            $calc = $basePriceBdt * ((float)$coupon['value'] / 100.0);
            $maxDisc = (float)($coupon['max_discount'] ?? 0);
            if ($maxDisc > 0 && $calc > $maxDisc) {
                $calc = $maxDisc;
            }
            $discountBdt = round($calc, 2);
        } else {
            $discountBdt = round((float)$coupon['value'], 2);
        }

        if ($discountBdt > $basePriceBdt) {
            $discountBdt = $basePriceBdt;
        }
        $usedCouponCode = $coupon['code'];
    }

    $netBdtAmount = max(0.00, $basePriceBdt - $discountBdt);

    // 3. Currency Validation & Rate Resolution
    $currNormalized = strtoupper(trim($currency));
    if (!in_array($currNormalized, ['BDT', 'USD'], true)) {
        return ['valid' => false, 'error' => 'Unsupported currency: ' . $currency];
    }

    $snapshot = [];
    if ($currNormalized === 'BDT') {
        $snapshot = [
            'currency'            => 'BDT',
            'base_amount_bdt'     => $netBdtAmount,
            'exchange_rate'       => 1.0000,
            'rate_buffer_percent' => 0.00,
            'rate_mode'           => 'canonical',
            'rate_source'         => 'canonical_bdt',
            'charge_amount'       => $netBdtAmount,
            'total_price'         => $netBdtAmount,
        ];
    } else {
        // USD checkout
        $usdCheck = isUsdCheckoutAllowed();
        if (!$usdCheck['allowed']) {
            return [
                'valid' => false,
                'error' => 'USD checkout is currently unavailable: ' . ($usdCheck['reason'] ?? 'Unspecified error')
            ];
        }

        $conversion = convertBdtToUsd($netBdtAmount);
        if ($conversion === null) {
            return ['valid' => false, 'error' => 'USD price conversion failed.'];
        }

        $snapshot = [
            'currency'            => 'USD',
            'base_amount_bdt'     => $netBdtAmount,
            'exchange_rate'       => $conversion['exchange_rate'],
            'rate_buffer_percent' => $conversion['buffer_percent'],
            'rate_mode'           => $conversion['rate_mode'],
            'rate_source'         => $conversion['rate_source'],
            'charge_amount'       => $conversion['charge_amount'],
            'total_price'         => $conversion['charge_amount'],
        ];
    }

    // 4. Payment Method & Cross-Contamination Guard
    $pmKey = strtolower(trim($paymentMethod));
    if (!in_array($pmKey, $activePaymentMethods, true)) {
        return ['valid' => false, 'error' => 'Invalid or inactive payment method: ' . $pmKey];
    }

    if ($currNormalized === 'BDT' && $pmKey === 'binance') {
        return ['valid' => false, 'error' => 'Binance Pay is only available for USD orders.'];
    }

    if ($currNormalized === 'USD' && $pmKey !== 'binance') {
        return ['valid' => false, 'error' => 'Only Binance Pay is accepted for USD orders.'];
    }

    // 5. Duplicate Transaction Reference Guard
    $cleanTrx = strtoupper(trim($paymentTrx));
    if (empty($cleanTrx)) {
        return ['valid' => false, 'error' => 'Transaction reference is required.'];
    }
    if (in_array($cleanTrx, $existingNonCancelledTrxIds, true)) {
        return ['valid' => false, 'error' => 'This Transaction ID has already been submitted for an active order.'];
    }

    return [
        'valid'            => true,
        'coupon_code'      => $usedCouponCode,
        'discount_amount'  => $discountBdt,
        'snapshot'         => $snapshot,
    ];
}

// ======================================================================
// TEST 1: HISTORICAL ORDER DISPLAY COMPATIBILITY
// ======================================================================
echo "--- TEST SUITE 1: Historical Order Display Compatibility ---\n";

$histOrder1 = ['total_price' => 1250.00]; // No currency column, no snapshot
assertTest("Historical order with no currency column formats as BDT",
    formatOrderAmount($histOrder1) === '৳ 1,250',
    formatOrderAmount($histOrder1)
);

$histOrder2 = ['total_price' => 500.00, 'currency' => null, 'base_amount_bdt' => null];
assertTest("Historical order with NULL currency columns formats as BDT",
    formatOrderAmount($histOrder2) === '৳ 500',
    formatOrderAmount($histOrder2)
);

$histOrder3 = ['total_price' => 750.00, 'currency' => 'BDT', 'base_amount_bdt' => 750.00];
assertTest("Explicit BDT order formats as BDT",
    formatOrderAmount($histOrder3) === '৳ 750',
    formatOrderAmount($histOrder3)
);

$usdOrder = ['total_price' => 10.50, 'currency' => 'USD', 'charge_amount' => 10.50, 'base_amount_bdt' => 1260.00];
assertTest("Gate 2B USD order formats as USD with 2 decimal places",
    formatOrderAmount($usdOrder) === '$ 10.50',
    formatOrderAmount($usdOrder)
);

// ======================================================================
// TEST 2: USD CHECKOUT REJECTION WHEN UNAVAILABLE (FAIL-CLOSED)
// ======================================================================
echo "\n--- TEST SUITE 2: USD Fail-Closed Invariants ---\n";

global $MOCK_EXCHANGE_RATE_CONFIG;

// Case 2.1: Switcher disabled
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => false,
    'mode'             => 'manual',
    'manual_rate'      => 120.00,
    'binance_active'   => true,
    'binance_pay_id'   => '12345678',
];
$res = isUsdCheckoutAllowed();
assertTest("USD rejected when currency switcher disabled",
    $res['allowed'] === false && strpos($res['reason'], 'disabled') !== false,
    $res['reason'] ?? ''
);

// Case 2.2: Rate is zero
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => true,
    'mode'             => 'manual',
    'manual_rate'      => 0.00,
    'binance_active'   => true,
    'binance_pay_id'   => '12345678',
];
$res = isUsdCheckoutAllowed();
assertTest("USD rejected when exchange rate is 0.00",
    $res['allowed'] === false,
    $res['reason'] ?? ''
);

// Case 2.3: Binance Pay inactive
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => true,
    'mode'             => 'manual',
    'manual_rate'      => 120.00,
    'binance_active'   => false,
    'binance_pay_id'   => '12345678',
];
$res = isUsdCheckoutAllowed();
assertTest("USD rejected when Binance Pay is inactive",
    $res['allowed'] === false && strpos($res['reason'], 'not enabled') !== false,
    $res['reason'] ?? ''
);

// Case 2.4: Binance Pay active but missing Pay ID and QR
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => true,
    'mode'             => 'manual',
    'manual_rate'      => 120.00,
    'binance_active'   => true,
    'binance_pay_id'   => '',
    'binance_qr_image' => '',
];
$res = isUsdCheckoutAllowed();
assertTest("USD rejected when Binance credentials unconfigured",
    $res['allowed'] === false && strpos($res['reason'], 'not configured') !== false,
    $res['reason'] ?? ''
);

// Case 2.5: Fully configured and valid
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => true,
    'mode'             => 'manual',
    'manual_rate'      => 120.00,
    'binance_active'   => true,
    'binance_pay_id'   => '987654321',
    'binance_qr_image' => '',
];
$res = isUsdCheckoutAllowed();
assertTest("USD permitted when switcher, rate, and Binance credentials valid",
    $res['allowed'] === true,
    'allowed = true'
);

// ======================================================================
// TEST 3: PAYMENT METHOD CROSS-CONTAMINATION & ALLOWLISTING
// ======================================================================
echo "\n--- TEST SUITE 3: Payment Method Cross-Contamination & Allowlists ---\n";

$sampleProduct = ['id' => 1, 'game_id' => 1, 'amount' => 1200.00, 'is_active' => 1];
$activeMethods = ['bkash', 'nagad', 'rocket', 'upay', 'binance'];
$existingTrx   = ['TRX123456'];

// 3.1 BDT attempting Binance Pay
$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'binance', 'TRX999', $activeMethods, $existingTrx);
assertTest("BDT order attempting Binance Pay is rejected",
    $res['valid'] === false && strpos($res['error'], 'Binance Pay is only available for USD') !== false,
    $res['error'] ?? ''
);

// 3.2 USD attempting bKash
$res = validateOrderSubmission($sampleProduct, null, 'USD', 'bkash', 'TRX999', $activeMethods, $existingTrx);
assertTest("USD order attempting bKash is rejected",
    $res['valid'] === false && strpos($res['error'], 'Only Binance Pay is accepted for USD') !== false,
    $res['error'] ?? ''
);

// 3.3 USD attempting Nagad
$res = validateOrderSubmission($sampleProduct, null, 'USD', 'nagad', 'TRX999', $activeMethods, $existingTrx);
assertTest("USD order attempting Nagad is rejected",
    $res['valid'] === false && strpos($res['error'], 'Only Binance Pay is accepted for USD') !== false,
    $res['error'] ?? ''
);

// 3.4 BDT attempting Upay (when Upay is in active list)
$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'upay', 'TRX999', $activeMethods, $existingTrx);
assertTest("BDT order with Upay is accepted when active",
    $res['valid'] === true,
    'valid = true'
);

// 3.5 BDT attempting unknown/unapproved method
$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'paypal', 'TRX999', $activeMethods, $existingTrx);
assertTest("BDT order with unregistered method is rejected",
    $res['valid'] === false && strpos($res['error'], 'Invalid or inactive payment method') !== false,
    $res['error'] ?? ''
);

// ======================================================================
// TEST 4: DUPLICATE TRANSACTION REFERENCE PROTECTION
// ======================================================================
echo "\n--- TEST SUITE 4: Duplicate Transaction Reference Protection ---\n";

$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'bkash', 'TRX123456', $activeMethods, $existingTrx);
assertTest("Submitting exact duplicate TrxID is rejected",
    $res['valid'] === false && strpos($res['error'], 'already been submitted') !== false,
    $res['error'] ?? ''
);

$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'bkash', '  trx123456  ', $activeMethods, $existingTrx);
assertTest("Submitting duplicate TrxID with whitespace/lowercase is normalized and rejected",
    $res['valid'] === false && strpos($res['error'], 'already been submitted') !== false,
    $res['error'] ?? ''
);

$res = validateOrderSubmission($sampleProduct, null, 'BDT', 'bkash', 'TRX_NEW_UNIQUE', $activeMethods, $existingTrx);
assertTest("Submitting unique TrxID is accepted",
    $res['valid'] === true,
    'valid = true'
);

// ======================================================================
// TEST 5: SERVER-SIDE COUPON VALIDATION & CANONICAL BDT CALCULATION
// ======================================================================
echo "\n--- TEST SUITE 5: Server-Side Coupon Security & Calculation ---\n";

// Coupon min_purchase not met
$couponMin = [
    'code' => 'BIGBUY', 'type' => 'fixed', 'value' => 100.00,
    'min_purchase' => 2000.00, 'usage_limit' => 10, 'used_count' => 0,
    'is_active' => 1, 'expires_at' => null
];
$res = validateOrderSubmission($sampleProduct, $couponMin, 'BDT', 'bkash', 'TRX_NEW', $activeMethods, $existingTrx);
assertTest("Coupon rejected when order amount (1200) < min_purchase (2000)",
    $res['valid'] === false && strpos($res['error'], 'Minimum spend') !== false,
    $res['error'] ?? ''
);

// Percentage coupon with max_discount cap
$couponPct = [
    'code' => 'DISC20', 'type' => 'percent', 'value' => 20.00, // 20% of 1200 = 240
    'max_discount' => 150.00, 'min_purchase' => 500.00, 'usage_limit' => 10,
    'used_count' => 0, 'is_active' => 1, 'expires_at' => null
];
$res = validateOrderSubmission($sampleProduct, $couponPct, 'BDT', 'bkash', 'TRX_NEW', $activeMethods, $existingTrx);
assertTest("Percentage coupon discount capped at max_discount (150 BDT)",
    $res['valid'] === true && $res['discount_amount'] === 150.00 && $res['snapshot']['base_amount_bdt'] === 1050.00,
    "Discount: {$res['discount_amount']}, Net: {$res['snapshot']['base_amount_bdt']}"
);

// USD order with coupon applied
$MOCK_EXCHANGE_RATE_CONFIG = [
    'switcher_enabled' => true,
    'mode'             => 'manual',
    'manual_rate'      => 120.00,
    'buffer_percent'   => 0.00,
    'binance_active'   => true,
    'binance_pay_id'   => '987654321',
    'binance_qr_image' => '',
];
// Net BDT is 1050.00. 1050 / 120 = 8.75 USD
$res = validateOrderSubmission($sampleProduct, $couponPct, 'USD', 'binance', 'BINANCE_TRX_999', $activeMethods, $existingTrx);
assertTest("USD snapshot calculates correctly after BDT coupon discount (1050 BDT / 120 = 8.75 USD)",
    $res['valid'] === true && $res['snapshot']['charge_amount'] === 8.75 && $res['snapshot']['currency'] === 'USD',
    "Charge USD: {$res['snapshot']['charge_amount']}, Base BDT: {$res['snapshot']['base_amount_bdt']}"
);

echo "\n======================================================================\n";
echo "SUMMARY: Passed {$passCount} tests, Failed {$failCount} tests.\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
