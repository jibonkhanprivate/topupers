<?php
/**
 * scratch/test_c6_4_rollback_mock.php
 *
 * TopUpers Gate 2B â€” Phase C6.4: State-Aware Rollback â€” OFFLINE MOCK TESTS
 *
 * TEST TYPE : PURE MOCKS. NO DATABASE OF ANY KIND IS CONTACTED.
 *             - information_schema is represented by hand-built fixtures (arrays).
 *             - The runner's I/O seams are overridden by an in-memory fake that records DDL
 *               instead of executing it.
 *             - The PDO handed to the runner is a tripwire: any query/exec/prepare/transaction call
 *               on it fails the test immediately.
 *             - db() is never called, so rechax_official is never connected to.
 *
 * What these mocks CAN prove : decision logic, ordering, guard behaviour, idempotency, reporting.
 * What they CANNOT prove     : that real MariaDB returns the fixture row shapes or accepts the DDL.
 *                              That is covered by test_c6_4_rollback_disposable_mariadb.php.
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

/** Runs $fn and returns the Throwable it threw (or null). */
function thrownBy(callable $fn): ?Throwable {
    try {
        $fn();
    } catch (Throwable $e) {
        return $e;
    }
    return null;
}

// ---------------------------------------------------------------------------
// Tripwire PDO: inert handle. Any data/transaction access is a test failure.
// ---------------------------------------------------------------------------
final class TripwirePDO extends PDO {
    public int $touches = 0;
    public function __construct() { parent::__construct('sqlite::memory:'); }
    #[\ReturnTypeWillChange] public function exec(string $statement) { $this->touches++; throw new LogicException('TripwirePDO::exec touched'); }
    #[\ReturnTypeWillChange] public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs) { $this->touches++; throw new LogicException('TripwirePDO::query touched'); }
    #[\ReturnTypeWillChange] public function prepare(string $query, array $options = []) { $this->touches++; throw new LogicException('TripwirePDO::prepare touched'); }
    #[\ReturnTypeWillChange] public function beginTransaction() { $this->touches++; throw new LogicException('TripwirePDO::beginTransaction touched'); }
}

// ---------------------------------------------------------------------------
// Mock runner: overrides every I/O seam.
// ---------------------------------------------------------------------------
final class MockRollbackRunner extends StandaloneUniquePaymentTrxRunner {
    public array $snapshot;
    public string $connectedDb = 'rechax_official';
    public array $calls = [];        // ordered seam calls
    public array $ddlLog = [];       // DDL "executed" (recorded only)
    public ?string $failOnSql = null;
    public bool $inTx = false;
    public int $snapshotReads = 0;
    /** @var callable|null invoked after Nth snapshot read (1-based) with ($this, $n) */
    public $afterSnapshotRead = null;

    public function __construct(array $snapshot, string $connectedDb = 'rechax_official') {
        parent::__construct(new TripwirePDO(), false);   // default expectedDb === 'rechax_official'
        $this->snapshot    = $snapshot;
        $this->connectedDb = $connectedDb;
        $this->setOutputEnabled(false);
    }

    protected function readConnectedDatabaseName(): string {
        $this->calls[] = 'identity';
        return $this->connectedDb;
    }

    protected function readRollbackSnapshot(string $database): array {
        $this->calls[] = 'snapshot';
        $this->snapshotReads++;
        $copy = $this->snapshot;                         // arrays copy by value
        if ($this->afterSnapshotRead) {
            ($this->afterSnapshotRead)($this, $this->snapshotReads);
        }
        return $copy;
    }

