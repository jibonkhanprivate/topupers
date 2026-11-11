<?php
/**
 * Test Suite for Phase C5.2: Safe Duplicate-Key Error Handling
 *
 * This test suite verifies:
 * 1. Unit testing of the structured exception discrimination logic (Simulated tests).
 * 2. Unrelated integrity constraint discrimination (ensuring non-trx constraints are masked).
 * 3. Atomic rollback behavior preventing partial state persistence (Simulated SQLite transaction tests).
 * 4. Admin order status restoration conflict handling (Simulated tests).
 * 5. Live database zero-write & zero-DDL read-only verification.
 */

$testResults = [];
function testAssert(string $testName, bool $condition, string $detail = '') {
    global $testResults;
    $status = $condition ? 'PASS' : 'FAIL';
    $testResults[] = ['name' => $testName, 'status' => $status, 'detail' => $detail];
    echo sprintf("[%s] %s%s\n", $status, $testName, $detail ? " - $detail" : "");
}

echo "======================================================================\n";
echo "SECTION 1: Structured Exception Discrimination (SIMULATED TESTS)\n";
echo "======================================================================\n";

function handleCheckoutException(Throwable $e, ?PDO $pdo = null): array {
    $rolledBack = false;
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
        $rolledBack = true;
    }

    $error = '';
    if ($e instanceof PDOException) {
        $sqlState   = (string)$e->getCode();
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        $errorMsg   = $e->getMessage();

        if (($sqlState === '23000' || $driverCode === 1062) && stripos($errorMsg, 'uq_orders_normalized_payment_trx') !== false) {
            $error = 'This transaction ID has already been submitted for another order. Please check your TrxID or contact support.';
        } else {
            $error = 'Failed to process order. Please try again.';
        }
    } elseif ($e instanceof RuntimeException) {
        $error = $e->getMessage();
    } else {
        $error = 'Failed to process order. Please try again.';
    }

    return [
        'error' => $error,
        'rolled_back' => $rolledBack
    ];
}

// Helper to create synthetic PDOException with custom errorInfo
function makePdoException(string $message, string $sqlState, int $driverCode): PDOException {
    $e = new PDOException($message);
    $e->errorInfo = [$sqlState, $driverCode, $message];
    // PDOException code is typically the SQLSTATE
    $ref = new ReflectionProperty('Exception', 'code');
    $ref->setAccessible(true);
    $ref->setValue($e, $sqlState);
    return $e;
}

// 1.1: Specific duplicate transaction reference violation
$e1 = makePdoException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'TRX123' for key 'uq_orders_normalized_payment_trx'", '23000', 1062);
$res1 = handleCheckoutException($e1);
testAssert("Duplicate payment_trx mapped to customer-friendly message", 
    $res1['error'] === 'This transaction ID has already been submitted for another order. Please check your TrxID or contact support.',
    $res1['error']
);
testAssert("Customer message contains zero SQL or schema leak", 
    strpos($res1['error'], '23000') === false && strpos($res1['error'], 'uq_orders_normalized_payment_trx') === false
);

// 1.2: Unrelated duplicate key (e.g. order_number collision)
$e2 = makePdoException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'ORD-123' for key 'order_number'", '23000', 1062);
$res2 = handleCheckoutException($e2);
testAssert("Unrelated duplicate key (order_number) is safely masked", 
    $res2['error'] === 'Failed to process order. Please try again.',
    $res2['error']
);

// 1.3: Foreign key constraint failure (SQLSTATE 23000, driverCode 1452)
$e3 = makePdoException("SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`orders`, CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`))", '23000', 1452);
$res3 = handleCheckoutException($e3);
testAssert("Foreign key constraint violation is safely masked", 
    $res3['error'] === 'Failed to process order. Please try again.',
    $res3['error']
);

// 1.4: Lock wait timeout (SQLSTATE HY000, driverCode 1205)
$e4 = makePdoException("SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction", 'HY000', 1205);
$res4 = handleCheckoutException($e4);
testAssert("Lock wait timeout is safely masked", 
    $res4['error'] === 'Failed to process order. Please try again.',
    $res4['error']
);

// 1.5: Domain RuntimeException (e.g. Promo code usage limit)
$e5 = new RuntimeException("Promo code usage limit has been reached. Please place your order without this promo code.");
$res5 = handleCheckoutException($e5);
testAssert("Domain business validation message is preserved verbatim", 
    $res5['error'] === 'Promo code usage limit has been reached. Please place your order without this promo code.',
    $res5['error']
);

// 1.6: Generic Throwable (e.g. LogicException)
$e6 = new LogicException("Unexpected assertion failure");
$res6 = handleCheckoutException($e6);
testAssert("Generic unexpected Throwable is safely masked", 
    $res6['error'] === 'Failed to process order. Please try again.',
    $res6['error']
);

echo "\n======================================================================\n";
echo "SECTION 2: Transaction Rollback & State Isolation (SIMULATED DB TESTS)\n";
echo "======================================================================\n";

