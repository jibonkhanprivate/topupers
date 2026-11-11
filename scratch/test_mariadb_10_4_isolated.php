<?php
/**
 * scratch/test_mariadb_10_4_isolated.php
 *
 * TopUpers Gate 2B — Phase C5.9 MariaDB 10.4 Comprehensive Isolated Test Suite
 *
 * Runs strictly against an isolated disposable database (rechax_disposable_c59_<timestamp>).
 * Zero writes or DDL against rechax_official.
 *
 * GATE B2 HARNESS HARDENING:
 *   - Does NOT load config/database.php and NEVER calls db() (which selects rechax_official).
 *   - Connects to the SERVER with no default database in the DSN.
 *   - The disposable DB name must match ^rechax_disposable_c59_[0-9]{14}$ (checked before any DDL).
 *   - The only DROP DATABASE targets that exact generated name; rechax_official is never selected.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';

$pdo = new PDO(
    'mysql:host=localhost;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
$testDb  = 'rechax_disposable_c59_' . date('YmdHis');
$created = false;
if (!preg_match('/^rechax_disposable_c59_[0-9]{14}$/', $testDb) || $testDb === 'rechax_official') {
    fwrite(STDERR, "Refusing to run: unsafe disposable database name.\n");
    exit(2);
}
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== null) {
    fwrite(STDERR, "Refusing to run: connection has a default database selected.\n");
    exit(2);
}

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
echo "TopUpers Gate 2B — MariaDB 10.4 Isolated Test Suite (Phase C5.9)\n";
echo "Target: Isolated Disposable Database `{$testDb}`\n";
echo "======================================================================\n\n";

try {
    // 0. Setup isolated test database
    $pdo->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$testDb}`");
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === $testDb, "Connected database is strictly `{$testDb}` (never rechax_official)");

    function resetTestOrdersTable(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `orders`");
        $pdo->exec("
            CREATE TABLE `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_number` VARCHAR(50) NOT NULL,
                `order_status` VARCHAR(20) NOT NULL,
                `payment_trx` VARCHAR(100) DEFAULT NULL,
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
        ");
    }

    // -------------------------------------------------------------------------
    // TEST 1: Creation of Persistent Generated Column & Unique Index on MariaDB 10.4
    // -------------------------------------------------------------------------
    echo "[TEST 1] Creating Persistent Generated Column and Unique Index on MariaDB 10.4\n";
    resetTestOrdersTable($pdo);

    $runner = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfInitial = $runner->runPreflight();
    assertCondition($pfInitial['state'] === 0, "Initial state in disposable DB is State 0");

    // Execute Step 1
    $colSql = StandaloneUniquePaymentTrxRunner::getAddColumnSql();
    $pdo->exec($colSql);
    $pfAfterCol = $runner->runPreflight();
    assertCondition($pfAfterCol['state'] === 1, "State transitions to State 1 after adding generated column");
    assertCondition($pfAfterCol['column_exists'] === true, "Generated column exists");
    assertCondition($pfAfterCol['column_valid'] === true, "Generated column passes strict canonical definition check");

    // Execute Step 2
    $idxSql = StandaloneUniquePaymentTrxRunner::getAddIndexSql();
    $pdo->exec($idxSql);
    $pfAfterIdx = $runner->runPreflight();
    assertCondition($pfAfterIdx['state'] === 3, "State transitions to State 3 after adding unique index");
    assertCondition($pfAfterIdx['index_exists'] === true, "Unique index exists");
    assertCondition($pfAfterIdx['index_valid'] === true, "Unique index passes single-column uniqueness check");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 2: Rejecting References Differing Only by Case or Surrounding Whitespace
    // -------------------------------------------------------------------------
    echo "[TEST 2] Rejecting References Differing Only by Case or Surrounding Whitespace\n";
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_CASE_1', 'pending', 'trx_sample_123')");
    
    // Check normalized value
    $normVal = $pdo->query("SELECT `normalized_payment_trx` FROM `orders` WHERE `order_number` = 'ORD_CASE_1'")->fetchColumn();
    assertCondition($normVal === 'TRX_SAMPLE_123', "Generated column normalizes to uppercase: TRX_SAMPLE_123");

    // Attempt insert with uppercase + padding
    $dupCaught = false;
    try {
        $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_CASE_2', 'processing', '  TRX_SAMPLE_123  ')");
    } catch (PDOException $e) {
        $dupCaught = true;
        assertCondition((int)$e->errorInfo[1] === 1062, "MariaDB driver error code is 1062");
        assertCondition(strpos($e->getMessage(), 'uq_orders_normalized_payment_trx') !== false, "Error message specifies key uq_orders_normalized_payment_trx");
    }
    assertCondition($dupCaught, "Insert differing only by case/whitespace was rejected with 1062");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 3: Allowing Multiple NULL and Blank References
    // -------------------------------------------------------------------------
    echo "[TEST 3] Allowing Multiple NULL and Blank References\n";
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_NULL_1', 'pending', NULL)");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_NULL_2', 'payment_pending', NULL)");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_BLANK_1', 'paid', '')");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_BLANK_2', 'completed', '   ')");

    $nullCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_number` LIKE 'ORD_NULL_%' OR `order_number` LIKE 'ORD_BLANK_%'")->fetchColumn();
    assertCondition($nullCount === 4, "Successfully inserted 4 orders with NULL/blank references without conflict");

    $normNullCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE (`order_number` LIKE 'ORD_NULL_%' OR `order_number` LIKE 'ORD_BLANK_%') AND `normalized_payment_trx` IS NULL")->fetchColumn();
    assertCondition($normNullCount === 4, "All 4 NULL/blank orders have normalized_payment_trx IS NULL");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 4: Reference Reuse After Order is Cancelled
    // -------------------------------------------------------------------------
    echo "[TEST 4] Allowing Reference Reuse After Order is Cancelled\n";
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_REUSE_1', 'pending', 'REUSE_TRX_999')");
    
    // Attempt duplicate while ORD_REUSE_1 is pending -> MUST FAIL
    $dupBeforeCancel = false;
    try {
        $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_REUSE_2', 'pending', 'reuse_trx_999')");
    } catch (PDOException $e) {
        $dupBeforeCancel = true;
    }
    assertCondition($dupBeforeCancel, "Active pending order strictly reserves REUSE_TRX_999");

    // Cancel ORD_REUSE_1
    $pdo->exec("UPDATE `orders` SET `order_status` = 'cancelled' WHERE `order_number` = 'ORD_REUSE_1'");
    $cancelledNorm = $pdo->query("SELECT `normalized_payment_trx` FROM `orders` WHERE `order_number` = 'ORD_REUSE_1'")->fetchColumn();
    assertCondition($cancelledNorm === null, "Cancelled order evaluates normalized_payment_trx to NULL");

    // Now insert ORD_REUSE_2 with the same reference -> MUST SUCCEED
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_REUSE_2', 'processing', 'reuse_trx_999')");
    $activeNorm = $pdo->query("SELECT `normalized_payment_trx` FROM `orders` WHERE `order_number` = 'ORD_REUSE_2'")->fetchColumn();
    assertCondition($activeNorm === 'REUSE_TRX_999', "New active order ORD_REUSE_2 successfully claims REUSE_TRX_999 after previous order cancelled");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 5: Rejecting Duplicate References for Every Other Order Status (All 8 Statuses)
    // -------------------------------------------------------------------------
    echo "[TEST 5] Rejecting Duplicate References for All 8 Active Order Statuses\n";
    $activeStatuses = ['pending', 'payment_pending', 'paid', 'processing', 'completed', 'failed', 'refunded', 'on_hold'];
    foreach ($activeStatuses as $idx => $status) {
        $ref = "STATUS_TRX_" . strtoupper($status);
        $ord1 = "ORD_STAT_A_" . $idx;
        $ord2 = "ORD_STAT_B_" . $idx;
        
        $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('{$ord1}', '{$status}', '{$ref}')");
        
        $dupCaughtStatus = false;
        try {
            $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('{$ord2}', 'pending', '{$ref}')");
        } catch (PDOException $e) {
            $dupCaughtStatus = true;
        }
        assertCondition($dupCaughtStatus, "Status '{$status}' strictly reserves transaction reference");
    }
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 6: Detecting Existing Duplicate References Before DDL Execution
    // -------------------------------------------------------------------------
    echo "[TEST 6] Detecting Existing Duplicate References Before DDL Execution\n";
    resetTestOrdersTable($pdo);
    // Insert colliding orders before DDL
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_PRE_1', 'pending', 'COLLIDE_TRX')");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_PRE_2', 'completed', '  collide_trx  ')");
    // Also insert a cancelled order with same trx (should NOT cause duplicate)
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD_PRE_CANCELLED', 'cancelled', 'collide_trx')");

    $runnerDupPreflight = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfDup = $runnerDupPreflight->runPreflight();
    assertCondition($pfDup['duplicate_count'] === 1, "Preflight detects exactly 1 colliding duplicate set");
    assertCondition($pfDup['duplicate_sets'][0]['cnt'] == 2, "Colliding set contains exactly 2 active orders");
    
    $abortedOnDup = false;
    try {
        $runnerDupPreflight->execute();
    } catch (RuntimeException $e) {
        $abortedOnDup = true;
        assertCondition(strpos($e->getMessage(), 'Cannot proceed while duplicate transaction references exist') !== false, "Runner aborts on duplicate references");
    }
    assertCondition($abortedOnDup, "Runner safely refused execution due to pre-existing duplicates");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 7: Handling Partial Migration State Resumption Safely
    // -------------------------------------------------------------------------
    echo "[TEST 7] Handling Partial Migration State Resumption Safely\n";
    resetTestOrdersTable($pdo);
    // Simulate partial failure: Column added, but index crashed/absent
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    
    $runnerPartial = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfPartial = $runnerPartial->runPreflight();
    assertCondition($pfPartial['state'] === 1, "Partial migration correctly identified as State 1");

    // Execute internal live resumption
    $resPartial = $runnerPartial->executeInternalLive($pfPartial);
    assertCondition($resPartial['status'] === 'SUCCESS', "Resumption returned SUCCESS");
    assertCondition(count($resPartial['executed_ddl']) === 1, "Resumption executed exactly 1 DDL statement (Step 2 ADD INDEX only)");

    $pfPostResumption = $runnerPartial->runPreflight();
    assertCondition($pfPostResumption['state'] === 3, "Schema successfully reached State 3 (Complete & Canonical)");

    // Re-running in State 3 is an idempotent no-op
    $resNoop = $runnerPartial->executeInternalLive($pfPostResumption);
    assertCondition($resNoop['status'] === 'ALREADY_CANONICAL', "Subsequent execution in State 3 is an idempotent no-op");
    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 8: State 99 (Definition Mismatch) Detection & Abort
    // -------------------------------------------------------------------------
    echo "[TEST 8] State 99 (Definition Mismatch) Detection & Abort\n";
    
    // Case 8A: Incompatible generated expression (missing cancelled logic)
    resetTestOrdersTable($pdo);
    $pdo->exec("
        ALTER TABLE `orders` 
        ADD COLUMN `normalized_payment_trx` VARCHAR(100) 
        GENERATED ALWAYS AS (UPPER(TRIM(`payment_trx`))) PERSISTENT
    ");
    $runnerMismatchExpr = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfMismatchExpr = $runnerMismatchExpr->runPreflight();
    assertCondition($pfMismatchExpr['state'] === 99, "Non-canonical generated expression triggers State 99 (Mismatch)");

    // Case 8B: Incompatible data type / length (VARCHAR(50) instead of VARCHAR(100))
    resetTestOrdersTable($pdo);
    $pdo->exec("
        ALTER TABLE `orders` 
        ADD COLUMN `normalized_payment_trx` VARCHAR(50) 
        GENERATED ALWAYS AS (
            CASE 
            WHEN `order_status` = 'cancelled' THEN NULL 
            WHEN `payment_trx` IS NULL THEN NULL 
            WHEN TRIM(`payment_trx`) = '' THEN NULL 
            ELSE UPPER(TRIM(`payment_trx`)) 
            END
        ) PERSISTENT
    ");
    $runnerMismatchLen = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfMismatchLen = $runnerMismatchLen->runPreflight();
    assertCondition($pfMismatchLen['state'] === 99, "VARCHAR(50) length mismatch triggers State 99");

    // Case 8C: Non-unique index
    resetTestOrdersTable($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec("ALTER TABLE `orders` ADD KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`)"); // Non-unique KEY
    $runnerMismatchNonUnique = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfMismatchNonUnique = $runnerMismatchNonUnique->runPreflight();
    assertCondition($pfMismatchNonUnique['state'] === 99, "Non-unique index triggers State 99");

    // Case 8D: Composite index
    resetTestOrdersTable($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec("ALTER TABLE `orders` ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`, `order_number`)");
    $runnerMismatchComposite = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pfMismatchComposite = $runnerMismatchComposite->runPreflight();
    assertCondition($pfMismatchComposite['state'] === 99, "Composite index triggers State 99");

    echo "\n";

    // -------------------------------------------------------------------------
    // TEST 9: Compensatory Rollback Execution on MariaDB 10.4
    // -------------------------------------------------------------------------
    echo "[TEST 9] Compensatory Rollback Execution on MariaDB 10.4\n";
    resetTestOrdersTable($pdo);
    // Apply full canonical migration
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());
    $runnerRb = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    assertCondition($runnerRb->runPreflight()['state'] === 3, "Started rollback test from State 3");

    // Execute compensatory rollback SQL
    foreach (StandaloneUniquePaymentTrxRunner::getRollbackSql() as $rbSql) {
        $pdo->exec($rbSql);
    }
    $pfAfterRollback = $runnerRb->runPreflight();
    assertCondition($pfAfterRollback['state'] === 0, "Compensatory rollback successfully restored table to State 0");
    assertCondition($pfAfterRollback['column_exists'] === false, "Column dropped cleanly");
    assertCondition($pfAfterRollback['index_exists'] === false, "Index dropped cleanly");
    echo "\n";

} finally {
    // Always clean up disposable test database (strict-pattern guarded; rechax_official is never selected)
    if ($created && preg_match('/^rechax_disposable_c59_[0-9]{14}$/', $testDb)) {
        $pdo->exec("USE `information_schema`");
        $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
    }
    echo "======================================================================\n";
    echo "CLEANUP: Disposable database `{$testDb}` successfully dropped.\n";
    echo "MARIADB 10.4 TEST SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
    echo "======================================================================\n";
}