    protected function runDdl(string $sql): void {
        $this->calls[] = 'ddl';
        if ($this->failOnSql !== null && $this->failOnSql === $sql) {
            throw new PDOException('simulated DDL failure (e.g. disk full / lock timeout)');
        }
        $this->ddlLog[] = $sql;
        if ($sql === self::getDropIndexSql()) {
            $exists = false;
            foreach ($this->snapshot['indexes'] as $r) {
                if (strtolower($r['INDEX_NAME']) === strtolower(self::INDEX_NAME)) { $exists = true; }
            }
            if (!$exists) {
                // MariaDB error 1091 behaviour: statement fails, nothing executed.
                array_pop($this->ddlLog);
                throw new PDOException("Can't DROP INDEX `uq_orders_normalized_payment_trx`; check that column/key exists");
            }
            $this->snapshot['indexes'] = array_values(array_filter(
                $this->snapshot['indexes'],
                fn($r) => strtolower($r['INDEX_NAME']) !== strtolower(self::INDEX_NAME)
            ));
        } elseif ($sql === self::getDropColumnSql()) {
            $this->snapshot['columns'] = array_values(array_filter(
                $this->snapshot['columns'],
                fn($r) => strtolower($r['COLUMN_NAME']) !== strtolower(self::COLUMN_NAME)
            ));
            // MariaDB also removes the column from any index containing it.
            $this->snapshot['indexes'] = array_values(array_filter(
                $this->snapshot['indexes'],
                fn($r) => strtolower($r['COLUMN_NAME']) !== strtolower(self::COLUMN_NAME)
            ));
        } else {
            throw new LogicException("Unexpected DDL reached the mock: {$sql}");
        }
    }

    protected function applySessionLockTimeout(int $seconds): void {
        $this->calls[] = 'session:' . $seconds;
    }

    protected function isInTransaction(): bool {
        return $this->inTx;
    }
}

// ---------------------------------------------------------------------------
// Fixtures (information_schema row shapes as returned by MariaDB 10.4)
// ---------------------------------------------------------------------------
function fxCol(string $name, string $type, string $dataType, ?int $len, string $nullable = 'NO', string $extra = ''): array {
    return [
        'COLUMN_NAME' => $name, 'DATA_TYPE' => $dataType, 'CHARACTER_MAXIMUM_LENGTH' => $len,
        'COLUMN_TYPE' => $type, 'IS_NULLABLE' => $nullable, 'COLUMN_DEFAULT' => null, 'EXTRA' => $extra,
        'COLLATION_NAME' => ($dataType === 'varchar') ? 'utf8mb4_unicode_ci' : null,
        'GENERATION_EXPRESSION' => '', 'COLUMN_COMMENT' => '',
    ];
}

function fxCanonicalExpression(): string {
    return "case when `order_status` = 'cancelled' then NULL when `payment_trx` is null then NULL "
         . "when trim(`payment_trx`) = '' then NULL else ucase(trim(`payment_trx`)) end";
}

function fxGeneratedCol(array $override = []): array {
    return array_merge(
        fxCol('normalized_payment_trx', 'varchar(100)', 'varchar', 100, 'YES', 'STORED GENERATED'),
        ['GENERATION_EXPRESSION' => fxCanonicalExpression()],
        $override
    );
}

function fxIdx(string $name, string $col, int $nonUnique = 0, int $seq = 1, $sub = null, string $type = 'BTREE'): array {
    return ['INDEX_NAME' => $name, 'NON_UNIQUE' => $nonUnique, 'SEQ_IN_INDEX' => $seq, 'COLUMN_NAME' => $col, 'SUB_PART' => $sub, 'INDEX_TYPE' => $type];
}

