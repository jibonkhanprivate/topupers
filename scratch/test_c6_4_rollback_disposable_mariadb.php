<?php
/**
 * scratch/test_c6_4_rollback_disposable_mariadb.php
 *
 * TopUpers Gate 2B — Phase C6.4: State-Aware Rollback — DISPOSABLE MARIADB TESTS
 *
 * TEST TYPE : REAL MariaDB, REAL DDL — but ONLY inside a uniquely named throw-away database
 *             (rechax_disposable_c64_<timestamp>) that this script creates and drops itself.
 *
 * ISOLATION GUARANTEES (all enforced in code below):
 *   - Connects to the SERVER WITHOUT a default database (DSN has no dbname). It never calls db()
 *     and never selects, reads, or alters `rechax_official`.
 *   - Refuses to run if the connection already has a default database.
 *   - Refuses to run if the throw-away name does not match ^rechax_disposable_c64_[0-9]{14}$
 *     or already exists.
 *   - The ONLY DROP DATABASE statement targets that exact generated name.
 *   - The temporary restore database rechax_restore_test_c6_1_* is only observed
 *     (SHOW DATABASES LIKE) before/after; it is never touched.
 *   - The runner's refusal guards are not involved: tests call the guarded-internal methods directly
 *     ONLY against the disposable database (same pattern as C5.9 used for executeInternalLive).
 *
 * Relationship to the mock suite: test_c6_4_rollback_mock.php proves the decision logic with fixtures;
 * this suite proves real MariaDB returns the row shapes the classifier expects and accepts the DDL.
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

const RB_BASELINE = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_BASELINE;
const RB_PARTIAL  = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_PARTIAL_FORWARD;
const RB_FULL     = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_FULLY_MIGRATED;
const RB_UNEXP    = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_UNEXPECTED;

function serverConnection(): PDO {
    // NOTE: no dbname in the DSN => no default database => rechax_official is never connected to.
    return new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
}

function newRunner(PDO $pdo, string $expectedDb): StandaloneUniquePaymentTrxRunner {
    $r = new StandaloneUniquePaymentTrxRunner($pdo, false, $expectedDb);
    $r->setOutputEnabled(false);
    return $r;
}

function tableFingerprint(PDO $pdo): string {
    $row = $pdo->query('SHOW CREATE TABLE `orders`')->fetch(PDO::FETCH_NUM);
    return (string)$row[1];
}

function dataFingerprint(PDO $pdo): string {
    $rows = $pdo->query('SELECT id, order_number, order_status, payment_trx FROM `orders` ORDER BY id')->fetchAll();
    return md5((string)json_encode($rows));
}

function resetOrders(PDO $pdo, bool $seed = true): void {
    $pdo->exec('DROP VIEW IF EXISTS `v_c64_orders`');
    $pdo->exec('DROP TABLE IF EXISTS `orders`');
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
    if ($seed) {
        $ins = $pdo->prepare('INSERT INTO `orders` (order_number, order_status, payment_trx) VALUES (?, ?, ?)');
        foreach ([
            ['ORD1', 'pending',    'TRX_A'],
            ['ORD2', 'completed',  'trx_b'],
            ['ORD3', 'cancelled',  'TRX_A'],   // reuse after cancel
            ['ORD4', 'pending',    null],
            ['ORD5', 'paid',       '   '],
            ['ORD6', 'processing', 'TRX_C'],
        ] as $r) {
            $ins->execute($r);
        }
    }
}

$CANONICAL_EXPR = "CASE WHEN `order_status` = 'cancelled' THEN NULL WHEN `payment_trx` IS NULL THEN NULL WHEN TRIM(`payment_trx`) = '' THEN NULL ELSE UPPER(TRIM(`payment_trx`)) END";

// ---------------------------------------------------------------------------
// Setup & isolation checks
// ---------------------------------------------------------------------------
$pdo    = serverConnection();
$testDb = 'rechax_disposable_c64_' . date('YmdHis');

echo "======================================================================\n";
echo "TopUpers Gate 2B — Phase C6.4 Rollback Hardening: DISPOSABLE MARIADB TESTS\n";
echo "Type: REAL MariaDB, isolated throw-away database `{$testDb}`.\n";
echo "======================================================================\n\n";

$created = false;
try {
    echo "[D0] Isolation safety checks\n";
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === null, 'Server connection has NO default database (rechax_official is not selected)');
    assertCondition((bool)preg_match('/^rechax_disposable_c64_[0-9]{14}$/', $testDb) && $testDb !== 'rechax_official', 'Throw-away database name matches the strict safe pattern');
    $exists = $pdo->query("SHOW DATABASES LIKE '" . str_replace(['_', '%'], ['\\_', '\\%'], $testDb) . "'")->fetchAll();
    assertCondition($exists === [], 'Throw-away database does not pre-exist');

    $restoreBefore = $pdo->query("SHOW DATABASES LIKE 'rechax\\_restore\\_test\\_c6\\_1%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "  [INFO] Temporary restore database(s) present before tests: " . (empty($restoreBefore) ? '(none)' : implode(', ', $restoreBefore)) . "\n";
    $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    echo "  [INFO] Server version: {$version}\n";

    $pdo->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$testDb}`");
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === $testDb, 'Connection is now using only the throw-away database');
    echo "\n";

    // -----------------------------------------------------------------------
    // D1: Identity check — default expected DB is rechax_official
    // -----------------------------------------------------------------------
    echo "[D1] Identity check refuses any database other than rechax_official (zero DDL)\n";
    resetOrders($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());
    $fpFull = tableFingerprint($pdo);
    $dataFull = dataFingerprint($pdo);

    $wrongIdentity = new StandaloneUniquePaymentTrxRunner($pdo);   // DEFAULT expectedDb === 'rechax_official'
    $wrongIdentity->setOutputEnabled(false);
    $e = thrownBy(fn() => $wrongIdentity->executeRollback(false));
    assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), "not exactly 'rechax_official'") !== false, 'Dry-run rollback refuses: connected DB is not exactly rechax_official');
    $e = thrownBy(fn() => $wrongIdentity->executeInternalRollbackLive());
    assertCondition($e instanceof RuntimeException, 'Live-internal rollback refuses on identity as well');
    assertCondition(tableFingerprint($pdo) === $fpFull && $wrongIdentity->getExecutedDdl() === [], 'Table definition untouched; zero DDL executed');
    $actions = array_column($wrongIdentity->getActionLog(), 'action');
    assertCondition(!in_array('SCHEMA_INSPECTION', $actions, true), 'information_schema inspection never ran after failed identity check');
    echo "\n";

    // -----------------------------------------------------------------------
    // D2: Baseline
    // -----------------------------------------------------------------------
    echo "[D2] BASELINE (neither column nor index)\n";
    resetOrders($pdo);
    $fpBaseline = tableFingerprint($pdo);
    $dataBaseline = dataFingerprint($pdo);
    $r = newRunner($pdo, $testDb);
    assertCondition($r->inspectRollbackSchema()['state'] === RB_BASELINE, 'Real information_schema classifies baseline as BASELINE');
    $res = $r->executeInternalRollbackLive();
    assertCondition($res['status'] === 'ALREADY_BASELINE' && $res['executed_ddl'] === [], 'Baseline rollback: zero DDL (ALREADY_BASELINE)');
    assertCondition(tableFingerprint($pdo) === $fpBaseline, 'Baseline table definition unchanged');
    echo "\n";

    // -----------------------------------------------------------------------
    // D3: Partial forward
    // -----------------------------------------------------------------------
    echo "[D3] PARTIAL FORWARD (generated column only)\n";
    resetOrders($pdo);
    $fpBaseline = tableFingerprint($pdo);
    $dataBaseline = dataFingerprint($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $r = newRunner($pdo, $testDb);
    $insp = $r->inspectRollbackSchema();
    assertCondition($insp['state'] === RB_PARTIAL && $insp['column_valid'] === true && $insp['index_exists'] === false, 'Real MariaDB column definition verifies as canonical => PARTIAL_FORWARD');
    $res = $r->executeInternalRollbackLive();
    assertCondition($res['status'] === 'SUCCESS' && array_keys($res['executed_ddl']) === ['drop_column'], 'Partial rollback executes exactly one statement: DROP COLUMN');
    assertCondition(tableFingerprint($pdo) === $fpBaseline, 'Table definition is byte-identical to the pre-migration baseline');
    assertCondition(dataFingerprint($pdo) === $dataBaseline, 'All order rows preserved (data fingerprint identical)');
    $res = $r->executeInternalRollbackLive();
    assertCondition($res['status'] === 'ALREADY_BASELINE' && $res['executed_ddl'] === [], 'Re-run is an idempotent no-op');
    $log = array_column($res['actions'], 'action');
    assertCondition(in_array('drop_column', $log, true) && in_array('POST_VERIFY', $log, true), 'Action log reports the DROP COLUMN and the post-verification');
    echo "\n";

    // -----------------------------------------------------------------------
    // D4: Fully migrated
    // -----------------------------------------------------------------------
    echo "[D4] FULLY MIGRATED (column + unique index)\n";
    resetOrders($pdo);
    $fpBaseline = tableFingerprint($pdo);
    $dataBaseline = dataFingerprint($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());
    $r = newRunner($pdo, $testDb);
    $insp = $r->inspectRollbackSchema();
    assertCondition($insp['state'] === RB_FULL && $insp['column_valid'] && $insp['index_valid'], 'Real MariaDB definitions verify as canonical => FULLY_MIGRATED');
    $res = $r->executeInternalRollbackLive();
    assertCondition($res['status'] === 'SUCCESS' && array_keys($res['executed_ddl']) === ['drop_index', 'drop_column'], 'Full rollback executes DROP INDEX then DROP COLUMN');
    assertCondition($res['final_state'] === RB_BASELINE, 'Final state verified BASELINE from information_schema');
    assertCondition(tableFingerprint($pdo) === $fpBaseline, 'Table definition is byte-identical to the pre-migration baseline');
    assertCondition(dataFingerprint($pdo) === $dataBaseline, 'All order rows preserved (data fingerprint identical)');
    $res2 = $r->executeInternalRollbackLive();
    assertCondition($res2['status'] === 'ALREADY_BASELINE' && $res2['executed_ddl'] === [], 'Second run: idempotent, zero DDL');
    echo "\n";

    // -----------------------------------------------------------------------
    // D5: Unexpected schemas — real MariaDB, abort with zero DDL, nothing dropped
    // -----------------------------------------------------------------------
    echo "[D5] UNEXPECTED schemas abort with zero DDL (nothing is dropped because a name matches)\n";
    $addCanonicalCol = StandaloneUniquePaymentTrxRunner::getAddColumnSql();
    $unexpectedBuilders = [
        'non-canonical expression (no cancelled logic)' => function (PDO $p) {
            $p->exec('ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(100) GENERATED ALWAYS AS (UPPER(TRIM(`payment_trx`))) PERSISTENT');
        },
        'varchar(50) instead of varchar(100)' => function (PDO $p) use ($CANONICAL_EXPR) {
            $p->exec("ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(50) GENERATED ALWAYS AS ({$CANONICAL_EXPR}) PERSISTENT");
        },
        'VIRTUAL generated column' => function (PDO $p) use ($CANONICAL_EXPR) {
            $p->exec("ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(100) GENERATED ALWAYS AS ({$CANONICAL_EXPR}) VIRTUAL");
        },
        'plain (non-generated) column with the same name' => function (PDO $p) {
            $p->exec('ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(100) NULL');
        },
        'canonical column but with a comment' => function (PDO $p) use ($CANONICAL_EXPR) {
            $p->exec("ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(100) GENERATED ALWAYS AS ({$CANONICAL_EXPR}) PERSISTENT COMMENT 'hand made'");
        },
        'canonical column but different character set / collation' => function (PDO $p) use ($CANONICAL_EXPR) {
            $p->exec("ALTER TABLE `orders` ADD COLUMN `normalized_payment_trx` VARCHAR(100) CHARACTER SET latin1 GENERATED ALWAYS AS ({$CANONICAL_EXPR}) PERSISTENT");
        },
        'our index name, NON-unique' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec('ALTER TABLE `orders` ADD KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`)');
        },
        'our index name, composite' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec('ALTER TABLE `orders` ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`, `order_number`)');
        },
        'our index name, prefix index' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec('ALTER TABLE `orders` ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`normalized_payment_trx`(10))');
        },
        'our index name, but on a DIFFERENT column and NO generated column' => function (PDO $p) {
            $p->exec('ALTER TABLE `orders` ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`order_number`)');
        },
        'our index name on a different column while canonical column exists' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec('ALTER TABLE `orders` ADD UNIQUE KEY `uq_orders_normalized_payment_trx` (`order_number`)');
        },
        'canonical column + canonical index + extra index by another name' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());
            $p->exec('ALTER TABLE `orders` ADD KEY `idx_someone_elses` (`normalized_payment_trx`)');
        },
        'canonical column + a view that references it' => function (PDO $p) use ($addCanonicalCol) {
            $p->exec($addCanonicalCol);
            $p->exec('CREATE VIEW `v_c64_orders` AS SELECT `id`, `normalized_payment_trx` FROM `orders`');
        },
    ];
    foreach ($unexpectedBuilders as $label => $build) {
        resetOrders($pdo);
        $build($pdo);
        $fpBefore   = tableFingerprint($pdo);
        $dataBefore = dataFingerprint($pdo);
        $viewBefore = (string)($pdo->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = " . $pdo->quote($testDb))->fetchColumn());

        $runner = newRunner($pdo, $testDb);
        $state  = $runner->inspectRollbackSchema()['state'];
        $err    = thrownBy(fn() => $runner->executeInternalRollbackLive());
        $ok = ($state === RB_UNEXP)
           && ($err instanceof RuntimeException)
           && tableFingerprint($pdo) === $fpBefore
           && dataFingerprint($pdo) === $dataBefore
           && $runner->getExecutedDdl() === []
           && (string)($pdo->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = " . $pdo->quote($testDb))->fetchColumn()) === $viewBefore;
        assertCondition($ok, "UNEXPECTED => aborted, zero DDL, schema+data untouched: {$label}");
    }
    echo "\n";

    // -----------------------------------------------------------------------
    // D6: Interrupted migration via duplicate-key failure; safe rollback AND safe forward retry
    // -----------------------------------------------------------------------
    echo "[D6] Interrupted migration: duplicate-key failure at Step 2, recovery, retry\n";
    resetOrders($pdo, false);
    $ins = $pdo->prepare('INSERT INTO `orders` (order_number, order_status, payment_trx) VALUES (?, ?, ?)');
    $ins->execute(['DUP1', 'pending',    'SAME_TRX']);
    $ins->execute(['DUP2', 'processing', '  same_trx ']);   // collides after normalization
    $fpBaseline   = tableFingerprint($pdo);
    $dataBaseline = dataFingerprint($pdo);

    $fwd = newRunner($pdo, $testDb);
    $pf  = $fwd->runPreflight();
    assertCondition($pf['duplicate_count'] === 1, 'Forward preflight detects the collision BEFORE any DDL');

    // Simulate the operator bypassing preflight: Step 1 succeeds, Step 2 fails with 1062.
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $dupErr = thrownBy(fn() => $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql()));
    assertCondition($dupErr instanceof PDOException && (int)($dupErr->errorInfo[1] ?? 0) === 1062, 'Step 2 fails with MariaDB error 1062 (duplicate entry)');
    assertCondition(strpos($dupErr->getMessage(), 'uq_orders_normalized_payment_trx') !== false, 'Error message names key uq_orders_normalized_payment_trx (checkout handler can still identify it)');
    $rb = newRunner($pdo, $testDb);
    assertCondition($rb->inspectRollbackSchema()['state'] === RB_PARTIAL, 'Failed Step 2 left NO index and the schema is exactly PARTIAL_FORWARD (ALTER failed atomically)');
    assertCondition(dataFingerprint($pdo) === $dataBaseline, 'Data unchanged by the failed ALTER');

    // Path A: roll back.
    $res = $rb->executeInternalRollbackLive();
    assertCondition($res['status'] === 'SUCCESS' && array_keys($res['executed_ddl']) === ['drop_column'], 'Recovery A: state-aware rollback drops only the column');
    assertCondition(tableFingerprint($pdo) === $fpBaseline, 'Recovery A: table definition identical to pre-migration baseline');

    // Path B: fix the data, then retry the FORWARD migration from the partial state.
    resetOrders($pdo, false);
    $ins = $pdo->prepare('INSERT INTO `orders` (order_number, order_status, payment_trx) VALUES (?, ?, ?)');
    $ins->execute(['DUP1', 'pending',   'SAME_TRX']);
    $ins->execute(['DUP2', 'cancelled', 'same_trx']);        // operator-resolved: cancelled releases the reference
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $fwd2 = newRunner($pdo, $testDb);
    $pf2  = $fwd2->runPreflight();
    assertCondition($pf2['state'] === 1 && $pf2['duplicate_count'] === 0, 'Recovery B: after data fix, preflight reports State 1 and zero duplicates');
    $resF = $fwd2->executeInternalLive($pf2);
    assertCondition($resF['status'] === 'SUCCESS' && count($resF['executed_ddl']) === 1, 'Recovery B: forward retry executes ONLY Step 2 (ADD INDEX)');
    assertCondition($fwd2->runPreflight()['state'] === 3, 'Recovery B: schema reaches State 3 (complete)');
    echo "\n";

    // -----------------------------------------------------------------------
    // D7: MariaDB implicit DDL commit
    // -----------------------------------------------------------------------
    echo "[D7] MariaDB implicit DDL commit\n";
    resetOrders($pdo, false);
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO `orders` (order_number, order_status, payment_trx) VALUES ('ORD_IMPLICIT', 'pending', 'TRX_IMPLICIT')");
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());   // implicit COMMIT happens here
    $rollBackThrew = false;
    try { $pdo->rollBack(); } catch (Throwable $t) { $rollBackThrew = true; }
    $persisted = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE order_number = 'ORD_IMPLICIT'")->fetchColumn();
    assertCondition($persisted === 1, 'Row inserted inside a transaction SURVIVED rollBack(): ALTER TABLE committed it implicitly (DDL cannot be rolled back)');
    echo "  [INFO] PDO rollBack() after the DDL " . ($rollBackThrew ? 'threw an exception' : 'did not throw') . "\n";

    // Runner refuses to run DDL with an open transaction.
    $fpPartial = tableFingerprint($pdo);
    $rt = newRunner($pdo, $testDb);
    $pdo->beginTransaction();
    $e = thrownBy(fn() => $rt->executeInternalRollbackLive());
    $pdo->rollBack();
    assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'open transaction') !== false && tableFingerprint($pdo) === $fpPartial, 'Rollback refuses to run DDL while a transaction is open; zero DDL');
    echo "\n";

    // -----------------------------------------------------------------------
    // D8: Metadata-lock contention (stands in for live traffic without a write-freeze)
    // -----------------------------------------------------------------------
    echo "[D8] Metadata-lock contention: bounded wait, clean failure, safe retry\n";
    resetOrders($pdo);
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());
    $fpFull = tableFingerprint($pdo);

    $pdoB = serverConnection();
    $pdoB->exec("USE `{$testDb}`");
    $pdoB->beginTransaction();
    $pdoB->query('SELECT COUNT(*) FROM `orders`')->fetchColumn();   // holds a shared metadata lock until commit

    $rl = newRunner($pdo, $testDb);
    $rl->setLockWaitTimeoutSeconds(1);
    $t0 = microtime(true);
    $e = thrownBy(fn() => $rl->executeInternalRollbackLive());
    $elapsed = microtime(true) - $t0;
    assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'ROLLBACK DDL FAILURE') !== false, 'Open reader transaction blocks DROP INDEX; failure is reported as a DDL failure');
    assertCondition($elapsed < 15.0, sprintf('Wait was bounded by lock_wait_timeout (%.1fs)', $elapsed));
    assertCondition(in_array('FAILED', array_column($rl->getActionLog(), 'result'), true), 'FAILED step recorded in the action log');
    assertCondition(tableFingerprint($pdo) === $fpFull, 'Failed (blocked) DDL changed nothing');

    $pdoB->commit();
    $resR = $rl->executeInternalRollbackLive();
    assertCondition($resR['status'] === 'SUCCESS' && $resR['final_state'] === RB_BASELINE, 'After the lock is released, retry completes to BASELINE');
    $pdoB = null;
    echo "\n";

    // -----------------------------------------------------------------------
    // D9: Informational algorithm / lock-level probe (no pass/fail; documents real behaviour)
    // -----------------------------------------------------------------------
    echo "[D9] INFO ONLY: ALTER algorithm / lock probe on this server (" . $version . ")\n";
    $probe = function (string $label, callable $setup, string $sql) use ($pdo) {
        resetOrders($pdo);
        $setup($pdo);
        try {
            $pdo->exec($sql);
            echo "  [INFO] ACCEPTED : {$label}\n";
        } catch (PDOException $e) {
            echo "  [INFO] REJECTED : {$label} => " . trim(preg_replace('/\s+/', ' ', (string)($e->errorInfo[2] ?? $e->getMessage()))) . "\n";
        }
    };
    $noop     = function (PDO $p) {};
    $withCol  = function (PDO $p) { $p->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql()); };
    $withBoth = function (PDO $p) { $p->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql()); $p->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql()); };
    $probe('ADD COLUMN PERSISTENT ... ALGORITHM=INSTANT',            $noop,     StandaloneUniquePaymentTrxRunner::getAddColumnSql() . ', ALGORITHM=INSTANT');
    $probe('ADD COLUMN PERSISTENT ... ALGORITHM=INPLACE, LOCK=NONE', $noop,     StandaloneUniquePaymentTrxRunner::getAddColumnSql() . ', ALGORITHM=INPLACE, LOCK=NONE');
    $probe('ADD UNIQUE KEY ... ALGORITHM=INPLACE, LOCK=NONE',        $withCol,  StandaloneUniquePaymentTrxRunner::getAddIndexSql() . ', ALGORITHM=INPLACE, LOCK=NONE');
    $probe('DROP INDEX ... ALGORITHM=INPLACE, LOCK=NONE',            $withBoth, StandaloneUniquePaymentTrxRunner::getDropIndexSql() . ', ALGORITHM=INPLACE, LOCK=NONE');
    $probe('DROP COLUMN ... ALGORITHM=INPLACE, LOCK=NONE',           $withCol,  StandaloneUniquePaymentTrxRunner::getDropColumnSql() . ', ALGORITHM=INPLACE, LOCK=NONE');
    echo "  [INFO] The runner itself does NOT pin ALGORITHM/LOCK; the server picks. Treat every step as potentially blocking writes.\n\n";

} finally {
    // Cleanup: the ONLY DROP DATABASE in this script, guarded by the strict name pattern.
    if ($created && preg_match('/^rechax_disposable_c64_[0-9]{14}$/', $testDb) && $testDb !== 'rechax_official') {
        $pdo->exec('USE `information_schema`');
        $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
        $left = $pdo->query("SHOW DATABASES LIKE 'rechax\\_disposable\\_c64\\_%'")->fetchAll(PDO::FETCH_COLUMN);
        echo "CLEANUP: dropped `{$testDb}`. Leftover rechax_disposable_c64_* databases: " . (empty($left) ? '(none)' : implode(', ', $left)) . "\n";
        $restoreAfter = $pdo->query("SHOW DATABASES LIKE 'rechax\\_restore\\_test\\_c6\\_1%'")->fetchAll(PDO::FETCH_COLUMN);
        if (isset($restoreBefore)) {
            echo "CHECK  : temporary restore database untouched: " . ($restoreBefore === $restoreAfter ? 'YES (' . (empty($restoreAfter) ? 'none present' : implode(', ', $restoreAfter)) . ')' : 'NO - MISMATCH') . "\n";
        }
    }
    echo "======================================================================\n";
    echo "DISPOSABLE MARIADB TEST SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED\n";
    echo "rechax_official was never selected, read, or altered by this script.\n";
    echo "======================================================================\n";
}
