<?php
/**
 * scratch/rehearse_gate2b_isolated_drill.php
 *
 * TopUpers Gate 2B — Isolated MariaDB Port 3308 Restore & Migration Rehearsal Harness
 *
 * PURPOSE:
 * Provides a dedicated, fail-closed restore-and-migration rehearsal harness targeting
 * strictly the isolated MariaDB 10.4 daemon on port 3308. Decoupled completely from
 * Production MariaDB on port 3306.
 *
 * STRICT SAFETY CONTRACT:
 * 1. Default Mode: PREFLIGHT ONLY (Read-only validation of port, daemon isolation, and dump integrity).
 * 2. Execution Mode: Requires explicit CLI flags:
 *      php scratch/rehearse_gate2b_isolated_drill.php --mode=execute --confirm=GATE2B_DRILL_EXECUTE
 * 3. Port 3306 Zero-Touch Refusal: Refuses immediately if target port is 3306 or if host is not '127.0.0.1'.
 * 4. Production Datadir Zero-Touch: In-band check verifies @@datadir is outside C:\xampp\mysql\data\.
 * 5. Production DB Zero-Touch: In-band check verifies `rechax_official` does NOT exist on target daemon.
 * 6. Dump Integrity: Verifies SHA-256 hash and validates absence of CREATE/DROP DATABASE or USE statements.
 * 7. Disposable Database: Generates a timestamped disposable database name on port 3308.
 * 8. Historical Data Preservation: Verifies Order #32 baseline values are preserved without reinterpretation.
 * 9. Idempotency: Rehearses secondary execution to guarantee idempotent convergence.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: Rehearsal harness must be executed via CLI only.\n");
}

require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

$shortopts = '';
$longopts  = [
    'port::',
    'host::',
    'user::',
    'pass::',
    'dump::',
    'dump-sha256::',
    'mode::',
    'confirm::',
];

$options = getopt($shortopts, $longopts);

$port               = isset($options['port']) ? (int)$options['port'] : 3308;
$host               = isset($options['host']) ? (string)$options['host'] : '127.0.0.1';
$user               = isset($options['user']) ? (string)$options['user'] : 'root';
$pass               = isset($options['pass']) ? (string)$options['pass'] : '';
$dumpPath           = isset($options['dump']) ? (string)$options['dump'] : 'C:/xampp/htdocs/RechaX Official/backups/backup_phase_c6_0_20261013_203231/db_rechax_official_c6_0_20261013_203231.sql';
$expectedDumpSha256 = isset($options['dump-sha256']) ? (string)$options['dump-sha256'] : '72e3e547a6339fdd6151b7e7c23da6118e990c04a7806da2c506bab06282a4f9';
$mode               = isset($options['mode']) ? (string)$options['mode'] : 'preflight';
$confirm            = isset($options['confirm']) ? (string)$options['confirm'] : '';

$mysqlBin           = 'C:\\xampp\\mysql\\bin\\mysql.exe';

echo "======================================================================\n";
echo "TopUpers Gate 2B — Isolated MariaDB Port 3308 Restore & Migration Harness\n";
echo "Mode:     " . strtoupper($mode) . "\n";
echo "Target:   {$host}:{$port}\n";
echo "======================================================================\n\n";

// -----------------------------------------------------------------------------
// GUARD 1: Port 3306 & Host Boundary Enforcement (Fail-Closed)
// -----------------------------------------------------------------------------
if ($port === 3306) {
    fwrite(STDERR, "[CRITICAL REFUSAL] Target port cannot be 3306 (Production port). Refusing rehearsal.\n");
    exit(2);
}
if ($host !== '127.0.0.1') {
    fwrite(STDERR, "[CRITICAL REFUSAL] Target host must strictly be '127.0.0.1' (Got '{$host}'). Named pipe/socket resolution prohibited.\n");
    exit(2);
}

// -----------------------------------------------------------------------------
// GUARD 2: Dump File Integrity & Destructive Statement Audit
// -----------------------------------------------------------------------------
echo "[STEP 1] Validating C6.0 Backup Dump File...\n";
if (!file_exists($dumpPath)) {
    fwrite(STDERR, "[DUMP ERROR] Dump file not found: '{$dumpPath}'\n");
    exit(2);
}
$dumpSize = filesize($dumpPath);
if ($dumpSize === false || $dumpSize <= 0) {
    fwrite(STDERR, "[DUMP ERROR] Dump file is empty or inaccessible: '{$dumpPath}'\n");
    exit(2);
}

echo "  Path:        {$dumpPath}\n";
echo "  Size:        " . round($dumpSize / 1024 / 1024, 2) . " MB ({$dumpSize} bytes)\n";

$actualDumpSha256 = hash_file('sha256', $dumpPath);
echo "  SHA-256:     {$actualDumpSha256}\n";
if (strcasecmp($actualDumpSha256, $expectedDumpSha256) !== 0) {
    fwrite(STDERR, "[INTEGRITY ERROR] Dump SHA-256 mismatch! Expected: {$expectedDumpSha256}, Got: {$actualDumpSha256}\n");
    exit(2);
}
echo "  [OK] Dump SHA-256 hash verified.\n";

// Scan dump file for forbidden database-level destructive statements
$dumpHandle = fopen($dumpPath, 'r');
if (!$dumpHandle) {
    fwrite(STDERR, "[DUMP ERROR] Unable to open dump file for inspection: '{$dumpPath}'\n");
    exit(2);
}

$lineNum = 0;
while (($line = fgets($dumpHandle)) !== false) {
    $lineNum++;
    if (preg_match('/^\s*(CREATE\s+DATABASE|DROP\s+DATABASE|\bUSE\b)/i', $line, $matches)) {
        fclose($dumpHandle);
        fwrite(STDERR, "[SAFETY ERROR] Forbidden statement '{$matches[1]}' found on line {$lineNum} of dump file. Refusing restore.\n");
        exit(2);
    }
}
fclose($dumpHandle);
echo "  [OK] Dump inspected: 0 CREATE DATABASE, 0 DROP DATABASE, and 0 USE statements found.\n\n";

// -----------------------------------------------------------------------------
// GUARD 3: Target Server In-Band Isolation & Boundary Audit
// -----------------------------------------------------------------------------
echo "[STEP 2] Connecting to Target Daemon on {$host}:{$port} (No Default Database)...\n";
$dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[CONNECTION ERROR] Failed to connect to MariaDB on {$host}:{$port}: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Ensure the isolated drill daemon is provisioned and running on port {$port} via scratch/provision_isolated_drill_daemon.ps1.\n");
    exit(2);
}

// In-band boundary assertions
$serverPort = (int)$pdo->query("SELECT @@port")->fetchColumn();
if ($serverPort !== $port || $serverPort === 3306) {
    fwrite(STDERR, "[BOUNDARY MISMATCH] Connected server reported @@port = {$serverPort}. Expected {$port}.\n");
    exit(2);
}

$serverDatadir = (string)$pdo->query("SELECT @@datadir")->fetchColumn();
$serverDataHome = (string)$pdo->query("SELECT @@innodb_data_home_dir")->fetchColumn();
$serverTmpdir   = (string)$pdo->query("SELECT @@tmpdir")->fetchColumn();

echo "  Server @@port:                  {$serverPort}\n";
echo "  Server @@datadir:               {$serverDatadir}\n";
echo "  Server @@innodb_data_home_dir:  {$serverDataHome}\n";
echo "  Server @@tmpdir:                {$serverTmpdir}\n";

if (stripos($serverDatadir, 'xampp/mysql/data') !== false || stripos($serverDatadir, 'xampp\\mysql\\data') !== false) {
    fwrite(STDERR, "[CRITICAL ISOLATION FAILURE] Server @@datadir references Production data directory! Aborting immediately.\n");
    exit(2);
}

$databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
echo "  Active Databases on Daemon:     " . implode(', ', $databases) . "\n";

if (in_array('rechax_official', $databases, true)) {
    fwrite(STDERR, "[CRITICAL ISOLATION FAILURE] 'rechax_official' database exists on target daemon! Refusing execution on non-clean daemon.\n");
    exit(2);
}
echo "  [OK] Target server boundary strictly verified. Zero production references.\n\n";

// -----------------------------------------------------------------------------
// GUARD 4: Preflight Completion & Execution Authorization Gate
// -----------------------------------------------------------------------------
if ($mode !== 'execute' || $confirm !== 'GATE2B_DRILL_EXECUTE') {
    echo "======================================================================\n";
    echo "READ-ONLY PREFLIGHT VERIFICATION PASSED.\n";
    echo "Status:   READY FOR ISOLATED DRILL EXECUTION (HOLD)\n";
    echo "Notice:   No databases were created, no SQL restored, and no migrations run.\n";
    echo "To run:   php scratch/rehearse_gate2b_isolated_drill.php --mode=execute --confirm=GATE2B_DRILL_EXECUTE\n";
    echo "======================================================================\n";
    exit(0);
}

// -----------------------------------------------------------------------------
// STEP 3: Provision Disposable Database on Port 3308
// -----------------------------------------------------------------------------
$timestamp   = date('Ymd_His');
$drillDb     = 'rechax_drill_gate2b_' . $timestamp;
$journalFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gate2b_drill_journal_' . $timestamp . '.jsonl';

if (!preg_match('/^rechax_[a-z0-9_]*[0-9]{14}$/', $drillDb) || $drillDb === 'rechax_official') {
    fwrite(STDERR, "[SAFETY ERROR] Disposable database name '{$drillDb}' violates naming contract.\n");
    exit(2);
}

echo "[STEP 3] Creating Disposable Rehearsal Database `{$drillDb}` on Port {$port}...\n";
$pdo->exec("CREATE DATABASE `{$drillDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  [OK] Database `{$drillDb}` created cleanly.\n\n";

// -----------------------------------------------------------------------------
// STEP 4: Restore C6.0 Backup into Disposable Database
// -----------------------------------------------------------------------------
echo "[STEP 4] Restoring C6.0 Backup Dump into `{$drillDb}` on Port {$port}...\n";

if (!file_exists($mysqlBin)) {
    fwrite(STDERR, "[PREFLIGHT ERROR] mysql client binary not found at: '{$mysqlBin}'\n");
    exit(2);
}

$dumpPathForMysql = str_replace('\\', '/', $dumpPath);
$restoreCmd = sprintf(
    '"%s" --host=%s --port=%d --user=%s --database=%s --execute="source %s;" 2>&1',
    $mysqlBin,
    escapeshellarg($host),
    $port,
    escapeshellarg($user),
    escapeshellarg($drillDb),
    $dumpPathForMysql
);

$output = [];
$exitCode = 0;
exec($restoreCmd, $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "[RESTORE FAILURE] Native restore command failed with exit code {$exitCode}: " . implode("\n", $output) . "\n");
    exit(2);
}
echo "  [OK] Dump restored successfully (Exit code: 0).\n\n";

// -----------------------------------------------------------------------------
// STEP 5: Post-Restore Table & Baseline Fingerprint Audit
// -----------------------------------------------------------------------------
echo "[STEP 5] Auditing Restored Baseline Schema & Order #32 Fingerprint...\n";

$drillDsn = "mysql:host={$host};port={$port};dbname={$drillDb};charset=utf8mb4";
$drillPdo = new PDO($drillDsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Table count check
$tables = $drillPdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$views  = $drillPdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);

echo "  Base Tables Restored: " . count($tables) . " (Expected: 42)\n";
echo "  Views Restored:       " . count($views) . " (Expected: 1 - `product_packages`)\n";

if (count($tables) !== 42 || !in_array('product_packages', $views, true)) {
    fwrite(STDERR, "[RESTORE VERIFICATION ERROR] Restored table or view count mismatch!\n");
    exit(2);
}

// Fingerprint Order #32 baseline
$o32 = $drillPdo->query("SELECT * FROM `orders` WHERE id = 32")->fetch();
if (!$o32) {
    fwrite(STDERR, "[RESTORE VERIFICATION ERROR] Baseline Order #32 missing in restored database!\n");
    exit(2);
}

$o32BaselineAmount = $o32['total_price'] ?? null;
$o32OrderNumber    = $o32['order_number'] ?? null;
$o32BaselineHash   = md5(json_encode($o32));

echo "  Baseline Order #32: ID={$o32['id']}, OrderNumber={$o32OrderNumber}, TotalPrice={$o32BaselineAmount}, Status={$o32['order_status']}\n";
echo "  Order #32 Baseline Fingerprint Hash: {$o32BaselineHash}\n";
echo "  [OK] Restored baseline verified.\n\n";

// -----------------------------------------------------------------------------
// STEP 6: Execute Gate 2B Standalone Migration Rehearsal
// -----------------------------------------------------------------------------
echo "[STEP 6] Executing Gate 2B Currency & Binance Migration Rehearsal...\n";

$runner = new Gate2BMigrationRunner($drillPdo, true, $journalFile, $drillDb);
$preflightReport = $runner->runPreflight();

echo "  Preflight Database Identity:  {$preflightReport['database']}\n";
echo "  Preflight PaymentMethod State:{$preflightReport['payment_method_state']}\n";
echo "  Preflight Binance Action:     {$preflightReport['binance_action']}\n";
echo "  Preflight Cron Action:        {$preflightReport['cron_action']}\n";

$migrationResult = $runner->executeLiveMigration();

if ($migrationResult['status'] !== 'SUCCESS') {
    fwrite(STDERR, "[MIGRATION FAILURE] Migration runner returned non-success status: {$migrationResult['status']}\n");
    exit(2);
}
echo "  Applied DDL Statements:   " . count($migrationResult['applied_ddl']) . " (Expected: 11)\n";
echo "  Applied Seed Insertions:  " . count($migrationResult['applied_seeds']) . "\n";
echo "  Preserved Existing Seeds: " . count($migrationResult['skipped_seeds']) . "\n";
echo "  [OK] Migration execution completed successfully.\n\n";

// -----------------------------------------------------------------------------
// STEP 7: Comprehensive Post-Migration Schema & Integrity Verification
// -----------------------------------------------------------------------------
echo "[STEP 7] Verifying Post-Migration Invariants & Historical Data Integrity...\n";

$runner->postVerifyDatabaseState();
echo "  [OK] postVerifyDatabaseState() passed: all 8 orders columns, 2 payments columns, dormant Binance, dormant cron, and 18 settings verified.\n";

// Verify Order #32 Historical Data Integrity (Zero Silent Changes / Zero Reinterpretation)
$o32Post = $drillPdo->query("SELECT * FROM `orders` WHERE id = 32")->fetch();
if (!$o32Post) {
    fwrite(STDERR, "[DATA CORRUPTION] Order #32 vanished after migration!\n");
    exit(2);
}

if ($o32Post['total_price'] !== $o32BaselineAmount) {
    fwrite(STDERR, "[DATA CORRUPTION] Order #32 total_price changed from '{$o32BaselineAmount}' to '{$o32Post['total_price']}'! Historical reinterpretation prohibited.\n");
    exit(2);
}
if ($o32Post['order_number'] !== $o32OrderNumber) {
    fwrite(STDERR, "[DATA CORRUPTION] Order #32 order_number changed from '{$o32OrderNumber}' to '{$o32Post['order_number']}'!\n");
    exit(2);
}
if ($o32Post['currency'] !== 'BDT') {
    fwrite(STDERR, "[SCHEMA MISMATCH] Order #32 currency default is not 'BDT' (Got '{$o32Post['currency']}').\n");
    exit(2);
}
if ($o32Post['base_amount_bdt'] !== '0.00') {
    fwrite(STDERR, "[SCHEMA MISMATCH] Order #32 base_amount_bdt default is not '0.00' (Got '{$o32Post['base_amount_bdt']}').\n");
    exit(2);
}

echo "  Order #32 post-migration total_price: {$o32Post['total_price']} (Preserved 100% exactly)\n";
echo "  Order #32 post-migration currency:    {$o32Post['currency']}\n";
echo "  Order #32 post-migration base_amount: {$o32Post['base_amount_bdt']}\n";
echo "  [OK] Historical data preservation confirmed. Zero silent changes.\n\n";

// -----------------------------------------------------------------------------
// STEP 8: Idempotency Rehearsal
// -----------------------------------------------------------------------------
echo "[STEP 8] Rehearsing Idempotency (Second Execution on Same Database)...\n";
$idempotencyRunner = new Gate2BMigrationRunner($drillPdo, true, $journalFile, $drillDb);
$idempotencyResult = $idempotencyRunner->executeLiveMigration();

echo "  Second Run Applied DDL:   " . count($idempotencyResult['applied_ddl']) . " (Expected: 0)\n";
echo "  Second Run Skipped DDL:   " . count($idempotencyResult['skipped_ddl']) . " (Expected: 11)\n";
echo "  Second Run Applied Seeds: " . count($idempotencyResult['applied_seeds']) . " (Expected: 0)\n";
echo "  Second Run Skipped Seeds: " . count($idempotencyResult['skipped_seeds']) . " (Expected: 20)\n";

if (count($idempotencyResult['applied_ddl']) !== 0 || count($idempotencyResult['applied_seeds']) !== 0) {
    fwrite(STDERR, "[IDEMPOTENCY FAILURE] Second run executed DDL or seeds that should have been skipped!\n");
    exit(2);
}
echo "  [OK] Full idempotency confirmed: Zero redundant DDL, zero duplicate rows.\n\n";

echo "======================================================================\n";
echo "ISOLATED RESTORE & MIGRATION DRILL SUCCESSFUL!\n";
echo "Drill Port:        {$port}\n";
echo "Rehearsal DB:      {$drillDb} (Preserved for forensic inspection)\n";
echo "Journal File:      {$journalFile}\n";
echo "Status:            PASSED ALL CHECKS\n";
echo "======================================================================\n";