function fxSnapshot(bool $withColumn, bool $withIndex, array $colOverride = [], array $extra = []): array {
    $columns = [
        fxCol('id', 'int(11)', 'int', null, 'NO', 'auto_increment'),
        fxCol('order_number', 'varchar(50)', 'varchar', 50),
        fxCol('order_status', 'varchar(20)', 'varchar', 20),
        fxCol('payment_trx', 'varchar(100)', 'varchar', 100, 'YES'),
    ];
    if ($withColumn) {
        $columns[] = fxGeneratedCol($colOverride);
    }
    $indexes = [
        fxIdx('PRIMARY', 'id'),
        fxIdx('order_number', 'order_number'),
    ];
    if ($withIndex) {
        $indexes[] = fxIdx('uq_orders_normalized_payment_trx', 'normalized_payment_trx');
    }
    return array_merge([
        'database'     => 'rechax_official',
        'table'        => ['TABLE_NAME' => 'orders', 'ENGINE' => 'InnoDB', 'ROW_FORMAT' => 'Dynamic', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
        'columns'      => $columns,
        'indexes'      => $indexes,
        'foreign_keys' => [],
        'triggers'     => [],
        'views'        => [],
    ], $extra);
}

const RB_BASELINE = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_BASELINE;
const RB_PARTIAL  = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_PARTIAL_FORWARD;
const RB_FULL     = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_FULLY_MIGRATED;
const RB_UNEXP    = StandaloneUniquePaymentTrxRunner::ROLLBACK_STATE_UNEXPECTED;

echo "======================================================================\n";
echo "TopUpers Gate 2B â€” Phase C6.4 Rollback Hardening: OFFLINE MOCK TESTS\n";
echo "Type: MOCKS ONLY (fixtures + tripwire PDO). No database contacted.\n";
echo "======================================================================\n\n";

// ===========================================================================
// SUITE M1: Pure classification of the three expected states
// ===========================================================================
echo "[M1] Classification of expected schema states (pure function)\n";

$base = StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot(fxSnapshot(false, false));
assertCondition($base['state'] === RB_BASELINE, 'Baseline (no column, no index) => BASELINE');
assertCondition($base['ddl'] === [], 'Baseline plans zero DDL');
assertCondition($base['problems'] === [], 'Baseline reports no problems');

$part = StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot(fxSnapshot(true, false));
assertCondition($part['state'] === RB_PARTIAL, 'Canonical column + no index => PARTIAL_FORWARD');
assertCondition($part['column_valid'] === true && $part['index_exists'] === false, 'Partial: column valid, index absent');
assertCondition(array_keys($part['ddl']) === ['drop_column'], 'Partial plans exactly one step: drop_column');

$full = StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot(fxSnapshot(true, true));
assertCondition($full['state'] === RB_FULL, 'Canonical column + canonical index => FULLY_MIGRATED');
assertCondition($full['column_valid'] && $full['index_valid'], 'Full: both definitions verified canonical');
assertCondition(array_keys($full['ddl']) === ['drop_index', 'drop_column'], 'Full plans drop_index BEFORE drop_column');

// Equivalent function spelling (UPPER vs UCASE) and PERSISTENT spelling are accepted.
$alt = StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot(fxSnapshot(true, false, [
    'EXTRA' => 'PERSISTENT',
    'GENERATION_EXPRESSION' => "CASE WHEN `order_status` = 'cancelled' THEN NULL WHEN `payment_trx` IS NULL THEN NULL WHEN TRIM(`payment_trx`) = '' THEN NULL ELSE UPPER(TRIM(`payment_trx`)) END",
]));
assertCondition($alt['state'] === RB_PARTIAL, 'UPPER/UCASE and PERSISTENT/STORED spellings both classify as canonical');
echo "\n";

// ===========================================================================
// SUITE M2: Unexpected schemas => UNEXPECTED, never droppable
// ===========================================================================
echo "[M2] Unexpected schemas are refused (name match alone is never enough)\n";

$unexpected = [
    'column: non-canonical expression (missing cancelled logic)' =>
        fxSnapshot(true, false, ['GENERATION_EXPRESSION' => 'ucase(trim(`payment_trx`))']),
    'column: varchar(50) instead of varchar(100)' =>
        fxSnapshot(true, false, ['COLUMN_TYPE' => 'varchar(50)', 'CHARACTER_MAXIMUM_LENGTH' => 50]),
    'column: VIRTUAL generated, not PERSISTENT' =>
        fxSnapshot(true, false, ['EXTRA' => 'VIRTUAL GENERATED']),
    'column: NOT NULL' =>
        fxSnapshot(true, false, ['IS_NULLABLE' => 'NO']),
    'column: same name but plain (non-generated) column' =>
        fxSnapshot(true, false, ['EXTRA' => '', 'GENERATION_EXPRESSION' => '']),
    'column: collation differs from table' =>
        fxSnapshot(true, false, ['COLLATION_NAME' => 'latin1_swedish_ci']),
    'column: has a comment (not created by this migration)' =>
        fxSnapshot(true, false, ['COLUMN_COMMENT' => 'hand made']),
    'index: orphan index with our name but NO generated column' =>
        fxSnapshot(false, true),
    'index: our name but NON-unique' => (function () {
        $s = fxSnapshot(true, true);
        foreach ($s['indexes'] as &$r) { if ($r['INDEX_NAME'] === 'uq_orders_normalized_payment_trx') { $r['NON_UNIQUE'] = 1; } }
        return $s;
    })(),
    'index: our name but composite (2 columns)' => (function () {
        $s = fxSnapshot(true, true);
        $s['indexes'][] = fxIdx('uq_orders_normalized_payment_trx', 'order_number', 0, 2);
        return $s;
    })(),
    'index: our name but prefix index' => (function () {
        $s = fxSnapshot(true, true);
        foreach ($s['indexes'] as &$r) { if ($r['INDEX_NAME'] === 'uq_orders_normalized_payment_trx') { $r['SUB_PART'] = 10; } }
        return $s;
    })(),
    'index: our name but covering a DIFFERENT column (order_number)' => (function () {
        $s = fxSnapshot(true, true);
        foreach ($s['indexes'] as &$r) { if ($r['INDEX_NAME'] === 'uq_orders_normalized_payment_trx') { $r['COLUMN_NAME'] = 'order_number'; } }
        return $s;
    })(),
    'index: our name but non-BTREE type' => (function () {
        $s = fxSnapshot(true, true);
        foreach ($s['indexes'] as &$r) { if ($r['INDEX_NAME'] === 'uq_orders_normalized_payment_trx') { $r['INDEX_TYPE'] = 'FULLTEXT'; } }
        return $s;
    })(),
    'dependency: a different index also uses the column (DROP COLUMN would rewrite it)' => (function () {
        $s = fxSnapshot(true, true);
        $s['indexes'][] = fxIdx('idx_someone_elses', 'normalized_payment_trx', 1);
        return $s;
    })(),
    'dependency: foreign key involves the column' =>
        fxSnapshot(true, false, [], ['foreign_keys' => [['CONSTRAINT_NAME' => 'fk_x', 'TABLE_NAME' => 'orders', 'COLUMN_NAME' => 'normalized_payment_trx', 'REFERENCED_TABLE_NAME' => 't', 'REFERENCED_COLUMN_NAME' => 'id']]]),
    'dependency: another generated column depends on it' => (function () {
        $s = fxSnapshot(true, false);
        $s['columns'][] = array_merge(fxCol('other_gen', 'varchar(10)', 'varchar', 10, 'YES', 'STORED GENERATED'), ['GENERATION_EXPRESSION' => 'left(`normalized_payment_trx`,3)']);
        return $s;
    })(),
    'dependency: trigger references the column' =>
        fxSnapshot(true, false, [], ['triggers' => [['TRIGGER_NAME' => 'trg_x', 'ACTION_STATEMENT' => 'SET NEW.foo = NEW.normalized_payment_trx']]]),
    'dependency: view references the column' =>
        fxSnapshot(true, false, [], ['views' => [['TABLE_NAME' => 'v_orders', 'VIEW_DEFINITION' => 'select `orders`.`normalized_payment_trx` from `orders`']]]),
    'table: orders does not exist' =>
        array_merge(fxSnapshot(false, false), ['table' => null]),
    'table: baseline column payment_trx missing' => (function () {
        $s = fxSnapshot(true, false);
        $s['columns'] = array_values(array_filter($s['columns'], fn($c) => $c['COLUMN_NAME'] !== 'payment_trx'));
        return $s;
    })(),
];
foreach ($unexpected as $label => $snap) {
    $r = StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot($snap);
    assertCondition($r['state'] === RB_UNEXP, "UNEXPECTED: {$label}");
    assertCondition($r['ddl'] === [] && !empty($r['problems']), "  -> plans zero DDL and explains why");
}
echo "\n";

// ===========================================================================
// SUITE M3: Identity check ordering; dry-run; guards â€” all with zero DDL
// ===========================================================================
echo "[M3] Identity check first; dry-run and guards never execute DDL\n";

$wrong = new MockRollbackRunner(fxSnapshot(true, true), 'rechax_restore_test_c6_1_20261014_035114');
$e = thrownBy(fn() => $wrong->executeRollback(false));
assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'not exactly') !== false, 'Wrong database => rollback aborts with identity error (dry-run)');
assertCondition($wrong->calls === ['identity'], 'information_schema was NEVER read when identity is wrong');
assertCondition($wrong->ddlLog === [], 'Zero DDL when identity is wrong');

