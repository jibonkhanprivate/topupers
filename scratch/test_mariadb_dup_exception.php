<?php
/**
 * scratch/test_mariadb_dup_exception.php
 *
 * Captures the real MariaDB duplicate-key exception for uq_orders_normalized_payment_trx and verifies the
 * checkout.php-style handler identifies it.
 *
 * GATE B2 HARNESS HARDENING:
 *   - Does NOT load config/database.php and NEVER calls db() (which selects rechax_official).
 *   - Connects to the SERVER with no default database in the DSN.
 *   - Disposable DB name must match ^rechax_disposable_c59_dup_[0-9]{14}$ (checked before any DDL).
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

$testDb  = 'rechax_disposable_c59_dup_' . date('YmdHis');
$created = false;
if (!preg_match('/^rechax_disposable_c59_dup_[0-9]{14}$/', $testDb) || $testDb === 'rechax_official') {
    fwrite(STDERR, "Refusing to run: unsafe disposable database name.\n");
    exit(2);
}
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== null) {
    fwrite(STDERR, "Refusing to run: connection has a default database selected.\n");
    exit(2);
}

$ok = false;
try {
    $pdo->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$testDb}`");
    echo "Connected database: " . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n";

    $pdo->exec("
        CREATE TABLE `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `order_status` VARCHAR(20) NOT NULL,
            `payment_trx` VARCHAR(100) DEFAULT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Apply canonical DDL
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());

    // Insert first order with TRX123
    $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD001', 'pending', 'TRX123')");

    $capturedException = null;
    try {
        // Attempt duplicate insertion
        $pdo->exec("INSERT INTO `orders` (`order_number`, `order_status`, `payment_trx`) VALUES ('ORD002', 'processing', '  trx123  ')");
    } catch (PDOException $e) {
        $capturedException = $e;
    }

    if ($capturedException === null) {
        echo "Duplicate insert was NOT rejected (FAIL)\n";
    } else {
        echo "Captured PDOException:\n";
        echo "  getCode(): " . $capturedException->getCode() . "\n";
        echo "  errorInfo[0] (SQLSTATE): " . ($capturedException->errorInfo[0] ?? 'N/A') . "\n";
        echo "  errorInfo[1] (Driver code): " . ($capturedException->errorInfo[1] ?? 'N/A') . "\n";
        echo "  errorInfo[2] (Driver message): " . ($capturedException->errorInfo[2] ?? 'N/A') . "\n";
        echo "  getMessage(): " . $capturedException->getMessage() . "\n\n";

        // Test application logic from checkout.php:
        $sqlState   = (string)$capturedException->getCode();
        $driverCode = (int)($capturedException->errorInfo[1] ?? 0);
        $errorMsg   = $capturedException->getMessage();

        $isIdentified = (($sqlState === '23000' || $driverCode === 1062) && stripos($errorMsg, 'uq_orders_normalized_payment_trx') !== false);
        echo "Identified by checkout.php handler: " . ($isIdentified ? "YES (SUCCESS)" : "NO (FAIL)") . "\n";
        $ok = $isIdentified && $driverCode === 1062;
    }
} finally {
    if ($created && preg_match('/^rechax_disposable_c59_dup_[0-9]{14}$/', $testDb)) {
        $pdo->exec('USE `information_schema`');
        $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
        echo "Disposable test DB `{$testDb}` cleaned up.\n";
    }
    echo "rechax_official was never selected, read, or altered by this script.\n";
}
echo $ok ? "RESULT: PASS\n" : "RESULT: FAIL\n";
exit($ok ? 0 : 1);
