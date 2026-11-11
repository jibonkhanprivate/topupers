<?php
/**
 * scratch/test_step4h_migration_safety.php
 *
 * Automated Unit Test Suite for Step 4H: Migration Safety Fixes
 * STRICT SAFETY: Uses in-memory SQLite database and isolated temporary journal files.
 * ZERO WRITES TO LIVE DATABASE `rechax_official`.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

echo "======================================================\n";
echo "TEST SUITE: STEP 4H MIGRATION SAFETY FIXES (OFFLINE/MOCK)\n";
echo "======================================================\n\n";

$passes = 0;
$fails = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passes, $fails;
    if ($condition) {
        echo "  [PASS] $desc\n";
        $passes++;
    } else {
        echo "  [FAIL] $desc: $detail\n";
        $fails++;
    }
}

/**
 * Factory helper: creates an isolated in-memory SQLite database representing baseline schema.
 */
function createBaselineTestDb(): PDO {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Baseline orders table with exact baseline ENUM representation
    $pdo->exec('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            payment_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_method "enum(\'bkash\',\'nagad\',\'rocket\',\'wallet\',\'stripe\')" DEFAULT \'bkash\',
            total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            profit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_trx VARCHAR(100) DEFAULT NULL,
            payment_proof VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ');

    // Baseline payments table
    $pdo->exec("
        CREATE TABLE payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            gateway_name VARCHAR(50) NOT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Baseline payment_methods table (4 gateways, NO binance)
    $pdo->exec("
        CREATE TABLE payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            method_key VARCHAR(50) UNIQUE NOT NULL,
            method_label VARCHAR(100) NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0
        );
    ");
    $pdo->exec("
        INSERT INTO payment_methods (method_key, method_label, is_active, sort_order) VALUES
        ('bkash', 'bKash', 1, 1),
        ('nagad', 'Nagad', 1, 2),
        ('rocket', 'Rocket', 1, 3),
        ('wallet', 'Wallet Balance', 1, 4);
    ");

    // Baseline cron_jobs table (NO exchange_rate_sync)
    $pdo->exec("
        CREATE TABLE cron_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_key VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            schedule_expression VARCHAR(50) NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 0,
            last_run_at DATETIME DEFAULT NULL
        );
    ");
    $pdo->exec("
        INSERT INTO cron_jobs (job_key, name, schedule_expression, is_active) VALUES
        ('cleanup_logs', 'Log Cleanup', '0 0 * * *', 1);
    ");

    // Baseline settings table (0 of the 18 new keys)
    $pdo->exec("
        CREATE TABLE settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT
        );
    ");
    $pdo->exec("
        INSERT INTO settings (setting_key, setting_value) VALUES
        ('site_name', 'TopUpers'),
        ('maintenance_mode', '0');
    ");

    return $pdo;
}

function getTempJournalPath(): string {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_journal_' . bin2hex(random_bytes(6)) . '.jsonl';
}

// =========================================================================
// TEST GROUP 1: Fresh Baseline Schema Full Execution
// =========================================================================
echo "--- Group 1: Fresh Baseline Schema Migration ---\n";
$db1 = createBaselineTestDb();
$journal1 = getTempJournalPath();

$runner1 = new Gate2BMigrationRunner($db1, true, $journal1);
$runner1->initJournal();

$result1 = $runner1->executeLiveMigration();

assertTest("Fresh migration returned status SUCCESS", ($result1['status'] ?? '') === 'SUCCESS');
assertTest("Applied DDL includes orders.payment_method", in_array('orders.payment_method -> varchar(50)', $result1['applied_ddl']));
assertTest("Applied DDL includes all 8 orders columns", count(array_filter($result1['applied_ddl'], fn($d) => str_starts_with($d, 'orders.'))) === 9); // 1 modify + 8 add
assertTest("Applied DDL includes all 2 payments columns", count(array_filter($result1['applied_ddl'], fn($d) => str_starts_with($d, 'payments.'))) === 2);
assertTest("Skipped DDL count is 0 on fresh run", count($result1['skipped_ddl']) === 0);
assertTest("Applied seeds includes payment_methods.binance", in_array('payment_methods.binance', $result1['applied_seeds']));
assertTest("Applied seeds includes cron_jobs.exchange_rate_sync", in_array('cron_jobs.exchange_rate_sync', $result1['applied_seeds']));
assertTest("Applied seeds includes all 18 settings", count(array_filter($result1['applied_seeds'], fn($s) => str_starts_with($s, 'settings.'))) === 18);

// Verify actual database state
$postColsOrders = $db1->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC);
$postColNamesOrders = array_column($postColsOrders, 'name');
assertTest("orders table contains 'currency'", in_array('currency', $postColNamesOrders));
assertTest("orders table contains 'base_amount_bdt'", in_array('base_amount_bdt', $postColNamesOrders));
assertTest("orders table contains 'exchange_rate'", in_array('exchange_rate', $postColNamesOrders));
assertTest("orders table contains 'rate_buffer_percent'", in_array('rate_buffer_percent', $postColNamesOrders));
assertTest("orders table contains 'rate_mode'", in_array('rate_mode', $postColNamesOrders));
assertTest("orders table contains 'rate_source'", in_array('rate_source', $postColNamesOrders));
assertTest("orders table contains 'charge_amount'", in_array('charge_amount', $postColNamesOrders));
assertTest("orders table contains 'payment_details'", in_array('payment_details', $postColNamesOrders));

$postColsPayments = $db1->query("PRAGMA table_info(payments)")->fetchAll(PDO::FETCH_ASSOC);
$postColNamesPayments = array_column($postColsPayments, 'name');
assertTest("payments table contains 'currency'", in_array('currency', $postColNamesPayments));
assertTest("payments table contains 'amount_bdt'", in_array('amount_bdt', $postColNamesPayments));

$binanceRow = $db1->query("SELECT * FROM payment_methods WHERE method_key = 'binance'")->fetch(PDO::FETCH_ASSOC);
assertTest("Binance method seeded with is_active = 0", (int)($binanceRow['is_active'] ?? -1) === 0);
assertTest("Binance method seeded with sort_order = 5", (int)($binanceRow['sort_order'] ?? -1) === 5);

$cronRow = $db1->query("SELECT * FROM cron_jobs WHERE job_key = 'exchange_rate_sync'")->fetch(PDO::FETCH_ASSOC);
assertTest("Exchange rate sync cron seeded with is_active = 0", (int)($cronRow['is_active'] ?? -1) === 0);

$settingsCount = (int)$db1->query("SELECT COUNT(*) FROM settings")->fetchColumn();
assertTest("Settings count is 20 (2 baseline + 18 canonical)", $settingsCount === 20);

// Verify journal file entries
$journalLines1 = file($journal1, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$journalSteps1 = array_map(fn($l) => json_decode($l, true)['step_id'] ?? '', $journalLines1);
assertTest("Journal recorded JOURNAL_INIT", in_array('JOURNAL_INIT', $journalSteps1));
assertTest("Journal recorded MIGRATION_START", in_array('MIGRATION_START', $journalSteps1));
assertTest("Journal recorded DDL_MODIFY_orders_payment_method", in_array('DDL_MODIFY_orders_payment_method', $journalSteps1));
assertTest("Journal recorded DDL_ADD_orders_currency", in_array('DDL_ADD_orders_currency', $journalSteps1));
assertTest("Journal recorded SEED_PAYMENT_METHOD_BINANCE", in_array('SEED_PAYMENT_METHOD_BINANCE', $journalSteps1));
assertTest("Journal recorded SEED_CRON_JOB_EXCHANGE_RATE_SYNC", in_array('SEED_CRON_JOB_EXCHANGE_RATE_SYNC', $journalSteps1));
assertTest("Journal recorded MIGRATION_COMPLETE as last entry", end($journalSteps1) === 'MIGRATION_COMPLETE');

@unlink($journal1);

// =========================================================================
// TEST GROUP 2: Already-Canonical Schema Idempotency
// =========================================================================
echo "\n--- Group 2: Already-Canonical Schema Idempotency ---\n";
// Re-run migration runner on the already-migrated database db1
$journal2 = getTempJournalPath();
$runner2 = new Gate2BMigrationRunner($db1, true, $journal2);
$result2 = $runner2->executeLiveMigration();

assertTest("Re-run returned status SUCCESS", ($result2['status'] ?? '') === 'SUCCESS');
assertTest("Applied DDL count is 0 on canonical schema", count($result2['applied_ddl']) === 0);
assertTest("Skipped DDL count is 11 (all columns already canonical)", count($result2['skipped_ddl']) === 11);
assertTest("Applied seeds count is 0 on canonical schema", count($result2['applied_seeds']) === 0);
assertTest("Skipped seeds count is 20 (binance, cron, 18 settings preserved)", count($result2['skipped_seeds']) === 20);

$journalLines2 = file($journal2, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$journalSteps2 = array_map(fn($l) => json_decode($l, true), $journalLines2);
$skipCountDdl = 0;
foreach ($journalSteps2 as $j) {
    if (in_array($j['status'], ['SKIPPED_ALREADY_EXISTS', 'SKIPPED_ALREADY_CANONICAL', 'SKIPPED_PRESERVED_EXISTING'])) {
        $skipCountDdl++;
    }
}
assertTest("Journal contains 31 skip entries (11 DDL + 20 seeds)", $skipCountDdl === 31);
assertTest("Journal recorded MIGRATION_COMPLETE", end($journalSteps2)['step_id'] === 'MIGRATION_COMPLETE');

@unlink($journal2);

// =========================================================================
// TEST GROUP 3: Partially Migrated Schema Resumption
// =========================================================================
echo "\n--- Group 3: Interrupted / Partially Migrated Schema Resumption ---\n";
$db3 = createBaselineTestDb();
// Simulate partial migration: alter payment_method to varchar(50), add 3 orders columns, 1 payments column, seed dormant binance
$db3->exec('CREATE TABLE orders_temp AS SELECT * FROM orders; DROP TABLE orders;');
$db3->exec('
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        payment_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) DEFAULT \'bkash\',
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        profit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_trx VARCHAR(100) DEFAULT NULL,
        payment_proof VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        currency VARCHAR(10) NOT NULL DEFAULT \'BDT\',
        base_amount_bdt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000
    );
');
$db3->exec("ALTER TABLE payments ADD COLUMN currency varchar(10) NOT NULL DEFAULT 'BDT';");
$db3->exec("INSERT INTO payment_methods (method_key, method_label, is_active, sort_order) VALUES ('binance', 'Binance Pay', 0, 5);");
$db3->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('currency_switcher_enabled', '0'), ('binance_pay_id', 'CUSTOM_ADMIN_PAY_ID');");

$journal3 = getTempJournalPath();
$runner3 = new Gate2BMigrationRunner($db3, true, $journal3);
$result3 = $runner3->executeLiveMigration();

assertTest("Resumed migration returned status SUCCESS", ($result3['status'] ?? '') === 'SUCCESS');
assertTest("3 existing orders columns were skipped", count(array_filter($result3['skipped_ddl'], fn($d) => str_contains($d, 'orders.currency') || str_contains($d, 'orders.base_amount_bdt') || str_contains($d, 'orders.exchange_rate'))) === 3);
assertTest("Remaining 5 orders columns were added", count(array_filter($result3['applied_ddl'], fn($d) => str_starts_with($d, 'orders.'))) === 5);
assertTest("1 existing payments column was skipped", in_array('payments.currency (already canonical)', $result3['skipped_ddl']));
assertTest("Remaining 1 payments column was added", in_array('payments.amount_bdt', $result3['applied_ddl']));
assertTest("Pre-existing binance was skipped", in_array('payment_methods.binance (conforming dormant)', $result3['skipped_seeds']));
assertTest("Pre-existing custom admin setting was preserved", in_array('settings.binance_pay_id (preserved)', $result3['skipped_seeds']));

// Verify that the custom setting value was NOT overwritten
$savedPayId = $db3->query("SELECT setting_value FROM settings WHERE setting_key = 'binance_pay_id'")->fetchColumn();
assertTest("Custom admin setting 'binance_pay_id' preserved without overwrite", $savedPayId === 'CUSTOM_ADMIN_PAY_ID');

@unlink($journal3);

// =========================================================================
// TEST GROUP 4: Unexpected Existing Definitions (Fatal Aborts)
// =========================================================================
echo "\n--- Group 4: Unexpected Existing Definitions (Fatal Aborts) ---\n";

// Test 4a: Column currency exists as INTEGER instead of varchar(10)
$db4a = createBaselineTestDb();
$db4a->exec("ALTER TABLE orders ADD COLUMN currency INTEGER NOT NULL DEFAULT 1;");
$journal4a = getTempJournalPath();
$runner4a = new Gate2BMigrationRunner($db4a, true, $journal4a);
$threw4a = false;
$msg4a = '';
try {
    $runner4a->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw4a = true;
    $msg4a = $e->getMessage();
}
assertTest("Type mismatch on orders.currency threw RuntimeException", $threw4a);
assertTest("Exception message notes type mismatch", str_contains($msg4a, "type mismatch"));

// Verify journal recorded failure and NOT completion
$journalLines4a = file($journal4a, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$steps4a = array_map(fn($l) => json_decode($l, true)['step_id'] ?? '', $journalLines4a);
assertTest("Journal recorded MIGRATION_FAILED", in_array('MIGRATION_FAILED', $steps4a));
assertTest("Journal NEVER recorded MIGRATION_COMPLETE on failure", !in_array('MIGRATION_COMPLETE', $steps4a));
@unlink($journal4a);

// Test 4b: Column currency exists with default 'USD' instead of 'BDT'
$db4b = createBaselineTestDb();
$db4b->exec("ALTER TABLE orders ADD COLUMN currency varchar(10) NOT NULL DEFAULT 'USD';");
$journal4b = getTempJournalPath();
$runner4b = new Gate2BMigrationRunner($db4b, true, $journal4b);
$threw4b = false;
$msg4b = '';
try {
    $runner4b->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw4b = true;
    $msg4b = $e->getMessage();
}
assertTest("Default mismatch on orders.currency threw RuntimeException", $threw4b);
assertTest("Exception message notes default mismatch", str_contains($msg4b, "default mismatch"));
@unlink($journal4b);

// Test 4c: Column currency exists with IS_NULLABLE='YES' instead of 'NO'
$db4c = createBaselineTestDb();
$db4c->exec("ALTER TABLE orders ADD COLUMN currency varchar(10) DEFAULT 'BDT';"); // SQLite default nullable is YES
$journal4c = getTempJournalPath();
$runner4c = new Gate2BMigrationRunner($db4c, true, $journal4c);
$threw4c = false;
$msg4c = '';
try {
    $runner4c->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw4c = true;
    $msg4c = $e->getMessage();
}
assertTest("Nullability mismatch on orders.currency threw RuntimeException", $threw4c);
assertTest("Exception message notes nullability mismatch", str_contains($msg4c, "nullability mismatch"));
@unlink($journal4c);

// Test 4d: orders.payment_method outside two-state contract
$db4d = createBaselineTestDb();
$journal4d = getTempJournalPath();
$runner4d = new Gate2BMigrationRunner($db4d, true, $journal4d);
$mockMaps4d = $runner4d->getTableColumnMaps('orders');
$mockMaps4d['by_name']['payment_method']['COLUMN_TYPE'] = 'int(11)';
$runner4d->setMockColumnMaps('orders', $mockMaps4d);

$threw4d = false;
$msg4d = '';
try {
    $runner4d->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw4d = true;
    $msg4d = $e->getMessage();
}
assertTest("Unexpected orders.payment_method type threw RuntimeException", $threw4d);
assertTest("Exception message notes two-state contract violation", str_contains($msg4d, "outside the allowed two-state contract"));
@unlink($journal4d);

// =========================================================================
// TEST GROUP 5: Conflicting Dormant Seeds & Duplicate Seed Keys
// =========================================================================
echo "\n--- Group 5: Conflicting Dormant Seeds & Duplicate Seed Keys ---\n";

// Test 5a: Pre-existing payment_methods 'binance' is active (is_active=1)
$db5a = createBaselineTestDb();
$db5a->exec("INSERT INTO payment_methods (method_key, method_label, is_active, sort_order) VALUES ('binance', 'Binance Pay', 1, 5);");
$journal5a = getTempJournalPath();
$runner5a = new Gate2BMigrationRunner($db5a, true, $journal5a);
$threw5a = false;
$msg5a = '';
try {
    $runner5a->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw5a = true;
    $msg5a = $e->getMessage();
}
assertTest("Pre-existing active binance threw RuntimeException", $threw5a);
assertTest("Exception notes binance is active", str_contains($msg5a, "is active"));
@unlink($journal5a);

// Test 5b: Pre-existing payment_methods 'binance' has divergent sort_order or label
$db5b = createBaselineTestDb();
$db5b->exec("INSERT INTO payment_methods (method_key, method_label, is_active, sort_order) VALUES ('binance', 'Binance Gateway Custom', 0, 99);");
$journal5b = getTempJournalPath();
$runner5b = new Gate2BMigrationRunner($db5b, true, $journal5b);
$threw5b = false;
$msg5b = '';
try {
    $runner5b->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw5b = true;
    $msg5b = $e->getMessage();
}
assertTest("Pre-existing divergent binance threw RuntimeException", $threw5b);
assertTest("Exception notes binance conflicts with canonical dormant definition", str_contains($msg5b, "conflicts with canonical dormant configuration"));
@unlink($journal5b);

// Test 5c: Pre-existing cron_jobs 'exchange_rate_sync' is active (is_active=1)
$db5c = createBaselineTestDb();
$db5c->exec("INSERT INTO cron_jobs (job_key, name, schedule_expression, is_active) VALUES ('exchange_rate_sync', 'Exchange Rate Daily & Auto Sync', '0 */4 * * *', 1);");
$journal5c = getTempJournalPath();
$runner5c = new Gate2BMigrationRunner($db5c, true, $journal5c);
$threw5c = false;
$msg5c = '';
try {
    $runner5c->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw5c = true;
    $msg5c = $e->getMessage();
}
assertTest("Pre-existing active exchange_rate_sync cron threw RuntimeException", $threw5c);
assertTest("Exception notes cron_jobs is active", str_contains($msg5c, "is active"));
@unlink($journal5c);

// Test 5d: Pre-existing cron_jobs 'exchange_rate_sync' has divergent schedule
$db5d = createBaselineTestDb();
$db5d->exec("INSERT INTO cron_jobs (job_key, name, schedule_expression, is_active) VALUES ('exchange_rate_sync', 'Exchange Rate Daily & Auto Sync', '*/5 * * * *', 0);");
$journal5d = getTempJournalPath();
$runner5d = new Gate2BMigrationRunner($db5d, true, $journal5d);
$threw5d = false;
$msg5d = '';
try {
    $runner5d->executeLiveMigration();
} catch (RuntimeException $e) {
    $threw5d = true;
    $msg5d = $e->getMessage();
}
assertTest("Pre-existing divergent cron_jobs schedule threw RuntimeException", $threw5d);
assertTest("Exception notes cron conflicts with canonical definition", str_contains($msg5d, "conflicts with canonical dormant configuration"));
@unlink($journal5d);

// =========================================================================
// TEST GROUP 6: Journal Safety Failures
// =========================================================================
echo "\n--- Group 6: Journal Safety Failures ---\n";

// Test 6a: Journal directory does not exist and is unwritable
$db6a = createBaselineTestDb();
$invalidJournal = 'Z:\\non_existent_drive_folder_12345\\journal.jsonl';
$runner6a = new Gate2BMigrationRunner($db6a, true, $invalidJournal);
$threw6a = false;
$msg6a = '';
try {
    $runner6a->initJournal();
} catch (RuntimeException $e) {
    $threw6a = true;
    $msg6a = $e->getMessage();
}
assertTest("Unwritable journal directory threw RuntimeException", $threw6a);
assertTest("Exception notes journal directory error", str_contains($msg6a, "Journal directory does not exist or is not writable"));

// Test 6b: Calling logJournal without initialized journal path
$db6b = createBaselineTestDb();
$runner6b = new Gate2BMigrationRunner($db6b, true, '');
$threw6b = false;
$msg6b = '';
try {
    $runner6b->logJournal('STEP', 'STATUS');
} catch (RuntimeException $e) {
    $threw6b = true;
    $msg6b = $e->getMessage();
}
assertTest("Logging without journal path threw RuntimeException", $threw6b);
assertTest("Exception notes journal path not initialized", str_contains($msg6b, "Journal path has not been initialized"));

// Test 6c: Post-verification failure prevents MIGRATION_COMPLETE
$db6c = createBaselineTestDb();
$journal6c = getTempJournalPath();
$runner6c = new Gate2BMigrationRunner($db6c, true, $journal6c);
$threw6c = false;
try {
    $runner6c->postVerifyDatabaseState();
} catch (RuntimeException $e) {
    $threw6c = true;
}
assertTest("postVerifyDatabaseState throws when database lacks canonical schema", $threw6c);
@unlink($journal6c);

// =========================================================================
// TEST GROUP 7: Live Safety Guard Verification
// =========================================================================
echo "\n--- Group 7: Live Safety Guard Verification ---\n";

$livePdo = db();
$initialLiveSettings = (int)$livePdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

// Test 7a: execute() with isExecuteMode=true throws LogicException
$runnerLive = new Gate2BMigrationRunner($livePdo, true);
$threwGuard = false;
$guardMsg = '';
try {
    $runnerLive->execute();
} catch (LogicException $e) {
    $threwGuard = true;
    $guardMsg = $e->getMessage();
}
assertTest("execute() in execute mode threw LogicException safety refusal", $threwGuard);
assertTest("Safety refusal message matches exact contract", str_contains($guardMsg, "[SAFETY ENFORCEMENT REFUSAL] Live execution is NOT authorized"));

// Test 7b: executeLiveMigration() directly on live DB without authorization token throws LogicException
$threwLiveDirect = false;
$liveDirectMsg = '';
try {
    $runnerLive->executeLiveMigration();
} catch (LogicException $e) {
    $threwLiveDirect = true;
    $liveDirectMsg = $e->getMessage();
}
assertTest("executeLiveMigration() directly against rechax_official threw LogicException", $threwLiveDirect);
assertTest("Direct refusal message notes writes are blocked", str_contains($liveDirectMsg, "Live database writes against 'rechax_official' are blocked"));

// Test 7c: Verify live database rechax_official remains 100% untouched
$liveOrdersCount = (int)$livePdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$livePaymentsCount = (int)$livePdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$livePmCount = (int)$livePdo->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
$liveCronCount = (int)$livePdo->query("SELECT COUNT(*) FROM cron_jobs")->fetchColumn();
$liveSettingsCount = (int)$livePdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

// Baseline updated: live holds exactly one order (Order #32, created 2026-10-13, present in the C6.1 backup).
$liveO32 = $livePdo->query("SELECT order_number, payment_trx FROM orders WHERE id = 32")->fetch(PDO::FETCH_ASSOC);
assertTest("Live orders count unchanged (1 - Order #32 only)", $liveOrdersCount === 1);
assertTest("Live Order #32 identity and payment reference intact", $liveO32 && $liveO32['order_number'] === 'RX6ACE242D8221B13' && $liveO32['payment_trx'] === 'dh4dhd64h4tht656');
assertTest("Live payments count unchanged (0)", $livePaymentsCount === 0);
assertTest("Live payment_methods count unchanged (4)", $livePmCount === 4);
assertTest("Live cron_jobs count unchanged (5)", $liveCronCount === 5);
assertTest("Live settings count unchanged ({$initialLiveSettings})", $liveSettingsCount === $initialLiveSettings);

$livePmBinance = $livePdo->query("SELECT * FROM payment_methods WHERE method_key = 'binance'")->fetch(PDO::FETCH_ASSOC);
assertTest("Live Binance method remains absent", $livePmBinance === false);

$liveCronSync = $livePdo->query("SELECT * FROM cron_jobs WHERE job_key = 'exchange_rate_sync'")->fetch(PDO::FETCH_ASSOC);
assertTest("Live Exchange Rate Sync cron remains absent", $liveCronSync === false);

// =========================================================================
// SUMMARY
// =========================================================================
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
exit(0);