foreach (['rechax_official_copy', 'RECHAX_OFFICIAL', 'rechax_official ', ''] as $badName) {
    $r = new MockRollbackRunner(fxSnapshot(true, true), $badName);
    $e = thrownBy(fn() => $r->inspectRollbackSchema());
    assertCondition($e instanceof RuntimeException && $r->calls === ['identity'], "Identity must be EXACT: '{$badName}' refused before inspection");
}

$wrongLive = new MockRollbackRunner(fxSnapshot(true, true), 'some_other_db');
$e = thrownBy(fn() => $wrongLive->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && $wrongLive->ddlLog === [] && !in_array('snapshot', $wrongLive->calls, true), 'executeInternalRollbackLive refuses a wrong database with zero DDL and no inspection');

$okId = new MockRollbackRunner(fxSnapshot(false, false));
$okId->inspectRollbackSchema();
assertCondition($okId->calls === ['identity', 'snapshot'], 'Correct database: identity check happens BEFORE information_schema snapshot');

$stateSnaps = ['baseline' => fxSnapshot(false, false), 'partial' => fxSnapshot(true, false), 'full' => fxSnapshot(true, true)];
foreach ($stateSnaps as $name => $snap) {
    $dry = new MockRollbackRunner($snap);
    ob_start();
    $dry->setOutputEnabled(true);
    $dry->executeRollback(false);
    $out = (string)ob_get_clean();
    assertCondition($dry->ddlLog === [] && !in_array('ddl', $dry->calls, true), "Dry-run ({$name}): zero DDL");
    assertCondition(strpos($out, 'ROLLBACK ACTION PLAN') !== false, "Dry-run ({$name}): prints the plan");
    assertCondition(strpos($out, 'IDENTITY_CHECK') !== false && strpos($out, 'SCHEMA_INSPECTION') !== false, "Dry-run ({$name}): reports identity check and inspection actions");

    $live = new MockRollbackRunner($snap);
    $e = thrownBy(fn() => $live->executeRollback(true));
    if ($name === 'baseline') {
        assertCondition($e === null && $live->ddlLog === [], "Live-request ({$name}): nothing to do, zero DDL");
    } else {
        assertCondition($e instanceof LogicException && strpos($e->getMessage(), '[SAFETY REFUSAL]') !== false, "Live-request ({$name}): REFUSAL GUARD 1 triggers");
        assertCondition($live->ddlLog === [] && !in_array('ddl', $live->calls, true), "Live-request ({$name}): zero DDL");
    }
}

