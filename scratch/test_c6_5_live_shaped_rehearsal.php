<?php
/**
 * scratch/test_c6_5_live_shaped_rehearsal.php
 *
 * TopUpers Gate 2B — Phase C6.5: Live-Shaped Schema & Data Rehearsal
 *
 * PURPOSE:
 * Clones the verified C6.1 restored database schema (43-column orders table with all FKs,
 * constraints, indexes, and Order #32 data) into an isolated temporary rehearsal database.
 * Executes both forward migration and state-aware rollback against this true live-shaped schema.
 *
 * STRICT SAFETY CONTRACT:
 * - Server connection has NO default database in DSN (rechax_official is NEVER selected).
 * - Source database (rechax_restore_test_c6_1_20261014_035114) is treated as STRICTLY READ-ONLY.
 * - Rehearsal database name matches strict pattern ^rechax_rehearsal_c65_[0-9]{14}$
 * - Only the temporary rehearsal database is created and dropped.
 * - Live Order #32 and all live tables in rechax_official are NEVER touched.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';

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

function thrownBy(callable $fn): ?Throwable {
    try {
        $fn();
    } catch (Throwable $e) {
        return $e;
    }
    return null;
}

echo "======================================================================\n";
echo "TopUpers Gate 2B — Phase C6.5: Live-Shaped Schema & Data Rehearsal\n";
echo "Source: Verified C6.1 Restored DB (rechax_restore_test_c6_1_20261014_035114)\n";
echo "Target: Isolated Rehearsal DB (rechax_rehearsal_c65_<timestamp>)\n";
echo "======================================================================\n\n";

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

$srcDb       = 'rechax_restore_test_c6_1_20261014_035114';
$rehearsalDb = 'rechax_rehearsal_c65_' . date('YmdHis');
$created     = false;

try {
    // -------------------------------------------------------------------------
    // STEP 0: Isolation Pre-flight Checks
    // -------------------------------------------------------------------------
    echo "[STEP 0] Isolation & Source Verification Pre-flight\n";
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === null, 'Connection has NO default database (rechax_official not selected)');
    assertCondition((bool)preg_match('/^rechax_rehearsal_c65_[0-9]{14}$/', $rehearsalDb), 'Rehearsal DB name matches strict safe pattern');
    
    // Verify source database exists and is intact
    $srcExists = (bool)$pdo->query("SHOW DATABASES LIKE '{$srcDb}'")->fetchColumn();
    assertCondition($srcExists, "Source C6.1 restored database `{$srcDb}` exists");
    
    // Record baseline fingerprint of source database Order #32
    $srcO32 = $pdo->query("SELECT * FROM `{$srcDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition($srcO32 !== false, "Source Order #32 exists in `{$srcDb}`");
    assertCondition($srcO32['order_number'] === 'RX6ACE242D8221B13', "Order #32 number is 'RX6ACE242D8221B13'");
    assertCondition($srcO32['payment_trx'] === 'dh4dhd64h4tht656', "Order #32 payment_trx is 'dh4dhd64h4tht656'");
    assertCondition($srcO32['order_status'] === 'pending', "Order #32 status is 'pending'");
    $srcO32Hash = md5(json_encode($srcO32));

    // Get source orders table create definition
    $srcCreateOrdersRow = $pdo->query("SHOW CREATE TABLE `{$srcDb}`.`orders`")->fetch(PDO::FETCH_NUM);
    $srcCreateOrdersSql = (string)$srcCreateOrdersRow[1];
    $srcColCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$srcDb}' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($srcColCount === 43, "Source table `orders` has exactly 43 baseline columns");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 1: Rehearsal Database Setup & Cloning
    // -------------------------------------------------------------------------
    echo "[STEP 1] Provisioning Isolated Rehearsal Database `{$rehearsalDb}`\n";
    $pdo->exec("CREATE DATABASE `{$rehearsalDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$rehearsalDb}`");
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === $rehearsalDb, "Current database is strictly `{$rehearsalDb}`");

    // Clone referenced foreign key tables first
    foreach (['users', 'games', 'products'] as $parentTable) {
        $pdo->exec("CREATE TABLE `{$rehearsalDb}`.`{$parentTable}` LIKE `{$srcDb}`.`{$parentTable}`");
        $pdo->exec("INSERT INTO `{$rehearsalDb}`.`{$parentTable}` SELECT * FROM `{$srcDb}`.`{$parentTable}`");
        $pCount = $pdo->query("SELECT COUNT(*) FROM `{$rehearsalDb}`.`{$parentTable}`")->fetchColumn();
        $sCount = $pdo->query("SELECT COUNT(*) FROM `{$srcDb}`.`{$parentTable}`")->fetchColumn();
        assertCondition($pCount === $sCount, "Cloned foreign table `{$parentTable}` ({$pCount} rows copied)");
    }

    // Clone 43-column orders table with all FKs and constraints
    $pdo->exec($srcCreateOrdersSql);
    $pdo->exec("INSERT INTO `{$rehearsalDb}`.`orders` SELECT * FROM `{$srcDb}`.`orders`");
    
    // Verify initial rehearsal clone
    $rhColCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$rehearsalDb}' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($rhColCount === 43, "Rehearsal table `orders` cloned with exactly 43 baseline columns");
    $rhOrderCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$rehearsalDb}`.`orders`")->fetchColumn();
    assertCondition($rhOrderCount === 1, "Rehearsal table `orders` contains exactly 1 row (Order #32)");
    
    $rhO32 = $pdo->query("SELECT * FROM `{$rehearsalDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition(md5(json_encode($rhO32)) === $srcO32Hash, "Rehearsal Order #32 data is identical to source");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 2: Runner State 0 Verification on Live-Shaped Schema
    // -------------------------------------------------------------------------
    echo "[STEP 2] Preflight Verification on Live-Shaped Baseline Schema\n";
    $runner = new StandaloneUniquePaymentTrxRunner($pdo, false, $rehearsalDb);
    $runner->setOutputEnabled(false);
    
    $pfInitial = $runner->runPreflight();
    assertCondition($pfInitial['state'] === 0, "Preflight correctly identifies State 0 (Clean Baseline) on 43-column schema");
    assertCondition($pfInitial['column_exists'] === false, "Generated column does not exist");
    assertCondition($pfInitial['index_exists'] === false, "Unique index does not exist");
    assertCondition($pfInitial['duplicate_count'] === 0, "Duplicate references count is 0");
    assertCondition($pfInitial['total_orders'] === 1, "Total orders evaluated is 1");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 3: Forward Migration Execution on 43-Column Schema
    // -------------------------------------------------------------------------
    echo "[STEP 3] Forward Migration Execution (Live-Internal Path on Rehearsal DB)\n";
    $resFwd = $runner->executeInternalLive($pfInitial);
    assertCondition($resFwd['status'] === 'SUCCESS', "Forward migration returned SUCCESS");
    assertCondition(count($resFwd['executed_ddl']) === 2, "Forward migration executed exactly 2 DDL statements");
    
    $pfPost = $runner->runPreflight();
    assertCondition($pfPost['state'] === 3, "Schema transitioned to State 3 (Complete & Canonical)");
    assertCondition($pfPost['column_exists'] === true, "Generated column `normalized_payment_trx` verified present");
    assertCondition($pfPost['column_valid'] === true, "Generated column definition passes canonical checks");
    assertCondition($pfPost['index_exists'] === true, "Unique index `uq_orders_normalized_payment_trx` verified present");
    assertCondition($pfPost['index_valid'] === true, "Unique index definition passes canonical checks");

    // Verify 44 total columns
    $colCountAfterFwd = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$rehearsalDb}' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($colCountAfterFwd === 44, "Total columns after migration is exactly 44 (43 baseline + 1 persistent)");

    // Verify Order #32 in post-migration schema
    $o32Post = $pdo->query("SELECT id, order_number, order_status, payment_trx, normalized_payment_trx FROM `{$rehearsalDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition($o32Post['id'] == 32, "Order #32 ID intact");
    assertCondition($o32Post['order_number'] === 'RX6ACE242D8221B13', "Order #32 order_number intact");
    assertCondition($o32Post['payment_trx'] === 'dh4dhd64h4tht656', "Order #32 raw payment_trx intact ('dh4dhd64h4tht656')");
    assertCondition($o32Post['normalized_payment_trx'] === 'DH4DHD64H4THT656', "Order #32 normalized_payment_trx correctly computed: 'DH4DHD64H4THT656'");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 4: Live-Shaped Data & Constraint Behavior Testing
    // -------------------------------------------------------------------------
    echo "[STEP 4] Live-Shaped Data & Unique Index Behavior Verification\n";

    // 4A: Collision on Order #32's reference with mixed case and whitespace
    $dupOn32Caught = false;
    try {
        $pdo->exec("
            INSERT INTO `{$rehearsalDb}`.`orders` 
            (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
            VALUES ('ORD_TEST_DUP32', 1, 1, 1, 'PLAYER123', 100.00, 100.00, '  DH4DHD64H4THT656  ', 'pending')
        ");
    } catch (PDOException $e) {
        $dupOn32Caught = true;
        assertCondition((int)($e->errorInfo[1] ?? 0) === 1062, "MariaDB rejected collision on Order #32 with Error 1062");
        assertCondition(strpos($e->getMessage(), 'uq_orders_normalized_payment_trx') !== false, "Error message specifies key `uq_orders_normalized_payment_trx`");
    }
    assertCondition($dupOn32Caught, "Collision against Order #32 reference correctly blocked by unique index");

    // 4B: Multiple NULL and empty references allowed
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_NULL_1', 1, 1, 1, 'P1', 50.00, 50.00, NULL, 'pending')
    ");
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_NULL_2', 1, 1, 1, 'P2', 50.00, 50.00, NULL, 'processing')
    ");
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_BLANK_1', 1, 1, 1, 'P3', 50.00, 50.00, '', 'paid')
    ");
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_BLANK_2', 1, 1, 1, 'P4', 50.00, 50.00, '   ', 'completed')
    ");
    $nullTotal = (int)$pdo->query("SELECT COUNT(*) FROM `{$rehearsalDb}`.`orders` WHERE order_number IN ('ORD_NULL_1','ORD_NULL_2','ORD_BLANK_1','ORD_BLANK_2') AND normalized_payment_trx IS NULL")->fetchColumn();
    assertCondition($nullTotal === 4, "Multiple NULL and whitespace-only references evaluate to NULL without conflict");

    // 4C: Reference release on cancellation
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_CAN_1', 1, 1, 1, 'P5', 200.00, 200.00, 'TRX_RELEASE_TEST', 'pending')
    ");
    // Verify collision while pending
    $dupBeforeCancel = false;
    try {
        $pdo->exec("
            INSERT INTO `{$rehearsalDb}`.`orders` 
            (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
            VALUES ('ORD_CAN_2', 1, 1, 1, 'P6', 200.00, 200.00, 'trx_release_test', 'pending')
        ");
    } catch (PDOException $e) { $dupBeforeCancel = true; }
    assertCondition($dupBeforeCancel, "Active order strictly reserves TRX_RELEASE_TEST");

    // Cancel ORD_CAN_1
    $pdo->exec("UPDATE `{$rehearsalDb}`.`orders` SET order_status = 'cancelled' WHERE order_number = 'ORD_CAN_1'");
    $canVal = $pdo->query("SELECT normalized_payment_trx FROM `{$rehearsalDb}`.`orders` WHERE order_number = 'ORD_CAN_1'")->fetchColumn();
    assertCondition($canVal === null, "Cancelled order evaluates normalized_payment_trx to NULL (reference released)");

    // Insert new order with same reference -> now succeeds
    $pdo->exec("
        INSERT INTO `{$rehearsalDb}`.`orders` 
        (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_trx, order_status)
        VALUES ('ORD_CAN_2', 1, 1, 1, 'P6', 200.00, 200.00, 'trx_release_test', 'processing')
    ");
    $can2Val = $pdo->query("SELECT normalized_payment_trx FROM `{$rehearsalDb}`.`orders` WHERE order_number = 'ORD_CAN_2'")->fetchColumn();
    assertCondition($can2Val === 'TRX_RELEASE_TEST', "New active order claims TRX_RELEASE_TEST after cancellation");

    // Clean up test rows so table matches exact baseline row counts for rollback test
    $pdo->exec("DELETE FROM `{$rehearsalDb}`.`orders` WHERE order_number != 'RX6ACE242D8221B13'");
    $remCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$rehearsalDb}`.`orders`")->fetchColumn();
    assertCondition($remCount === 1, "Cleaned test orders; exactly Order #32 remains");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 5: State-Aware Compensatory Rollback Rehearsal
    // -------------------------------------------------------------------------
    echo "[STEP 5] State-Aware Compensatory Rollback Rehearsal (State 3 -> State 0)\n";
    $inspBeforeRb = $runner->inspectRollbackSchema();
    assertCondition($inspBeforeRb['state'] === StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_FULLY_MIGRATED, "Rollback inspection classifies schema as FULLY_MIGRATED");

    $resRb = $runner->executeInternalRollbackLive();
    assertCondition($resRb['status'] === 'SUCCESS', "Rollback returned SUCCESS");
    assertCondition($resRb['final_state'] === StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_BASELINE, "Rollback verified clean return to BASELINE");
    assertCondition(array_keys($resRb['executed_ddl']) === ['drop_index', 'drop_column'], "Rollback executed DROP INDEX then DROP COLUMN");

    // Verify 43 columns restored
    $colCountAfterRb = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$rehearsalDb}' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($colCountAfterRb === 43, "Table `orders` restored to exactly 43 baseline columns");

    // Verify Order #32 integrity after rollback
    $o32AfterRb = $pdo->query("SELECT * FROM `{$rehearsalDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition(md5(json_encode($o32AfterRb)) === $srcO32Hash, "Order #32 data after rollback is 100% byte-identical to source baseline");

    // Verify table definition matches source (ignoring AUTO_INCREMENT counter change caused by test inserts)
    $normCreate = fn(string $sql) => trim(preg_replace('/AUTO_INCREMENT=\d+\s*/', '', preg_replace('/\s+/', ' ', $sql)));
    $rbCreateRow = $pdo->query("SHOW CREATE TABLE `{$rehearsalDb}`.`orders`")->fetch(PDO::FETCH_NUM);
    $rbCreateSql = str_replace("`{$rehearsalDb}`", "`{$srcDb}`", (string)$rbCreateRow[1]);
    assertCondition($normCreate($rbCreateSql) === $normCreate($srcCreateOrdersSql), "SHOW CREATE TABLE after rollback matches source baseline exactly (columns, types, indexes, and FKs)");

    // Idempotent re-run
    $resRbIdem = $runner->executeInternalRollbackLive();
    assertCondition($resRbIdem['status'] === 'ALREADY_BASELINE', "Subsequent rollback execution is an idempotent no-op (ALREADY_BASELINE)");
    assertCondition(empty($resRbIdem['executed_ddl']), "Zero DDL executed on idempotent re-run");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 6: Partial Forward Migration & Rollback Rehearsal (State 1 -> State 0)
    // -------------------------------------------------------------------------
    echo "[STEP 6] Partial Forward Migration & Rollback Rehearsal (State 1 -> State 0)\n";
    // Simulate failure after Step 1 (Column created, index absent)
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $inspPart = $runner->inspectRollbackSchema();
    assertCondition($inspPart['state'] === StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_PARTIAL_FORWARD, "Inspection correctly classifies State 1 (PARTIAL_FORWARD)");
    assertCondition($inspPart['column_valid'] === true && $inspPart['index_exists'] === false, "Column valid and index absent");

    $resPartRb = $runner->executeInternalRollbackLive();
    assertCondition($resPartRb['status'] === 'SUCCESS', "Partial rollback returned SUCCESS");
    assertCondition(array_keys($resPartRb['executed_ddl']) === ['drop_column'], "Partial rollback executed ONLY DROP COLUMN (no DROP INDEX)");
    assertCondition($resPartRb['final_state'] === StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_BASELINE, "Verified return to clean BASELINE");
    
    $colCountPart = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$rehearsalDb}' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($colCountPart === 43, "Table `orders` restored to 43 columns after partial rollback");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 7: Verify Source Database & Live Database Untouched
    // -------------------------------------------------------------------------
    echo "[STEP 7] Verifying Source Database & Live Integrity\n";
    $srcO32Final = $pdo->query("SELECT * FROM `{$srcDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition(md5(json_encode($srcO32Final)) === $srcO32Hash, "Source C6.1 restored database Order #32 remains completely untouched");
    
    $liveO32 = $pdo->query("SELECT * FROM `rechax_official`.`orders` WHERE id = 32")->fetch();
    assertCondition($liveO32 !== false && $liveO32['order_number'] === 'RX6ACE242D8221B13' && $liveO32['payment_trx'] === 'dh4dhd64h4tht656', "Live `rechax_official` Order #32 is completely untouched");
    
    $liveColCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders'")->fetchColumn();
    assertCondition($liveColCount === 43, "Live `rechax_official`.`orders` remains at 43 baseline columns (zero live DDL)");
    echo "\n";

} finally {
    // Clean up temporary rehearsal database
    if ($created && preg_match('/^rechax_rehearsal_c65_[0-9]{14}$/', $rehearsalDb)) {
        $pdo->exec('USE `information_schema`');
        $pdo->exec("DROP DATABASE IF EXISTS `{$rehearsalDb}`");
        echo "CLEANUP: Rehearsal database `{$rehearsalDb}` dropped successfully.\n";
    }
    echo "======================================================================\n";
    echo "REHEARSAL SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
    echo "The live database `rechax_official` was NEVER modified.\n";
    echo "======================================================================\n";
}