$memDb = new PDO('sqlite::memory:');
$memDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$memDb->exec("CREATE TABLE coupons (id INTEGER PRIMARY KEY, used_count INTEGER)");
$memDb->exec("INSERT INTO coupons (id, used_count) VALUES (1, 5)");

$memDb->beginTransaction();
$memDb->exec("UPDATE coupons SET used_count = used_count + 1 WHERE id = 1");

// Simulate duplicate key failure during order insertion
$eDup = makePdoException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'TRX999' for key 'uq_orders_normalized_payment_trx'", '23000', 1062);
$resTx = handleCheckoutException($eDup, $memDb);

$usedCountAfter = (int)$memDb->query("SELECT used_count FROM coupons WHERE id = 1")->fetchColumn();
testAssert("Transaction was rolled back when exception was caught", $resTx['rolled_back'] === true);
testAssert("Coupon increment was undone completely by rollback", $usedCountAfter === 5, "used_count: $usedCountAfter (expected: 5)");
testAssert("Connection is no longer in transaction", $memDb->inTransaction() === false);

echo "\n======================================================================\n";
echo "SECTION 3: Admin Status Restoration Conflict Handling (SIMULATED TESTS)\n";
echo "======================================================================\n";

function handleAdminStatusException(PDOException $e, array $targetOrder): string {
    $sqlState   = (string)$e->getCode();
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    $errorMsg   = $e->getMessage();
    if (($sqlState === '23000' || $driverCode === 1062) && stripos($errorMsg, 'uq_orders_normalized_payment_trx') !== false) {
        return '<div class="rx-alert rx-alert-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Cannot update order: Transaction ID (' . htmlspecialchars($targetOrder['payment_trx'] ?? '', ENT_QUOTES) . ') is already reserved by another active order.</div>';
    } else {
        return '<div class="rx-alert rx-alert-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Database error updating order. Please try again.</div>';
    }
}

$adminOrder = ['order_number' => 'ORD-1001', 'payment_trx' => 'BKASH_TRX_999'];
$adminDupEx = makePdoException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'BKASH_TRX_999' for key 'uq_orders_normalized_payment_trx'", '23000', 1062);
$adminMsg = handleAdminStatusException($adminDupEx, $adminOrder);

testAssert("Admin conflict message clearly identifies duplicate transaction ID", 
    strpos($adminMsg, 'Cannot update order: Transaction ID (BKASH_TRX_999) is already reserved by another active order.') !== false,
    strip_tags($adminMsg)
);
testAssert("Admin conflict message masks raw database constraint details", 
    strpos($adminMsg, 'uq_orders_normalized_payment_trx') === false && strpos($adminMsg, 'SQLSTATE') === false
);

// Unrelated error in admin
$adminUnrelatedEx = makePdoException("SQLSTATE[HY000]: General error: 2006 MySQL server has gone away", 'HY000', 2006);
$adminUnrelatedMsg = handleAdminStatusException($adminUnrelatedEx, $adminOrder);
testAssert("Admin unrelated error is safely masked", 
    strpos($adminUnrelatedMsg, 'Database error updating order. Please try again.') !== false,
    strip_tags($adminUnrelatedMsg)
);

echo "\n======================================================================\n";
echo "SECTION 4: Live Database Zero-Write & Zero-DDL Audit (READ-ONLY CHECK)\n";
echo "======================================================================\n";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$liveDb = db();

$ordersCount = (int)$liveDb->query("SELECT COUNT(*) FROM orders")->fetchColumn();
testAssert("Live database orders table is readable", $ordersCount >= 0, "Orders count: $ordersCount");

$dupStmt = $liveDb->query("
    SELECT UPPER(TRIM(payment_trx)) AS norm_trx, COUNT(*) as cnt 
    FROM orders 
    WHERE payment_trx IS NOT NULL AND TRIM(payment_trx) != '' AND order_status != 'cancelled'
    GROUP BY UPPER(TRIM(payment_trx)) 
    HAVING cnt > 1
");
$dupRows = $dupStmt->fetchAll(PDO::FETCH_ASSOC);
testAssert("Live database has ZERO duplicate transaction references", count($dupRows) === 0, "Duplicate sets: " . count($dupRows));

$colsStmt = $liveDb->query("SHOW COLUMNS FROM orders");
$cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
testAssert("Live database orders column count is exactly 43 (unmigrated, ZERO DDL)", count($cols) === 43, "Total columns: " . count($cols));
testAssert("Column normalized_payment_trx does not exist yet (no DDL)", !in_array('normalized_payment_trx', $cols));

echo "\n======================================================================\n";
echo "SUMMARY\n";
echo "======================================================================\n";
$fails = array_filter($testResults, fn($r) => $r['status'] === 'FAIL');
if (empty($fails)) {
    echo "ALL " . count($testResults) . " TESTS PASSED SUCCESSFULLY.\n";
} else {
    echo count($fails) . " TESTS FAILED!\n";
}