foreach ($unexpected as $label => $snap) {
    $dry = new MockRollbackRunner($snap);
    $e = thrownBy(fn() => $dry->executeRollback(false));
    if (!($e instanceof RuntimeException) || $dry->ddlLog !== []) {
        assertCondition(false, "Unexpected schema aborts dry-run with zero DDL: {$label}");
    }
}
assertCondition(true, 'All ' . count($unexpected) . ' unexpected schemas abort the dry-run with RuntimeException and zero DDL');
echo "\n";

// ===========================================================================
// SUITE M4: Live-internal rollback logic against the in-memory fake
// ===========================================================================
echo "[M4] executeInternalRollbackLive logic (fake records DDL; nothing executes)\n";

// Fully migrated: ordered, reported.
$m = new MockRollbackRunner(fxSnapshot(true, true));
$res = $m->executeInternalRollbackLive();
assertCondition($res['status'] === 'SUCCESS' && $res['final_state'] === RB_BASELINE, 'Full rollback: SUCCESS, final state BASELINE');
assertCondition($m->ddlLog === [StandaloneUniquePaymentTrxRunner::getDropIndexSql(), StandaloneUniquePaymentTrxRunner::getDropColumnSql()], 'Full rollback: DROP INDEX then DROP COLUMN, exactly two statements');
assertCondition(array_keys($res['executed_ddl']) === ['drop_index', 'drop_column'], 'Full rollback: result reports both executed steps');
$actions = array_column($res['actions'], 'action');
assertCondition(in_array('IDENTITY_CHECK', $actions, true) && in_array('SESSION_SETTING', $actions, true) && in_array('drop_index', $actions, true) && in_array('drop_column', $actions, true) && in_array('POST_VERIFY', $actions, true), 'Full rollback: every action is reported (identity, session setting, both DDL, post-verify)');
$identityCount = count(array_filter($m->calls, fn($c) => $c === 'identity'));
$ddlCount      = count(array_filter($m->calls, fn($c) => $c === 'ddl'));
assertCondition($identityCount >= $ddlCount + 1, 'Identity re-verified before every DDL statement (no stale plan)');
$firstDdlPos = array_search('ddl', $m->calls, true);
assertCondition($firstDdlPos !== false && array_search('snapshot', $m->calls, true) < $firstDdlPos && array_search('identity', $m->calls, true) < $firstDdlPos, 'Inspection precedes the first DDL');
assertCondition(in_array('session:' . StandaloneUniquePaymentTrxRunner::DEFAULT_LOCK_WAIT_TIMEOUT_SECONDS, $m->calls, true), 'Bounded lock_wait_timeout applied before DDL');

