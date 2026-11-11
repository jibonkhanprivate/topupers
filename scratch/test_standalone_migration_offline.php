<?php
/**
 * scratch/test_standalone_migration_offline.php
 *
 * TopUpers Gate 2B — Phase C5.8 Standalone Migration Offline Test Suite
 *
 * Runs strictly against isolated in-memory SQLite instances and mock PDO.
 * NEVER writes to the live rechax_official database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';

$testResults = [];
$totalAssertions = 0;
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
echo "TopUpers Gate 2B — Standalone Migration Offline Test Suite\n";
echo "Target: Isolated In-Memory SQLite Sandboxes Only (Zero Live Writes)\n";
echo "======================================================================\n\n";

// Helper to create an in-memory test table with baseline schema
function createBaseOrdersTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number VARCHAR(50) NOT NULL,
            order_status VARCHAR(20) NOT NULL,
            payment_trx VARCHAR(100) DEFAULT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )
    ");
}

// -----------------------------------------------------------------------------
// Test Suite 1: Canonical SQL Syntax & Clause Verification
// -----------------------------------------------------------------------------
echo "[TEST SUITE 1] Canonical SQL Syntax & Clause Verification\n";

$colSql = StandaloneUniquePaymentTrxRunner::getAddColumnSql();
assertCondition(strpos($colSql, 'ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx`') !== false, "ADD COLUMN targets orders.normalized_payment_trx");
assertCondition(stripos($colSql, 'AFTER') === false, "CRITICAL: ADD COLUMN must NOT contain an AFTER clause (avoids predecessor drift)");
assertCondition(stripos($colSql, 'PERSISTENT') !== false, "ADD COLUMN uses PERSISTENT keyword for MariaDB 10.4");
assertCondition(strpos($colSql, "WHEN `order_status` = 'cancelled' THEN NULL") !== false, "Generated expression evaluates cancelled status to NULL");
assertCondition(strpos($colSql, "WHEN `payment_trx` IS NULL THEN NULL") !== false, "Generated expression evaluates NULL payment_trx to NULL");
assertCondition(strpos($colSql, "WHEN TRIM(`payment_trx`) = '' THEN NULL") !== false, "Generated expression evaluates empty/whitespace payment_trx to NULL");
assertCondition(strpos($colSql, "ELSE UPPER(TRIM(`payment_trx`))") !== false, "Generated expression normalizes active references with UPPER(TRIM)");

$idxSql = StandaloneUniquePaymentTrxRunner::getAddIndexSql();
assertCondition(strpos($idxSql, 'ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`)') !== false, "ADD UNIQUE KEY targets uq_orders_normalized_payment_trx(normalized_payment_trx)");

$rollbackSql = StandaloneUniquePaymentTrxRunner::getRollbackSql();
assertCondition(count($rollbackSql) === 2, "Compensatory rollback provides exactly 2 statements");
assertCondition(strpos($rollbackSql[0], 'DROP INDEX `uq_orders_normalized_payment_trx`') !== false, "Rollback Step 1 drops unique index first");
assertCondition(strpos($rollbackSql[1], 'DROP COLUMN `normalized_payment_trx`') !== false, "Rollback Step 2 drops generated column second");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 2: State 0 (Clean Baseline) in Isolated Sandbox
// -----------------------------------------------------------------------------
echo "[TEST SUITE 2] State 0 (Clean Baseline Detection)\n";
$pdoState0 = new PDO('sqlite::memory:');
$pdoState0->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoState0);

$runner0 = new StandaloneUniquePaymentTrxRunner($pdoState0, false);
$pf0 = $runner0->runPreflight();
assertCondition($pf0['state'] === 0, "Preflight correctly identifies State 0 (Clean Baseline)");
assertCondition($pf0['column_exists'] === false, "State 0: column_exists is false");
assertCondition($pf0['index_exists'] === false, "State 0: index_exists is false");
assertCondition($pf0['duplicate_count'] === 0, "State 0: duplicate_count is 0");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 3: State 1 (Partial Forward) in Isolated Sandbox
// -----------------------------------------------------------------------------
echo "[TEST SUITE 3] State 1 (Partial Forward Detection)\n";
$pdoState1 = new PDO('sqlite::memory:');
$pdoState1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoState1);
$pdoState1->exec("ALTER TABLE orders ADD COLUMN normalized_payment_trx VARCHAR(100) DEFAULT NULL");

$runner1 = new StandaloneUniquePaymentTrxRunner($pdoState1, false);
$pf1 = $runner1->runPreflight();
assertCondition($pf1['state'] === 1, "Preflight correctly identifies State 1 (Column present, index absent)");
assertCondition($pf1['column_exists'] === true, "State 1: column_exists is true");
assertCondition($pf1['index_exists'] === false, "State 1: index_exists is false");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 4: State 2 (Anomalous Inconsistency) & Abort
// -----------------------------------------------------------------------------
echo "[TEST SUITE 4] State 2 (Anomalous Inconsistency Detection)\n";
$pdoState2 = new PDO('sqlite::memory:');
$pdoState2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoState2);
$pdoState2->exec("CREATE UNIQUE INDEX uq_orders_normalized_payment_trx ON orders (order_number)");

$runner2 = new StandaloneUniquePaymentTrxRunner($pdoState2, false);
$pf2 = $runner2->runPreflight();
assertCondition($pf2['state'] === 2, "Preflight correctly identifies State 2 (Index present without column)");
$threwState2 = false;
try {
    $runner2->execute();
} catch (RuntimeException $e) {
    $threwState2 = true;
    assertCondition(strpos($e->getMessage(), 'State 2') !== false, "Runner hard-stops on State 2 with descriptive message");
}
assertCondition($threwState2, "Runner aborted execution on State 2");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 5: State 3 (Complete & Canonical) Detection
// -----------------------------------------------------------------------------
echo "[TEST SUITE 5] State 3 (Complete & Canonical Detection)\n";
$pdoState3 = new PDO('sqlite::memory:');
$pdoState3->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoState3);
$pdoState3->exec("ALTER TABLE orders ADD COLUMN normalized_payment_trx VARCHAR(100) DEFAULT NULL");
$pdoState3->exec("CREATE UNIQUE INDEX uq_orders_normalized_payment_trx ON orders (normalized_payment_trx)");

$runner3 = new StandaloneUniquePaymentTrxRunner($pdoState3, false);
$pf3 = $runner3->runPreflight();
assertCondition($pf3['state'] === 3, "Preflight correctly identifies State 3 (Canonical complete)");
assertCondition($pf3['column_exists'] === true, "State 3: column_exists is true");
assertCondition($pf3['index_exists'] === true, "State 3: index_exists is true");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 6: Duplicate Reference Detection Rules & Reservation Policy
// -----------------------------------------------------------------------------
echo "[TEST SUITE 6] Duplicate Reference Detection Rules & Reservation Policy\n";
$pdoDup = new PDO('sqlite::memory:');
$pdoDup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoDup);

// Case 6A: Clean dataset with non-conflicting records
// - NULL references
// - Whitespace-only references
// - Distinct valid references
// - Same reference on cancelled order vs pending order
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD001', 'pending', NULL)");
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD002', 'payment_pending', '')");
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD003', 'paid', '   ')");
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD004', 'processing', 'TRX_ALPHA')");
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD005', 'completed', 'TRX_BETA')");
// Cancelled order with same reference as ORD004: MUST NOT CONFLICT
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD006', 'cancelled', 'trx_alpha')");
// Another cancelled order with same reference: MUST NOT CONFLICT
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD007', 'cancelled', 'TRX_ALPHA')");

$runnerDup = new StandaloneUniquePaymentTrxRunner($pdoDup, false);
$pfDupClean = $runnerDup->runPreflight();
assertCondition($pfDupClean['duplicate_count'] === 0, "Cancelled orders, NULLs, and empty strings do NOT trigger duplicate collision");

// Case 6B: Introduce actual collision between active orders
// Add an active order ('on_hold') with lowercase + padding of TRX_ALPHA
$pdoDup->exec("INSERT INTO orders (order_number, order_status, payment_trx) VALUES ('ORD008', 'on_hold', '  trx_alpha  ')");

$pfDupCollision = $runnerDup->runPreflight();
assertCondition($pfDupCollision['duplicate_count'] === 1, "Duplicate collision detected between ORD004 and ORD008");
$collision = $pfDupCollision['duplicate_sets'][0];
assertCondition($collision['norm_trx'] === 'TRX_ALPHA', "Normalized reference matches UPPER(TRIM): TRX_ALPHA");
assertCondition((int)$collision['cnt'] === 2, "Duplicate count is exactly 2 active orders");

// Verify runner aborts on execute() when duplicates exist
$threwDupAbort = false;
try {
    $runnerDup->execute();
} catch (RuntimeException $e) {
    $threwDupAbort = true;
    assertCondition(strpos($e->getMessage(), 'Cannot proceed while duplicate transaction references exist') !== false, "Runner aborts with duplicate transaction references error");
}
assertCondition($threwDupAbort, "Runner refused execution due to active duplicate collision");

echo "\n";

// -----------------------------------------------------------------------------
// Test Suite 7: Safety Refusal Guards in Execute Mode
// -----------------------------------------------------------------------------
echo "[TEST SUITE 7] Safety Refusal Guards in Execute Mode\n";
$pdoGuard = new PDO('sqlite::memory:');
$pdoGuard->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
createBaseOrdersTable($pdoGuard);

// Runner configured with isExecuteMode = true
$runnerGuard = new StandaloneUniquePaymentTrxRunner($pdoGuard, true);

// Case 7A: Without STANDALONE_UNIQUE_TRX_AUTHORIZED defined
$threwAuthGuard = false;
try {
    $runnerGuard->execute();
} catch (LogicException $e) {
    $threwAuthGuard = true;
    assertCondition(strpos($e->getMessage(), '[SAFETY REFUSAL]') !== false, "Guard 1: Throws LogicException when STANDALONE_UNIQUE_TRX_AUTHORIZED is undefined");
}
assertCondition($threwAuthGuard, "Safety refusal guard 1 successfully triggered");

echo "\n";

// -----------------------------------------------------------------------------
// Final Report
// -----------------------------------------------------------------------------
echo "======================================================================\n";
echo "OFFLINE TEST SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
echo "Isolated database tests verified cleanly. Live database was NEVER touched.\n";
echo "======================================================================\n";
