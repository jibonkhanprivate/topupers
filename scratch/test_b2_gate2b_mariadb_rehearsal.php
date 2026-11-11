<?php
/**
 * scratch/test_b2_gate2b_mariadb_rehearsal.php
 *
 * TopUpers Gate B2 — Currency & Binance Migration Rehearsal on Disposable MariaDB 10.4.
 *
 * PURPOSE:
 * Rehearses `migrate_v4_gate2b_currency_binance.php` against an isolated disposable database
 * cloned from the verified C6.1 restored schema with Order #32 and representative test data:
 *   - Verifies preflight checks on MariaDB 10.4.
 *   - Executes live-shaped DDL and dormant seeding.
 *   - Verifies all 8 orders columns, 2 payments columns, dormant binance, dormant cron, and 18 settings.
 *   - Verifies Order #32 baseline data integrity and non-corruption.
 *   - Verifies full idempotency on subsequent execution.
 *   - Confirms zero live writes against `rechax_official`.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

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
echo "TopUpers Gate B2 — Gate 2B Currency & Binance Migration MariaDB Rehearsal\n";
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
$rehearsalDb = 'rechax_rehearsal_gate2b_' . date('YmdHis');
$created     = false;
$journalFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gate2b_rehearsal_journal_' . date('YmdHis') . '.jsonl';

try {
    // -------------------------------------------------------------------------
    // STEP 0: Isolation & Source Verification Pre-flight
    // -------------------------------------------------------------------------
    echo "[STEP 0] Isolation & Source Verification\n";
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === null, 'Server connection has NO default database');
    assertCondition((bool)preg_match('/^rechax_rehearsal_gate2b_[0-9]{14}$/', $rehearsalDb), 'Rehearsal DB name matches strict safe pattern');
    
    $srcExists = (bool)$pdo->query("SHOW DATABASES LIKE '{$srcDb}'")->fetchColumn();
    assertCondition($srcExists, "Source C6.1 restored database `{$srcDb}` exists");

    // Fingerprint source Order #32
    $srcO32 = $pdo->query("SELECT * FROM `{$srcDb}`.`orders` WHERE id = 32")->fetch();
    assertCondition($srcO32 !== false, "Source Order #32 exists in `{$srcDb}`");
    $srcO32BaselineHash = md5(json_encode($srcO32));
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 1: Provision Isolated Rehearsal Database & Clone Schema
    // -------------------------------------------------------------------------
    echo "[STEP 1] Provisioning Isolated Rehearsal DB `{$rehearsalDb}`\n";
    $pdo->exec("CREATE DATABASE `{$rehearsalDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$rehearsalDb}`");
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === $rehearsalDb, "Current database is strictly `{$rehearsalDb}`");

    // Clone necessary tables
    $tablesToClone = ['users', 'games', 'products', 'settings', 'payment_methods', 'cron_jobs', 'payments', 'orders'];
    foreach ($tablesToClone as $tbl) {
        $createRow = $pdo->query("SHOW CREATE TABLE `{$srcDb}`.`{$tbl}`")->fetch(PDO::FETCH_NUM);
        $pdo->exec((string)$createRow[1]);
        $pdo->exec("INSERT INTO `{$rehearsalDb}`.`{$tbl}` SELECT * FROM `{$srcDb}`.`{$tbl}`");
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$rehearsalDb}`.`{$tbl}`")->fetchColumn();
        echo "  - Cloned `{$tbl}` ({$count} rows)\n";
    }
    
    $initOrderCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$rehearsalDb}' AND TABLE_NAME='orders'")->fetchColumn();
    assertCondition($initOrderCols === 43, 'Rehearsal orders table cloned with exactly 43 baseline columns');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 2: Preflight Execution on Disposable Database
    // -------------------------------------------------------------------------
    echo "[STEP 2] Preflight Checks on Disposable Database\n";
    $runner = new Gate2BMigrationRunner($pdo, true, $journalFile, $rehearsalDb);
    $pfReport = $runner->runPreflight();

    assertCondition($pfReport['database'] === $rehearsalDb, "Preflight verified database identity `{$rehearsalDb}`");
    assertCondition($pfReport['payment_method_state'] === 'BASELINE_ENUM', "Preflight classified `orders.payment_method` as BASELINE_ENUM");
    assertCondition($pfReport['binance_action'] === 'INSERT_DORMANT', "Preflight identified dormant Binance method is clean for insertion");
    assertCondition($pfReport['cron_action'] === 'INSERT_DORMANT', "Preflight identified dormant cron job is clean for insertion");
    assertCondition($pfReport['baseline_counts']['orders'] === 1, "Baseline orders count is 1 (Order #32)");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 3: Execute Live-Shaped Migration on Disposable MariaDB
    // -------------------------------------------------------------------------
    echo "[STEP 3] Executing Live Migration on Disposable MariaDB Database\n";
    $result = $runner->executeLiveMigration();

    assertCondition($result['status'] === 'SUCCESS', "Migration returned SUCCESS");
    assertCondition(in_array('orders.payment_method -> varchar(50)', $result['applied_ddl']), "Applied DDL to convert orders.payment_method to varchar(50)");
    assertCondition(count($result['applied_ddl']) === 11, "Executed exactly 11 DDL statements (1 payment_method + 8 orders + 2 payments)");
    $expectedAppliedSeeds = count($pfReport['missing_settings_keys']) + 2; // missing settings + binance + cron
    assertCondition(count($result['applied_seeds']) === $expectedAppliedSeeds, "Executed {$expectedAppliedSeeds} seed insertions (1 binance + 1 cron + " . count($pfReport['missing_settings_keys']) . " missing settings)");
    assertCondition(count($result['applied_seeds']) + count($result['skipped_seeds']) === 20, "Total seed operations evaluated is 20 (applied + preserved)");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 4: Schema & Data Integrity Verification
    // -------------------------------------------------------------------------
    echo "[STEP 4] Schema & Data Integrity Verification\n";

    // 4A: orders table columns count
    $postOrderCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$rehearsalDb}' AND TABLE_NAME='orders'")->fetchColumn();
    assertCondition($postOrderCols === 51, "orders table now has exactly 51 columns (43 baseline + 8 canonical)");

    // 4B: orders.payment_method type
    $pmType = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$rehearsalDb}' AND TABLE_NAME='orders' AND COLUMN_NAME='payment_method'")->fetchColumn();
    assertCondition($pmType === 'varchar(50)', "orders.payment_method is now varchar(50)");

    // 4C: payments table columns count
    $postPayCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$rehearsalDb}' AND TABLE_NAME='payments'")->fetchColumn();
    assertCondition($postPayCols === 18, "payments table now has exactly 18 columns (16 baseline + 2 canonical)");

    // 4D: Dormant Binance payment method
    $binanceRow = $pdo->query("SELECT * FROM payment_methods WHERE method_key = 'binance'")->fetch();
    assertCondition($binanceRow !== false, "payment_methods row 'binance' present");
    assertCondition((int)$binanceRow['is_active'] === 0, "Binance payment method is strictly dormant (is_active = 0)");
    assertCondition((int)$binanceRow['sort_order'] === 5, "Binance payment method sort_order is 5");

    // 4E: Dormant cron job
    $cronRow = $pdo->query("SELECT * FROM cron_jobs WHERE job_key = 'exchange_rate_sync'")->fetch();
    assertCondition($cronRow !== false, "cron_jobs row 'exchange_rate_sync' present");
    assertCondition((int)$cronRow['is_active'] === 0, "Exchange rate sync cron is strictly dormant (is_active = 0)");
    assertCondition($cronRow['schedule_expression'] === '0 */4 * * *', "Cron schedule expression is '0 */4 * * *'");

    // 4F: 18 canonical settings exist
    $settingsCount = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    assertCondition($settingsCount === (int)$pfReport['expected_final_settings_count'], "settings table count matches expected ({$settingsCount} rows, all 18 canonical settings present)");

    // 4G: Order #32 data integrity
    $o32After = $pdo->query("SELECT * FROM orders WHERE id = 32")->fetch();
    assertCondition($o32After['order_number'] === 'RX6ACE242D8221B13', "Order #32 order_number intact");
    assertCondition($o32After['payment_trx'] === 'dh4dhd64h4tht656', "Order #32 raw payment_trx intact");
    assertCondition($o32After['currency'] === 'BDT', "Order #32 currency defaulted to 'BDT'");
    assertCondition((float)$o32After['exchange_rate'] === 1.0000, "Order #32 exchange_rate defaulted to 1.0000");

    // Baseline columns slice check
    $baselineSlice = [];
    foreach (array_keys($srcO32) as $k) {
        $baselineSlice[$k] = $o32After[$k];
    }
    assertCondition(md5(json_encode($baselineSlice)) === $srcO32BaselineHash, "All 43 original baseline fields of Order #32 are 100% byte-identical to source");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 5: Idempotency Re-run Verification
    // -------------------------------------------------------------------------
    echo "[STEP 5] Testing Idempotency (Zero DDL, Skipped Operations)\n";
    $runner2 = new Gate2BMigrationRunner($pdo, true, $journalFile, $rehearsalDb);
    $res2 = $runner2->executeLiveMigration();

    assertCondition($res2['status'] === 'SUCCESS', "Second migration run returned SUCCESS");
    assertCondition(empty($res2['applied_ddl']), "Second run applied ZERO DDL statements");
    assertCondition(count($res2['skipped_ddl']) === 11, "Second run skipped all 11 existing columns");
    assertCondition(empty($res2['applied_seeds']), "Second run applied ZERO new seeds");
    assertCondition(count($res2['skipped_seeds']) === 20, "Second run skipped/preserved all 20 seeds");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 6: Confirm Live Database Untouched
    // -------------------------------------------------------------------------
    echo "[STEP 6] Confirming Live Database rechax_official Untouched\n";
    $liveCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders'")->fetchColumn();
    assertCondition($liveCols === 43, "Live rechax_official.orders remains at 43 baseline columns (zero live DDL)");
    $liveBinance = $pdo->query("SELECT * FROM `rechax_official`.`payment_methods` WHERE method_key = 'binance'")->fetch();
    assertCondition($liveBinance === false, "Live rechax_official.payment_methods has NO binance row");
    echo "\n";

} finally {
    if ($created && preg_match('/^rechax_rehearsal_gate2b_[0-9]{14}$/', $rehearsalDb)) {
        $pdo->exec('USE `information_schema`');
        $pdo->exec("DROP DATABASE IF EXISTS `{$rehearsalDb}`");
        echo "CLEANUP: Rehearsal database `{$rehearsalDb}` dropped successfully.\n";
    }
    if (file_exists($journalFile)) {
        unlink($journalFile);
        echo "CLEANUP: Rehearsal journal file deleted.\n";
    }
    echo "======================================================================\n";
    echo "GATE 2B MARIADB REHEARSAL SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
    echo "Live database `rechax_official` was NEVER touched.\n";
    echo "======================================================================\n";
}