// Idempotent: second run is a no-op.
$res2 = $m->executeInternalRollbackLive();
assertCondition($res2['status'] === 'ALREADY_BASELINE' && $res2['executed_ddl'] === [], 'Re-run at baseline: ALREADY_BASELINE, zero DDL');
assertCondition(count($m->ddlLog) === 2, 'Re-run did not add any DDL (idempotent)');
$noopActions = array_column(array_slice($res2['actions'], -3), 'result');
assertCondition(in_array('NOOP', array_column($res2['actions'], 'result'), true), 'No-op is explicitly reported in the action log');

// Partial: only drop column.
$p = new MockRollbackRunner(fxSnapshot(true, false));
$resP = $p->executeInternalRollbackLive();
assertCondition($resP['status'] === 'SUCCESS' && $p->ddlLog === [StandaloneUniquePaymentTrxRunner::getDropColumnSql()], 'Partial-forward: exactly one DDL (DROP COLUMN); no DROP INDEX attempted');

// Baseline: zero DDL, no session setting.
$b = new MockRollbackRunner(fxSnapshot(false, false));
$resB = $b->executeInternalRollbackLive();
assertCondition($resB['status'] === 'ALREADY_BASELINE' && $b->ddlLog === [] && !preg_grep('/^session:/', $b->calls), 'Baseline: zero DDL and no session changes');

