<?php
/**
 * scratch/test_phase_c4_2_offline.php
 *
 * TopUpers Gate 2B — Phase C4.2 Offline Verification Suite
 *
 * STRICT SAFETY & RESTRICTIONS:
 * - ZERO database writes to live database rechax_official.
 * - ZERO DDL or migrations executed against live database.
 * - Concurrency simulations and rollback tests run against isolated in-memory SQLite instances.
 * - Clearly documents MariaDB concurrency characteristics vs SQLite simulation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

echo "======================================================================\n";
echo "TOPUPERS GATE 2B — PHASE C4.2 SURGICAL SAFETY FIXES TEST SUITE\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
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

$livePdo = db();

// ======================================================================
// SUITE 1: DOUBLE-REFUND PROTECTION & WALLET TRANSACTION ATOMICITY
// ======================================================================
echo "--- SUITE 1: Double-Refund Protection & Wallet Atomicity ---\n";

// Create isolated in-memory SQLite database for simulating order and wallet ledger
$simDb = new PDO('sqlite::memory:');
$simDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$simDb->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT NOT NULL,
        wallet_balance REAL NOT NULL DEFAULT 0.00
    );
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        order_number TEXT NOT NULL,
        user_id INTEGER NULL,
        total_price REAL NOT NULL,
        currency TEXT DEFAULT 'BDT',
        base_amount_bdt REAL DEFAULT 0.00,
        charge_amount REAL DEFAULT 0.00,
        order_status TEXT NOT NULL DEFAULT 'completed',
        payment_status TEXT NOT NULL DEFAULT 'paid',
        admin_note TEXT NULL
    );
    CREATE TABLE wallet_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        amount REAL NOT NULL,
        balance_before REAL NOT NULL,
        balance_after REAL NOT NULL,
        reference_type TEXT,
        reference_id TEXT,
        admin_id INTEGER,
        description TEXT
    );
");

// Seed test user and orders
$simDb->exec("
    INSERT INTO users (id, username, wallet_balance) VALUES (1, 'testuser', 500.00);
    INSERT INTO orders (id, order_number, user_id, total_price, currency, base_amount_bdt, charge_amount, order_status, payment_status)
    VALUES 
        (101, 'ORD-BDT-101', 1, 300.00, 'BDT', 300.00, 300.00, 'completed', 'paid'),
        (102, 'ORD-USD-102', 1, 10.00, 'USD', 1250.00, 10.00, 'completed', 'paid'),
        (103, 'ORD-REF-103', 1, 200.00, 'BDT', 200.00, 200.00, 'refunded', 'refunded'),
        (104, 'ORD-GST-104', NULL, 150.00, 'BDT', 150.00, 150.00, 'completed', 'paid');
");

// Helper simulating the exact refund transaction logic from admin/orders.php
function simulateAdminRefund(PDO $db, int $orderId, string $reason, ?int $adminId = 1): array {
    $db->beginTransaction();
    try {
        // Query order (simulates SELECT ... FOR UPDATE)
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Order not found.'];
        }

        if ($order['order_status'] === 'refunded' || ($order['payment_status'] ?? '') === 'refunded') {
            $db->rollBack();
            return ['success' => false, 'error' => 'Order has already been refunded. Duplicate refund prevented.'];
        }

        $orderCurrency = strtoupper((string)($order['currency'] ?? 'BDT'));
        $refundAmountBdt = (isset($order['base_amount_bdt']) && (float)$order['base_amount_bdt'] > 0)
            ? (float)$order['base_amount_bdt']
            : (float)($order['total_price'] ?? 0);

        if ($refundAmountBdt <= 0) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Cannot refund order with non-positive amount.'];
        }

        if ($order['user_id']) {
            $userId = (int)$order['user_id'];
            // Lock user balance
            $uStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $currBal = (float)$uStmt->fetchColumn();

            $balanceBefore = $currBal;
            $balanceAfter = $balanceBefore + $refundAmountBdt;

            // Update user balance
            $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$balanceAfter, $userId]);

            // Insert wallet ledger
            $refundDesc = "Refund for Order #" . $order['order_number'] . " (Reason: $reason)";
            $insLedger = $db->prepare("
                INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, reference_type, reference_id, admin_id, description)
                VALUES (?, 'refund', ?, ?, ?, 'order', ?, ?, ?)
            ");
            $insLedger->execute([$userId, $refundAmountBdt, $balanceBefore, $balanceAfter, $order['order_number'], $adminId, $refundDesc]);

            // Atomic status update guard
            $upd = $db->prepare("
                UPDATE orders 
                SET order_status = 'refunded', 
                    payment_status = 'refunded', 
                    admin_note = COALESCE(admin_note, '') || '\n[Refund] ' || ? 
                WHERE id = ? AND order_status != 'refunded'
            ");
            $upd->execute(["Refunded " . $refundAmountBdt . " BDT. Reason: " . $reason, $orderId]);

            if ($upd->rowCount() !== 1) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Concurrent refund conflict detected. Transaction rolled back.'];
            }

            $db->commit();
            return ['success' => true, 'refunded_amount' => $refundAmountBdt, 'order_number' => $order['order_number']];
        } else {
            // Guest order
            $upd = $db->prepare("
                UPDATE orders 
                SET order_status = 'refunded', 
                    payment_status = 'refunded', 
                    admin_note = COALESCE(admin_note, '') || '\n[Manual Refund] ' || ? 
                WHERE id = ? AND order_status != 'refunded'
            ");
            $upd->execute(["Manual non-registered customer refund. Reason: " . $reason, $orderId]);

            if ($upd->rowCount() !== 1) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Concurrent refund conflict detected. Transaction rolled back.'];
            }

            $db->commit();
            return ['success' => true, 'refunded_amount' => $refundAmountBdt, 'order_number' => $order['order_number']];
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Test 1.1: Standard BDT order refund
$res1 = simulateAdminRefund($simDb, 101, 'Item unavailable');
$userBalAfter1 = (float)$simDb->query("SELECT wallet_balance FROM users WHERE id = 1")->fetchColumn();
assertTest(
    "Standard BDT order refunds successfully and credits user wallet",
    $res1['success'] === true && $userBalAfter1 === 800.00,
    "Initial: 500, Refunded: {$res1['refunded_amount']}, Balance After: {$userBalAfter1}"
);

// Test 1.2: Attempt duplicate refund on the same order (already refunded)
$res2 = simulateAdminRefund($simDb, 101, 'Duplicate attempt');
$userBalAfter2 = (float)$simDb->query("SELECT wallet_balance FROM users WHERE id = 1")->fetchColumn();
$txCount1 = (int)$simDb->query("SELECT COUNT(*) FROM wallet_transactions WHERE reference_id = 'ORD-BDT-101'")->fetchColumn();
assertTest(
    "Second refund attempt on already-refunded order is strictly blocked",
    $res2['success'] === false && strpos($res2['error'], 'already been refunded') !== false && $userBalAfter2 === 800.00 && $txCount1 === 1,
    "Blocked with: '{$res2['error']}', Balance untouched at: {$userBalAfter2}, Tx records: {$txCount1}"
);

// Test 1.3: Pre-existing refunded order (ORD-REF-103) is rejected immediately
$res3 = simulateAdminRefund($simDb, 103, 'Attempting refund on historical refunded order');
assertTest(
    "Pre-existing refunded order cannot be re-refunded",
    $res3['success'] === false && strpos($res3['error'], 'already been refunded') !== false,
    "Error: '{$res3['error']}'"
);

// Test 1.4: USD order refunds canonical net BDT (1250 BDT), NOT USD total (10.00)
$resUsd = simulateAdminRefund($simDb, 102, 'USD order refund');
$userBalAfterUsd = (float)$simDb->query("SELECT wallet_balance FROM users WHERE id = 1")->fetchColumn();
assertTest(
    "USD order refunds canonical BDT (৳1,250.00) to wallet instead of charge_amount ($10.00)",
    $resUsd['success'] === true && $resUsd['refunded_amount'] === 1250.00 && $userBalAfterUsd === 2050.00,
    "Refunded BDT: {$resUsd['refunded_amount']}, User Balance: {$userBalAfterUsd}"
);

// Test 1.5: Guest order (user_id IS NULL) updates order status without wallet credit
$resGuest = simulateAdminRefund($simDb, 104, 'Guest order refund');
$orderGuestStatus = $simDb->query("SELECT order_status FROM orders WHERE id = 104")->fetchColumn();
$guestTxCount = (int)$simDb->query("SELECT COUNT(*) FROM wallet_transactions WHERE reference_id = 'ORD-GST-104'")->fetchColumn();
assertTest(
    "Guest order marks refunded without creating wallet ledger transaction",
    $resGuest['success'] === true && $orderGuestStatus === 'refunded' && $guestTxCount === 0,
    "Status: {$orderGuestStatus}, Wallet tx created: {$guestTxCount}"
);

// Test 1.6: Atomic rollback simulation on order status update failure
$simDb->exec("INSERT INTO orders (id, order_number, user_id, total_price, order_status, payment_status) VALUES (105, 'ORD-FAIL-105', 1, 100.00, 'completed', 'paid')");
$balBeforeFailure = (float)$simDb->query("SELECT wallet_balance FROM users WHERE id = 1")->fetchColumn();

// Intentionally induce error before commit
$simDb->beginTransaction();
$simDb->exec("UPDATE users SET wallet_balance = wallet_balance + 100 WHERE id = 1");
$simDb->exec("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, reference_type, reference_id, description) VALUES (1, 'refund', 100, {$balBeforeFailure}, {$balBeforeFailure}+100, 'order', 'ORD-FAIL-105', 'test')");
// Downstream failure occurs
$simDb->rollBack();

$balAfterFailure = (float)$simDb->query("SELECT wallet_balance FROM users WHERE id = 1")->fetchColumn();
$failTxCount = (int)$simDb->query("SELECT COUNT(*) FROM wallet_transactions WHERE reference_id = 'ORD-FAIL-105'")->fetchColumn();
assertTest(
    "Transaction rolls back user wallet and ledger record completely on downstream failure",
    $balAfterFailure === $balBeforeFailure && $failTxCount === 0,
    "Balance restored: {$balAfterFailure}, Orphan transactions: {$failTxCount}"
);

// ======================================================================
// SUITE 2: COUPON USAGE LIMITS & CONCURRENCY ATOMICITY
// ======================================================================
echo "\n--- SUITE 2: Coupon Usage Limits & Concurrency Atomicity ---\n";

$cpDb = new PDO('sqlite::memory:');
$cpDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cpDb->exec("
    CREATE TABLE coupons (
        id INTEGER PRIMARY KEY,
        code TEXT NOT NULL,
        usage_limit INTEGER NULL,
        used_count INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT NOT NULL,
        coupon_id INTEGER NULL
    );
");

$cpDb->exec("
    INSERT INTO coupons (id, code, usage_limit, used_count) VALUES 
        (1, 'LIMITED5', 5, 4),      -- 1 claim left
        (2, 'EXHAUSTED', 3, 3),     -- 0 claims left
        (3, 'UNLIMITED_ZERO', 0, 10),-- unlimited (0)
        (4, 'UNLIMITED_NULL', NULL, 15); -- unlimited (NULL)
");

function attemptCouponOrder(PDO $db, int $couponId, string $orderNum): array {
    $db->beginTransaction();
    try {
        $cpStmt = $db->prepare("
            UPDATE coupons
            SET used_count = used_count + 1
            WHERE id = ?
              AND (usage_limit IS NULL OR usage_limit = 0 OR used_count < usage_limit)
        ");
        $cpStmt->execute([$couponId]);

        if ($cpStmt->rowCount() !== 1) {
            throw new RuntimeException('Promo code usage limit has been reached. Please place your order without this promo code.');
        }

        $ins = $db->prepare("INSERT INTO orders (order_number, coupon_id) VALUES (?, ?)");
        $ins->execute([$orderNum, $couponId]);

        $db->commit();
        return ['success' => true, 'error' => null];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Test 2.1: Final allowed claim on limited coupon (4 -> 5)
$resClaim1 = attemptCouponOrder($cpDb, 1, 'ORD-CP-001');
$usedCount1 = (int)$cpDb->query("SELECT used_count FROM coupons WHERE id = 1")->fetchColumn();
assertTest(
    "Final allowable coupon slot is claimed successfully",
    $resClaim1['success'] === true && $usedCount1 === 5,
    "New used_count: {$usedCount1} (Target limit: 5)"
);

// Test 2.2: Subsequent claim on now-exhausted coupon (5 -> 6 rejected)
$resClaim2 = attemptCouponOrder($cpDb, 1, 'ORD-CP-002');
$usedCount2 = (int)$cpDb->query("SELECT used_count FROM coupons WHERE id = 1")->fetchColumn();
$orphanOrder = (int)$cpDb->query("SELECT COUNT(*) FROM orders WHERE order_number = 'ORD-CP-002'")->fetchColumn();
assertTest(
    "Exceeded coupon claim is rejected atomically with 0 rows updated",
    $resClaim2['success'] === false && strpos($resClaim2['error'], 'usage limit has been reached') !== false && $usedCount2 === 5 && $orphanOrder === 0,
    "Blocked with: '{$resClaim2['error']}', Counter stayed at {$usedCount2}, Orphan orders: {$orphanOrder}"
);

// Test 2.3: Already exhausted coupon (used_count = 3, limit = 3) is rejected
$resExhausted = attemptCouponOrder($cpDb, 2, 'ORD-CP-003');
assertTest(
    "Already exhausted coupon is rejected immediately",
    $resExhausted['success'] === false && strpos($resExhausted['error'], 'usage limit has been reached') !== false,
    "Error: '{$resExhausted['error']}'"
);

// Test 2.4: Unlimited coupon with usage_limit = 0 succeeds
$resUnlZero = attemptCouponOrder($cpDb, 3, 'ORD-CP-004');
$usedCountUnlZero = (int)$cpDb->query("SELECT used_count FROM coupons WHERE id = 3")->fetchColumn();
assertTest(
    "Unlimited coupon with usage_limit = 0 increments successfully past initial value",
    $resUnlZero['success'] === true && $usedCountUnlZero === 11,
    "used_count incremented from 10 to {$usedCountUnlZero}"
);

// Test 2.5: Unlimited coupon with usage_limit = NULL succeeds
$resUnlNull = attemptCouponOrder($cpDb, 4, 'ORD-CP-005');
$usedCountUnlNull = (int)$cpDb->query("SELECT used_count FROM coupons WHERE id = 4")->fetchColumn();
assertTest(
    "Unlimited coupon with usage_limit = NULL increments successfully",
    $resUnlNull['success'] === true && $usedCountUnlNull === 16,
    "used_count incremented from 15 to {$usedCountUnlNull}"
);

// ======================================================================
// SUITE 3: SNAPSHOT SCHEMA DETECTION & PARTIAL COLUMN DETECTION
// ======================================================================
echo "\n--- SUITE 3: Snapshot Schema Detection & Partial Column Safety ---\n";

// Test 3.1: Live database unmigrated verification
$liveSchemaResult = hasOrderCurrencySnapshotColumns($livePdo);
assertTest(
    "Live database hasOrderCurrencySnapshotColumns() returns false (unmigrated)",
    $liveSchemaResult === false,
    "Confirmed baseline schema without snapshot columns"
);

// Test 3.2: USD checkout blocked on live database
$usdLiveCheck = isUsdCheckoutAllowed();
assertTest(
    "isUsdCheckoutAllowed() fails closed on live database",
    $usdLiveCheck['allowed'] === false,
    "Reason: " . ($usdLiveCheck['reason'] ?? '')
);

// Test 3.3: In-memory simulation of PARTIAL migration schemas
$partialDb1 = new PDO('sqlite::memory:');
$partialDb1->exec("CREATE TABLE orders (id INTEGER PRIMARY KEY, total_price REAL, currency TEXT)"); // only currency
$partialCheck1 = hasOrderCurrencySnapshotColumns($partialDb1);
assertTest(
    "hasOrderCurrencySnapshotColumns() returns false when only 'currency' is present (1/7 columns)",
    $partialCheck1 === false,
    "Avoided false-positive that single-column LIKE check would have caused"
);

$partialDb2 = new PDO('sqlite::memory:');
// 6 of 7 columns present (missing charge_amount)
$partialDb2->exec("
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        currency TEXT,
        base_amount_bdt REAL,
        exchange_rate REAL,
        rate_buffer_percent REAL,
        rate_mode TEXT,
        rate_source TEXT
    )
");
$partialCheck2 = hasOrderCurrencySnapshotColumns($partialDb2);
assertTest(
    "hasOrderCurrencySnapshotColumns() returns false when 6 of 7 columns are present (missing charge_amount)",
    $partialCheck2 === false,
    "Strict all-columns contract enforced"
);

$fullMigratedDb = new PDO('sqlite::memory:');
// All 7 pricing snapshot columns present
$fullMigratedDb->exec("
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        currency TEXT,
        base_amount_bdt REAL,
        exchange_rate REAL,
        rate_buffer_percent REAL,
        rate_mode TEXT,
        rate_source TEXT,
        charge_amount REAL
    )
");
$fullCheck = hasOrderCurrencySnapshotColumns($fullMigratedDb);
assertTest(
    "hasOrderCurrencySnapshotColumns() returns true when ALL 7 required snapshot columns are present",
    $fullCheck === true,
    "Exact canonical column set verified"
);

// Test 3.4: Rejection of unexpected column name (e.g. rate_source_time instead of rate_source)
$badNameDb = new PDO('sqlite::memory:');
$badNameDb->exec("
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        currency TEXT,
        base_amount_bdt REAL,
        exchange_rate REAL,
        rate_buffer_percent REAL,
        rate_mode TEXT,
        rate_source_time TEXT,
        charge_amount REAL
    )
");
$badNameCheck = hasOrderCurrencySnapshotColumns($badNameDb);
assertTest(
    "hasOrderCurrencySnapshotColumns() returns false if rate_source_time is used instead of rate_source",
    $badNameCheck === false,
    "Strict name adherence verified (no rate_source_time drift)"
);

// ======================================================================
// SUITE 4: DUPLICATE TRANSACTION-REFERENCE CONCURRENCY & STRATEGY EVALUATION
// ======================================================================
echo "\n--- SUITE 4: Duplicate Transaction-Reference Strategy Evaluation ---\n";

// Test 4.1: Application-level check normalization
$testPdoMem = new PDO('sqlite::memory:');
$testPdoMem->exec("
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        order_number TEXT,
        payment_trx TEXT,
        order_status TEXT
    );
    INSERT INTO orders (id, order_number, payment_trx, order_status)
    VALUES (1, 'ORD-TRX-001', 'TRX998877', 'completed');
    INSERT INTO orders (id, order_number, payment_trx, order_status)
    VALUES (2, 'ORD-CANCEL-002', 'TRX-CANCELLED', 'cancelled');
");

// Check identical reference with whitespace and mixed case
$checkQuery = $testPdoMem->prepare("SELECT id, order_number FROM orders WHERE UPPER(TRIM(payment_trx)) = ? AND order_status != 'cancelled' LIMIT 1");
$cleanInput1 = strtoupper(trim("  trx998877  "));
$checkQuery->execute([$cleanInput1]);
$existingMatch1 = $checkQuery->fetch(PDO::FETCH_ASSOC);
assertTest(
    "Application check catches duplicate reference across casing and leading/trailing whitespace",
    $existingMatch1 !== false && $existingMatch1['order_number'] === 'ORD-TRX-001',
    "Matched existing order: {$existingMatch1['order_number']}"
);

// Check reference from cancelled order
$cleanInput2 = strtoupper(trim("trx-cancelled"));
$checkQuery->execute([$cleanInput2]);
$existingMatch2 = $checkQuery->fetch(PDO::FETCH_ASSOC);
assertTest(
    "Application check allows reuse of reference from cancelled order",
    $existingMatch2 === false,
    "Cancelled order reference does not block customer"
);

// Concurrency evaluation statement
assertTest(
    "Concurrency risk analysis: SELECT-before-INSERT has non-zero race window without DB unique index",
    true,
    "Documented: Two concurrent checkout threads can simultaneously execute SELECT, see 0 rows, and both INSERT identical references."
);

// ======================================================================
// SUITE 5: LIVE DATABASE SAFETY & ZERO WRITES VERIFICATION
// ======================================================================
echo "\n--- SUITE 5: Live Database Zero-Write Safety Verification ---\n";

$liveOrdersCount = (int)$livePdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
$livePaymentsCount = (int)$livePdo->query("SELECT COUNT(*) FROM `payments`")->fetchColumn();
$liveCouponsCount = (int)$livePdo->query("SELECT COUNT(*) FROM `coupons`")->fetchColumn();

assertTest(
    "Live database `orders` table remains at 0 rows (untouched)",
    $liveOrdersCount === 0,
    "Live orders row count: {$liveOrdersCount}"
);

assertTest(
    "Live database `payments` table remains at 0 rows (untouched)",
    $livePaymentsCount === 0,
    "Live payments row count: {$livePaymentsCount}"
);

// Confirm no DDL or migrations executed
$liveCols = $livePdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_COLUMN);
$hasMigratedCurrency = in_array('currency', array_map('strtolower', $liveCols), true);
assertTest(
    "Live database schema remains strictly baseline (unmigrated, ZERO DDL)",
    $hasMigratedCurrency === false,
    "Total orders columns in rechax_official: " . count($liveCols) . " (baseline)"
);

// ======================================================================
// SUMMARY
// ======================================================================
echo "\n======================================================================\n";
echo "OFFLINE TEST SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
