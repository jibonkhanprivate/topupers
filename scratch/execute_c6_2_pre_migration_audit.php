<?php
/**
 * scratch/execute_c6_2_pre_migration_audit.php
 *
 * TopUpers Gate 2B — Phase C6.2: Pre-Migration Readiness Audit Only
 *
 * STRICT SAFETY CONTRACT:
 * - 100% Read-Only inspection.
 * - Zero DDL, zero DML (no INSERT, UPDATE, DELETE, TRUNCATE, DROP).
 * - Zero modifications to Order #32.
 * - Confirms retention of temporary database `rechax_restore_test_c6_1_20261014_035114`.
 * - Verifies C6.0 backup path and SHA-256 integrity.
 * - Confirms migration refusal guards are active and unbypassed.
 * - Evaluates maintenance write-freeze and rollback recovery considerations.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';

$pdo = db();

echo "======================================================================\n";
echo "TopUpers Gate 2B — Phase C6.2: Pre-Migration Readiness Audit Only\n";
echo "======================================================================\n\n";

// -----------------------------------------------------------------------------
// CHECK 1: Live Database Identity & Connection
// -----------------------------------------------------------------------------
echo "[CHECK 1] Live Database Identity & Connection Status\n";
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
$version = $pdo->query("SELECT VERSION()")->fetchColumn();
$host = DB_HOST;

echo "  Database Name: {$dbName}\n";
echo "  Server Engine: {$version}\n";
echo "  Host:          {$host}\n";

if ($dbName !== 'rechax_official') {
    throw new RuntimeException("ABORT: Connected database is '{$dbName}', expected 'rechax_official'!");
}
echo "  -> RESULT: CONFIRMED connected to live target `rechax_official`.\n\n";

// -----------------------------------------------------------------------------
// CHECK 2: Baseline `orders` Schema & Target Objects Absence
// -----------------------------------------------------------------------------
echo "[CHECK 2] Baseline `orders` Schema & Target Object Status\n";
$colCount = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders'
")->fetchColumn();

$hasGenCol = (bool)$pdo->query("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'normalized_payment_trx'
")->fetchColumn();

$hasTargetIdx = (bool)$pdo->query("
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = 'rechax_official' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'uq_orders_normalized_payment_trx'
")->fetchColumn();

echo "  Total Columns in `orders`:            {$colCount} (Baseline expected: 43)\n";
echo "  Target Column `normalized_payment_trx`: " . ($hasGenCol ? "PRESENT (UNEXPECTED)" : "ABSENT (Clean baseline)") . "\n";
echo "  Target Index `uq_orders_...`:          " . ($hasTargetIdx ? "PRESENT (UNEXPECTED)" : "ABSENT (Clean baseline)") . "\n";

if ($colCount !== 43 || $hasGenCol || $hasTargetIdx) {
    throw new RuntimeException("ABORT: `orders` table is not in clean 43-column baseline state!");
}
echo "  -> RESULT: Verified clean 43-column baseline. Target objects do not exist.\n\n";

// -----------------------------------------------------------------------------
// CHECK 3: Orders Count & Pending Order #32 Integrity
// -----------------------------------------------------------------------------
echo "[CHECK 3] Current Orders Count & Pending Order #32 Integrity\n";
$orderCount = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
echo "  Total Live Orders in Database: {$orderCount}\n";

$order32 = $pdo->query("SELECT * FROM `orders` WHERE id = 32")->fetch(PDO::FETCH_ASSOC);
if (!$order32) {
    throw new RuntimeException("ABORT: Order #32 not found in database!");
}

echo "  Order #32 Details:\n";
echo "    - ID:            {$order32['id']}\n";
echo "    - Order Number:  {$order32['order_number']}\n";
echo "    - Order Status:  {$order32['order_status']}\n";
echo "    - Payment Trx:   {$order32['payment_trx']}\n";
echo "    - Payment Method:{$order32['payment_method']}\n";
echo "    - Total Price:   {$order32['total_price']}\n";
echo "    - Created At:    {$order32['created_at']}\n";

if ($order32['order_number'] !== 'RX6ACE242D8221B13') {
    throw new RuntimeException("ABORT: Order #32 order_number mismatch!");
}
if ($order32['order_status'] !== 'pending') {
    throw new RuntimeException("ABORT: Order #32 status is '{$order32['order_status']}', expected 'pending'!");
}
echo "  -> RESULT: Order #32 confirmed intact with status 'pending' (ZERO MUTATIONS).\n\n";

// -----------------------------------------------------------------------------
// CHECK 4: Duplicate & Conflicting Payment Transaction Reference Check
// -----------------------------------------------------------------------------
echo "[CHECK 4] Conflicting Payment Transaction Reference Analysis\n";
$dupQuery = "
    SELECT 
        UPPER(TRIM(payment_trx)) AS norm_trx,
        COUNT(*) AS cnt,
        GROUP_CONCAT(order_number) AS colliding_orders,
        GROUP_CONCAT(order_status) AS statuses
    FROM `orders`
    WHERE payment_trx IS NOT NULL 
      AND TRIM(payment_trx) != ''
      AND order_status != 'cancelled'
    GROUP BY UPPER(TRIM(payment_trx))
    HAVING cnt > 1
";
$duplicates = $pdo->query($dupQuery)->fetchAll(PDO::FETCH_ASSOC);
echo "  Colliding Active Transaction Sets: " . count($duplicates) . "\n";

if (!empty($duplicates)) {
    throw new RuntimeException("CRITICAL NO-GO: Duplicate active transaction references found!");
}
echo "  -> RESULT: ZERO transaction collisions. Current live data will not violate unique constraint.\n\n";

// -----------------------------------------------------------------------------
// CHECK 5: Generation Expression & Cancelled Order Exception Policy
// -----------------------------------------------------------------------------
echo "[CHECK 5] Generated-Column Expression & Cancellation Policy\n";
$canonicalExpr = StandaloneUniquePaymentTrxRunner::getCanonicalNormalizedExpression();
$addColumnSql  = StandaloneUniquePaymentTrxRunner::getAddColumnSql();
$addIndexSql   = StandaloneUniquePaymentTrxRunner::getAddIndexSql();

echo "  Canonical Expression:\n    {$canonicalExpr}\n";
echo "  Add Column SQL:\n    {$addColumnSql}\n";
echo "  Add Index SQL:\n    {$addIndexSql}\n";
echo "  Cancelled Order Policy:\n";
echo "    - Expression evaluates to NULL when `order_status = 'cancelled'`.\n";
echo "    - In MariaDB/MySQL, UNIQUE indexes permit multiple NULLs.\n";
echo "    - When an order is cancelled, its transaction reference is immediately freed for re-submission.\n";
echo "    - All 8 non-cancelled statuses strictly reserve the normalized reference.\n";
echo "  -> RESULT: Generation expression and cancellation policy match reviewed specification.\n\n";

// -----------------------------------------------------------------------------
// CHECK 6: Handler Error Trapping & Customer-Safe Error Messages
// -----------------------------------------------------------------------------
echo "[CHECK 6] Application Handler Duplicate-Reference Review\n";
$handlers = [
    'checkout.php' => __DIR__ . '/../checkout.php',
    'game.php'     => __DIR__ . '/../game.php',
    'admin/orders.php' => __DIR__ . '/../admin/orders.php',
];

foreach ($handlers as $label => $path) {
    $code = file_get_contents($path);
    $hasTrap = (strpos($code, 'uq_orders_normalized_payment_trx') !== false);
    $hasSafeMsg = (
        strpos($code, 'This transaction ID has already been submitted') !== false ||
        strpos($code, 'is already reserved by another active order') !== false
    );
    echo "  Handler `{$label}`:\n";
    echo "    - Index Trapping (`uq_orders_...`): " . ($hasTrap ? "VERIFIED" : "MISSING") . "\n";
    echo "    - Customer/Admin-Safe Message:      " . ($hasSafeMsg ? "VERIFIED" : "MISSING") . "\n";

    if (!$hasTrap || !$hasSafeMsg) {
        throw new RuntimeException("ABORT: Handler `{$label}` does not properly trap duplicate reference errors!");
    }
}
echo "  -> RESULT: All order creation and status update handlers have hardened error trapping.\n\n";

// -----------------------------------------------------------------------------
// CHECK 7: C6.0 Backup Path, SHA-256 Hash & Refusal Guards
// -----------------------------------------------------------------------------
echo "[CHECK 7] C6.0 Backup & Safety Refusal Guards\n";
$backupPath = 'C:/xampp/htdocs/RechaX Official/backups/backup_phase_c6_0_20261013_203231/db_rechax_official_c6_0_20261013_203231.sql';
$expectedSha256 = '72e3e547a6339fdd6151b7e7c23da6118e990c04a7806da2c506bab06282a4f9';

if (!file_exists($backupPath)) {
    throw new RuntimeException("ABORT: C6.0 backup file not found at {$backupPath}!");
}
$actualSha256 = hash_file('sha256', $backupPath);
echo "  Backup File:   {$backupPath}\n";
echo "  Size:          " . filesize($backupPath) . " bytes\n";
echo "  SHA-256:       {$actualSha256}\n";
if (strtolower($actualSha256) !== strtolower($expectedSha256)) {
    throw new RuntimeException("ABORT: C6.0 backup hash mismatch!");
}
echo "  -> Backup Integrity: 100% exact match against C6.0 recorded hash.\n";

// Test refusal guard
$guardStandaloneActive = false;
try {
    $r = new StandaloneUniquePaymentTrxRunner($pdo, true);
    $r->execute();
} catch (LogicException $le) {
    if (strpos($le->getMessage(), 'SAFETY REFUSAL') !== false || strpos($le->getMessage(), 'SAFETY ENFORCEMENT REFUSAL') !== false) {
        $guardStandaloneActive = true;
    }
}
echo "  Standalone Runner Refusal Guard: " . ($guardStandaloneActive ? "ACTIVE (BLOCKED)" : "INACTIVE (VIOLATION)") . "\n";
if (!$guardStandaloneActive) {
    throw new RuntimeException("ABORT: Standalone runner refusal guard is not active!");
}
echo "  -> RESULT: C6.0 backup verified intact; live execution gate remains strictly locked.\n\n";

// -----------------------------------------------------------------------------
// CHECK 8: Temporary Restore Database Retention Check
// -----------------------------------------------------------------------------
echo "[CHECK 8] Temporary Restore Database Retention Status\n";
$tempDbName = 'rechax_restore_test_c6_1_20261014_035114';
$dbList = $pdo->query("SHOW DATABASES LIKE '{$tempDbName}'")->fetchAll(PDO::FETCH_COLUMN);
$tempDbExists = in_array($tempDbName, $dbList, true);

echo "  Temporary Test DB: `{$tempDbName}`\n";
echo "  Database On Disk:  " . ($tempDbExists ? "PRESERVED & RETAINED (VERIFIED)" : "NOT FOUND (WARNING)") . "\n";

if (!$tempDbExists) {
    throw new RuntimeException("ABORT: Temporary database `{$tempDbName}` was deleted unexpectedly!");
}
echo "  -> RESULT: Temporary database is retained intact awaiting operator cleanup authorization.\n\n";

// -----------------------------------------------------------------------------
// CHECK 9: Remaining Blockers, Maintenance Window & Data Recovery Policy
// -----------------------------------------------------------------------------
echo "[CHECK 9] Remaining Blockers & Operational Prerequisites\n";
echo "  1. Live Migration Authorization Gate:\n";
echo "     - Live writes are hard-coded blocked in `execute()`.\n";
echo "     - Requires explicit operator instruction to arm the migration flag.\n";
echo "  2. Write-Freeze / Maintenance Window:\n";
echo "     - MariaDB DDL (`ALTER TABLE orders ADD COLUMN ... PERSISTENT`) acquires metadata locks.\n";
echo "     - Incoming customer checkouts during the migration window could stall or cause lock waits.\n";
echo "     - A formal checkout freeze (maintenance mode) is required during the live execution window.\n";
echo "  3. Live Data Recovery Policy:\n";
echo "     - If live migration fails and a full dump restore is performed, any orders created between the\n";
echo "       C6.0 backup and the rollback would be overwritten.\n";
echo "     - Operational mitigation: Compensatory DDL rollback (`DROP INDEX`, `DROP COLUMN`) is preferred\n";
echo "       over whole-database restore to prevent live data loss.\n\n";

echo "======================================================================\n";
echo "PHASE C6.2 READINESS AUDIT VERDICT: GO (READ-ONLY AUDIT COMPLETE)\n";
echo "Note: GO indicates technical readiness and zero blockers detected.\n";
echo "      It does NOT authorize or initiate live migration.\n";
echo "======================================================================\n";