// Unexpected: zero DDL, throws.
$u = new MockRollbackRunner(fxSnapshot(true, true, ['COLUMN_TYPE' => 'varchar(50)', 'CHARACTER_MAXIMUM_LENGTH' => 50]));
$e = thrownBy(fn() => $u->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && $u->ddlLog === [] && !preg_grep('/^session:/', $u->calls), 'Unexpected: aborts, zero DDL, no session changes');

// Interrupted rollback: second DDL fails; schema left PARTIAL; re-run resumes with only DROP COLUMN.
$i = new MockRollbackRunner(fxSnapshot(true, true));
$i->failOnSql = StandaloneUniquePaymentTrxRunner::getDropColumnSql();
$e = thrownBy(fn() => $i->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'ROLLBACK DDL FAILURE') !== false, 'Interrupted at DROP COLUMN: failure is reported');
assertCondition(array_column($i->getActionLog(), 'result') && in_array('FAILED', array_column($i->getActionLog(), 'result'), true), 'Failed step is recorded in the action log');
assertCondition($i->ddlLog === [StandaloneUniquePaymentTrxRunner::getDropIndexSql()], 'Only DROP INDEX had completed');
assertCondition(StandaloneUniquePaymentTrxRunner::classifyRollbackSnapshot($i->snapshot)['state'] === RB_PARTIAL, 'Interrupted state is classified PARTIAL_FORWARD (recoverable)');
$i->failOnSql = null;
$resRetry = $i->executeInternalRollbackLive();
assertCondition($resRetry['status'] === 'SUCCESS' && array_keys($resRetry['executed_ddl']) === ['drop_column'], 'Retry resumes with ONLY DROP COLUMN (never re-drops the index)');

// Failure on first DDL leaves state unchanged.
$f = new MockRollbackRunner(fxSnapshot(true, true));
$f->failOnSql = StandaloneUniquePaymentTrxRunner::getDropIndexSql();
$before = $f->snapshot;
$e = thrownBy(fn() => $f->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && $f->snapshot === $before && $f->ddlLog === [], 'Failure on first DDL: schema unchanged, nothing recorded as executed');

// Drift 1: a dependent index appears right AFTER the first inspection. That one validated step (DROP INDEX of
// our own exactly-verified index) is already in flight and cannot be prevented by re-inspection (hence the
// mandatory write-freeze) - but the NEXT inspection must refuse DROP COLUMN.
$d = new MockRollbackRunner(fxSnapshot(true, true));
$d->afterSnapshotRead = function (MockRollbackRunner $r, int $n) {
    if ($n === 1) {
        $r->snapshot['indexes'][] = fxIdx('idx_added_concurrently', 'normalized_payment_trx', 1);
    }
};
$e = thrownBy(fn() => $d->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && $d->ddlLog === [StandaloneUniquePaymentTrxRunner::getDropIndexSql()], 'Dependent index appears after 1st inspection: DROP COLUMN is refused on re-inspection (only the already-validated DROP INDEX ran)');
assertCondition(count(array_filter($d->snapshot['columns'], fn($c) => $c['COLUMN_NAME'] === 'normalized_payment_trx')) === 1, 'Drift 1: generated column still present (never dropped under a new dependency)');

// Drift 2: index vanishes between inspection and DROP INDEX (the unavoidable in-window race) => error 1091 is
// reported as a DDL failure, nothing is mis-recorded, and a retry resumes with DROP COLUMN only.
$d2 = new MockRollbackRunner(fxSnapshot(true, true));
$d2->afterSnapshotRead = function (MockRollbackRunner $r, int $n) {
    if ($n === 1) {
        $r->snapshot['indexes'] = array_values(array_filter($r->snapshot['indexes'], fn($x) => $x['INDEX_NAME'] !== 'uq_orders_normalized_payment_trx'));
    }
};
$e = thrownBy(fn() => $d2->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'ROLLBACK DDL FAILURE') !== false && $d2->ddlLog === [], 'Index vanishes inside the inspect->DDL window: failure reported, nothing recorded as executed');
$d2->afterSnapshotRead = null;
$resD2 = $d2->executeInternalRollbackLive();
assertCondition($resD2['status'] === 'SUCCESS' && $d2->ddlLog === [StandaloneUniquePaymentTrxRunner::getDropColumnSql()], 'Retry after that race issues only DROP COLUMN');

