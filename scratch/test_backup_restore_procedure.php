<?php
/**
 * scratch/test_backup_restore_procedure.php
 *
 * Validates the PowerShell backup & restore workflow using disposable databases.
 * Tests --result-file, exit code checking, SHA-256 calculation, and restore via --execute="source ...".
 * Zero interaction with live database rechax_official.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$sourceDb  = 'rechax_disposable_c59_backup_src';
$restoreDb = 'rechax_disposable_c59_backup_dst';

$mysqldumpBin = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$mysqlBin     = 'C:\\xampp\\mysql\\bin\\mysql.exe';

$scratchBackupDir = __DIR__ . '/scratch_backups';
if (!is_dir($scratchBackupDir)) {
    mkdir($scratchBackupDir, 0777, true);
}
$backupFile = $scratchBackupDir . '/disposable_test_backup_' . date('Ymd_His') . '.sql';

echo "======================================================================\n";
echo "TopUpers Gate 2B — Backup & Restore Validation Procedure Test\n";
echo "Testing with Disposable Databases: `{$sourceDb}` -> `{$restoreDb}`\n";
echo "======================================================================\n\n";

try {
    // 1. Setup source disposable database with sample schema & data
    $pdo->exec("DROP DATABASE IF EXISTS `{$sourceDb}`");
    $pdo->exec("CREATE DATABASE `{$sourceDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$sourceDb}`");
    
    $pdo->exec("
        CREATE TABLE `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `order_status` VARCHAR(20) NOT NULL,
            `payment_trx` VARCHAR(100) DEFAULT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`, `total_amount`) VALUES ('ORD_BACKUP_1', 'pending', 'TRX_BCK_01', 500.00)");
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`, `total_amount`) VALUES ('ORD_BACKUP_2', 'completed', 'TRX_BCK_02', 1250.00)");

    echo "[STEP 1] Source disposable database populated. Orders count: 2.\n";

    // 2. Execute mysqldump with --result-file
    // Windows path formatting
    $backupFileNorm = str_replace('/', '\\', $backupFile);
    $cmdBackup = "\"{$mysqldumpBin}\" --host=localhost --user=root --result-file=\"{$backupFileNorm}\" --single-transaction --quick --routines --triggers {$sourceDb} 2>&1";
    
    exec($cmdBackup, $outBackup, $retBackup);
    echo "[STEP 2] mysqldump command executed. Exit code: {$retBackup}\n";
    if ($retBackup !== 0) {
        throw new RuntimeException("mysqldump failed: " . implode("\n", $outBackup));
    }

    // 3. Verify backup file exists, is non-empty, and calculate SHA-256
    if (!file_exists($backupFileNorm)) {
        throw new RuntimeException("Backup file does not exist: {$backupFileNorm}");
    }
    $size = filesize($backupFileNorm);
    if ($size === 0) {
        throw new RuntimeException("Backup file is empty!");
    }
    $sha256 = hash_file('sha256', $backupFileNorm);
    echo "[STEP 3] Backup file verified on disk:\n";
    echo "  - File:   {$backupFileNorm}\n";
    echo "  - Size:   {$size} bytes\n";
    echo "  - SHA256: {$sha256}\n\n";

    // 4. Setup target disposable database for restore validation
    $pdo->exec("DROP DATABASE IF EXISTS `{$restoreDb}`");
    $pdo->exec("CREATE DATABASE `{$restoreDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[STEP 4] Target disposable database `{$restoreDb}` created for restore test.\n";

    // 5. Restore using mysql --execute="source ..."
    // In mysql client, forward slashes in source path work reliably across platforms
    $sourcePathForSql = str_replace('\\', '/', $backupFileNorm);
    $cmdRestore = "\"{$mysqlBin}\" --host=localhost --user=root --database={$restoreDb} --execute=\"source {$sourcePathForSql};\" 2>&1";
    exec($cmdRestore, $outRestore, $retRestore);
    echo "[STEP 5] mysql restore command executed. Exit code: {$retRestore}\n";
    if ($retRestore !== 0) {
        throw new RuntimeException("Restore failed: " . implode("\n", $outRestore));
    }

    // 6. Verify restored data in restoreDb
    $pdo->exec("USE `{$restoreDb}`");
    $restoredOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    $rows = $pdo->query("SELECT `order_number`, `order_status`, `payment_trx` FROM `orders` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo "[STEP 6] Restore validation:\n";
    echo "  - Restored row count: {$restoredOrders} (Expected: 2)\n";
    foreach ($rows as $r) {
        echo "  - Row: {$r['order_number']} | Status: {$r['order_status']} | Trx: {$r['payment_trx']}\n";
    }

    if ($restoredOrders !== 2) {
        throw new RuntimeException("Restored row count mismatch! Expected 2, got {$restoredOrders}");
    }

    echo "\n[STATUS] BACKUP & RESTORE VALIDATION SUCCESSFUL!\n";

} finally {
    // 7. Cleanup
    $pdo->exec("USE `rechax_official`");
    $pdo->exec("DROP DATABASE IF EXISTS `{$sourceDb}`");
    $pdo->exec("DROP DATABASE IF EXISTS `{$restoreDb}`");
    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
    if (is_dir($scratchBackupDir)) {
        @rmdir($scratchBackupDir);
    }
    echo "[CLEANUP] Disposable databases `{$sourceDb}` and `{$restoreDb}`, and scratch test backup deleted.\n";
    echo "Live database `rechax_official` was completely untouched.\n";
}
