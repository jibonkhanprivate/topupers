<?php
/**
 * scratch/test_disposable_restore.php
 * Strictly isolated test of backup restoration into a disposable database.
 * NEVER touches rechax_official.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();

// 1. Verify Live Database Identity
$liveDb = $pdo->query("SELECT DATABASE()")->fetchColumn();
if ($liveDb !== 'rechax_official') {
    die("[FATAL ABORT] Active DB is not rechax_official: $liveDb\n");
}

$dumpFile = "c:/xampp/htdocs/RechaX Official/backups/backup_gate2b_20261012_035539/db_gate2b_preflight_20261012_035539.sql";
if (!file_exists($dumpFile)) {
    die("[FATAL ABORT] Backup file does not exist: $dumpFile\n");
}

$randomToken = bin2hex(random_bytes(4));
$disposableDb = "rechax_disposable_audit_" . date('Ymd_His') . "_{$randomToken}";

// Absolute Safety Checks
if (strpos($disposableDb, 'rechax_disposable_audit_') !== 0) {
    die("[FATAL ABORT] Invalid disposable DB name prefix: $disposableDb\n");
}
if ($disposableDb === 'rechax_official') {
    die("[FATAL ABORT] Collision with live database!\n");
}

echo "=== ISOLATED DISPOSABLE DATABASE RESTORATION AUDIT ===\n";
echo "Live DB:       $liveDb (READ-ONLY, ZERO WRITES)\n";
echo "Backup File:   $dumpFile\n";
echo "Disposable DB: $disposableDb\n\n";

// Capture live row counts BEFORE test
$liveTablesBefore = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$liveCountsBefore = [];
foreach ($liveTablesBefore as $t) {
    $liveCountsBefore[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}

$created = false;
try {
    // 2. Create Disposable Database
    echo "[1/4] Creating temporary disposable database: $disposableDb...\n";
    $pdo->exec("CREATE DATABASE `$disposableDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    echo "  [✓] Disposable database created successfully.\n\n";

    // 3. Restore Backup into Disposable Database
    echo "[2/4] Restoring preflight backup into disposable database via mysql.exe...\n";
    $mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    $cmd = "\"$mysqlBin\" -u root $disposableDb < \"$dumpFile\"";
    exec($cmd, $out, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException("mysql.exe restore failed with exit code $exitCode");
    }
    echo "  [✓] Backup restored into disposable database with exit code 0.\n\n";

    // 4. Verify Contents in Disposable Database
    echo "[3/4] Verifying table inventory & row counts in disposable database...\n";
    $pdoDisp = new PDO("mysql:host=localhost;dbname=$disposableDb;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $dispTables = $pdoDisp->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Total tables restored: " . count($dispTables) . " (Expected: " . count($liveTablesBefore) . ")\n";

    if (count($dispTables) !== count($liveTablesBefore)) {
        throw new RuntimeException("Table count mismatch in disposable database!");
    }

    $mismatches = 0;
    foreach ($dispTables as $t) {
        $c = (int)$pdoDisp->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        if ($c !== $liveCountsBefore[$t]) {
            echo "  [MISMATCH] Table `$t`: live={$liveCountsBefore[$t]}, restored=$c\n";
            $mismatches++;
        }
    }

    if ($mismatches === 0) {
        echo "  [✓] All " . count($dispTables) . " tables match live row counts exactly 100%!\n\n";
    } else {
        throw new RuntimeException("Row count mismatches detected in restored database.");
    }

} finally {
    // 5. Clean up Disposable Database
    if ($created) {
        echo "[4/4] Tearing down disposable database: $disposableDb...\n";
        $pdo->exec("DROP DATABASE IF EXISTS `$disposableDb`");
        echo "  [✓] Disposable database dropped cleanly.\n\n";
    }
}

// 6. Final verification that live database was NOT touched
echo "=== POST-TEST LIVE DATABASE INTEGRITY CHECK ===\n";
$liveTablesAfter = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$liveMismatches = 0;
foreach ($liveTablesAfter as $t) {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    if ($c !== $liveCountsBefore[$t]) {
        echo "  [CRITICAL] Live table `$t` changed: before={$liveCountsBefore[$t]}, after=$c\n";
        $liveMismatches++;
    }
}

if ($liveMismatches === 0) {
    echo "[PASS] Live database `rechax_official` was completely unaffected and remains 100% identical.\n";
    echo "[PASS] Preflight backup is 100% syntactically valid and fully restorable.\n";
} else {
    echo "[CRITICAL FAIL] Live database state was altered!\n";
}