// Open transaction refused (DDL implicitly commits).
$t = new MockRollbackRunner(fxSnapshot(true, true));
$t->inTx = true;
$e = thrownBy(fn() => $t->executeInternalRollbackLive());
assertCondition($e instanceof RuntimeException && strpos($e->getMessage(), 'open transaction') !== false && $t->ddlLog === [] && $t->calls === [], 'Open transaction refused before any inspection or DDL');

// Tripwire: no mock test ever touched the PDO.
$trip = new MockRollbackRunner(fxSnapshot(false, false));
assertCondition(true, 'PDO tripwire armed for every runner above (any DB access would have thrown LogicException)');
echo "\n";

// ===========================================================================
// SUITE M5: Static safety assertions (guards intact; live path unreachable; app unchanged)
// ===========================================================================
echo "[M5] Static safety assertions\n";
$runnerSrc = (string)file_get_contents(__DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php');

assertCondition(strpos($runnerSrc, "defined('STANDALONE_UNIQUE_TRX_AUTHORIZED')") !== false && strpos($runnerSrc, '[SAFETY ENFORCEMENT REFUSAL] Live execution of standalone migration is on hold') !== false, 'FORWARD migration: both refusal guards still present');
assertCondition(strpos($runnerSrc, "defined('STANDALONE_UNIQUE_TRX_ROLLBACK_AUTHORIZED')") !== false && strpos($runnerSrc, '[SAFETY ENFORCEMENT REFUSAL] Live execution of standalone rollback is on hold') !== false, 'ROLLBACK: both refusal guards still present');
assertCondition(!defined('STANDALONE_UNIQUE_TRX_AUTHORIZED') && !defined('STANDALONE_UNIQUE_TRX_ROLLBACK_AUTHORIZED'), 'Authorization constants are NOT defined in this process');
assertCondition(substr_count($runnerSrc, 'executeInternalRollbackLive(') === 1, 'executeInternalRollbackLive() has no callers inside the runner (definition only)');
assertCondition(substr_count($runnerSrc, 'executeInternalLive(') === 1, 'executeInternalLive() has no callers inside the runner (definition only)');
assertCondition(substr_count($runnerSrc, '->runDdl(') === 1, 'runDdl() is invoked from exactly one place (the live-internal rollback loop)');

$cli = substr($runnerSrc, (int)strpos($runnerSrc, '// CLI Invocation Entry Point'));
assertCondition(strpos($cli, 'executeInternal') === false && strpos($cli, 'runDdl') === false, 'CLI entry point cannot reach any live-internal method');

$checkout = (string)file_get_contents(__DIR__ . '/../checkout.php');
$game     = (string)file_get_contents(__DIR__ . '/../game.php');
foreach (['checkout.php' => $checkout, 'game.php' => $game] as $fname => $src) {
    assertCondition(strpos($src, 'uq_orders_normalized_payment_trx') !== false && strpos($src, '1062') !== false, "{$fname}: duplicate-reference (1062 / uq_orders_normalized_payment_trx) handling intact");
}
assertCondition(strpos($checkout, 'This transaction ID has already been submitted for another order.') !== false, 'checkout.php: customer-facing duplicate message intact');

echo "\n======================================================================\n";
echo "MOCK TEST SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED\n";
echo "No database was contacted. rechax_official was NEVER connected to.\n";
echo "======================================================================\n";
