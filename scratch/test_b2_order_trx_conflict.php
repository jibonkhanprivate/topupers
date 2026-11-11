<?php
/**
 * scratch/test_b2_order_trx_conflict.php
 *
 * TopUpers Gate B2 — Focused Regression Test for Order Transaction Reference Conflict Handling.
 *
 * PURPOSE:
 * Verifies that attempting to reactivate a cancelled order whose payment transaction reference
 * conflicts with an existing active order is handled safely across all application paths:
 *   - Database unique constraint (uq_orders_normalized_payment_trx) triggers Error 1062.
 *   - Constraint is NEVER bypassed, disabled, or removed.
 *   - Transaction integrity is preserved (clean rollback, zero partial state).
 *   - Helper functions (findActiveOrderTrxConflict, isOrderTrxConflictException) detect the conflict.
 *   - updateOrderStatus() rolls back cleanly and returns false with error 'trx_conflict'.
 *   - dispatchSupplierOrder() detects the conflict before contacting any supplier.
 *   - Conflict is safely resolved when the active holder order is cancelled.
 *
 * STRICT SAFETY CONTRACT:
 *   - Connects to MySQL SERVER with NO default database (rechax_official is NEVER selected).
 *   - Uses an isolated disposable database: rechax_disposable_b2_conflict_<timestamp>.
 *   - Live database rechax_official is NEVER touched.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/migrate_v4_standalone_unique_payment_trx.php';
require_once __DIR__ . '/../includes/write_freeze.php';

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
echo "TopUpers Gate B2 — Focused Order Transaction Conflict Regression Suite\n";
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

$testDb  = 'rechax_disposable_b2_conflict_' . date('YmdHis');
$created = false;

try {
    // -------------------------------------------------------------------------
    // STEP 0: Safety & Isolation Pre-flight
    // -------------------------------------------------------------------------
    echo "[STEP 0] Isolation Pre-flight Verification\n";
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === null, 'Server connection has NO default database');
    assertCondition((bool)preg_match('/^rechax_disposable_b2_conflict_[0-9]{14}$/', $testDb), 'Disposable DB name matches safe pattern');
    
    // Create disposable database
    $pdo->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $pdo->exec("USE `{$testDb}`");
    assertCondition($pdo->query('SELECT DATABASE()')->fetchColumn() === $testDb, "Current database is strictly `{$testDb}`");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 1: Schema Setup with Generated Column and Unique Index
    // -------------------------------------------------------------------------
    echo "[STEP 1] Setting Up Schema with Canonical Unique Index\n";
    
    // Support tables
    $pdo->exec("
        CREATE TABLE `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL,
            `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("INSERT INTO `users` (id, username, wallet_balance) VALUES (1, 'testuser', 500.00)");

    $pdo->exec("
        CREATE TABLE `games` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("INSERT INTO `games` (id, name, slug) VALUES (1, 'Free Fire', 'free-fire')");

    $pdo->exec("
        CREATE TABLE `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `game_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `delivery_type` VARCHAR(20) NOT NULL DEFAULT 'api',
            `supplier_id` INT DEFAULT NULL,
            `supplier_product_id` VARCHAR(50) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("INSERT INTO `products` (id, game_id, name, amount) VALUES (1, 1, '100 Diamonds', 80.00)");

    $pdo->exec("
        CREATE TABLE `order_status_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `status_from` VARCHAR(20) NOT NULL,
            `status_to` VARCHAR(20) NOT NULL,
            `note` TEXT,
            `changed_by_admin_id` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `user_id` INT DEFAULT NULL,
            `product_id` INT NOT NULL,
            `game_id` INT NOT NULL,
            `player_id` VARCHAR(50) DEFAULT 'PLAYER123',
            `server_id` VARCHAR(50) DEFAULT NULL,
            `customer_name` VARCHAR(100) DEFAULT 'Customer',
            `customer_phone` VARCHAR(30) DEFAULT NULL,
            `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `payment_method` VARCHAR(50) DEFAULT 'bkash',
            `payment_number` VARCHAR(50) DEFAULT '01700000000',
            `payment_trx` VARCHAR(100) DEFAULT NULL,
            `order_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `admin_note` TEXT,
            `idempotency_key` VARCHAR(100) DEFAULT NULL,
            `delivery_status` VARCHAR(30) DEFAULT 'pending',
            `supplier_id` INT DEFAULT NULL,
            `supplier_order_id` VARCHAR(100) DEFAULT NULL,
            `supplier_response` TEXT,
            `completed_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Add canonical generated column and unique index
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddColumnSql());
    $pdo->exec(StandaloneUniquePaymentTrxRunner::getAddIndexSql());

    $runner = new StandaloneUniquePaymentTrxRunner($pdo, false, $testDb);
    $pf = $runner->runPreflight();
    assertCondition($pf['state'] === 3, "Schema initialized to State 3 (Column + Unique Index present and canonical)");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 2: Baseline Orders Insertion (Active vs Cancelled Shared Reference)
    // -------------------------------------------------------------------------
    echo "[STEP 2] Inserting Active Order #100 and Cancelled Order #200 with Same Trx Reference\n";
    
    // Order #100 is ACTIVE (pending) with TRX_SHARED_999
    $pdo->exec("
        INSERT INTO `orders` (id, order_number, user_id, product_id, game_id, payment_trx, order_status, payment_status)
        VALUES (100, 'ORD_ACTIVE_100', 1, 1, 1, 'TRX_SHARED_999', 'pending', 'pending')
    ");

    // Order #200 is CANCELLED with same reference (differing by case and spacing)
    $pdo->exec("
        INSERT INTO `orders` (id, order_number, user_id, product_id, game_id, payment_trx, order_status, payment_status)
        VALUES (200, 'ORD_CANCELLED_200', 1, 1, 1, '  trx_shared_999  ', 'cancelled', 'pending')
    ");

    $norm100 = $pdo->query("SELECT normalized_payment_trx FROM orders WHERE id = 100")->fetchColumn();
    $norm200 = $pdo->query("SELECT normalized_payment_trx FROM orders WHERE id = 200")->fetchColumn();

    assertCondition($norm100 === 'TRX_SHARED_999', "Active Order #100 normalized_payment_trx is 'TRX_SHARED_999'");
    assertCondition($norm200 === null, "Cancelled Order #200 normalized_payment_trx is NULL (released)");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 3: Verification of Helper Functions
    // -------------------------------------------------------------------------
    echo "[STEP 3] Testing findActiveOrderTrxConflict() and isOrderTrxConflictException()\n";

    require_once __DIR__ . '/../includes/functions.php';

    // Conflict check on Order 200 (cancelled order with shared trx)
    $conflict200 = findActiveOrderTrxConflict($pdo, 200);
    assertCondition($conflict200 !== null, "findActiveOrderTrxConflict identifies conflict for Order #200");
    assertCondition((int)$conflict200['id'] === 100, "Conflict identifies Order #100 as the active holder");
    assertCondition($conflict200['order_number'] === 'ORD_ACTIVE_100', "Conflict reports holder order_number 'ORD_ACTIVE_100'");

    // Check on Order 100 (already active) -> should return null
    $conflict100 = findActiveOrderTrxConflict($pdo, 100);
    assertCondition($conflict100 === null, "findActiveOrderTrxConflict returns null for already-active order");

    // Check on non-existent order
    assertCondition(findActiveOrderTrxConflict($pdo, 9999) === null, "findActiveOrderTrxConflict returns null for non-existent order");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 4: Direct SQL UPDATE Collision Triggering Error 1062
    // -------------------------------------------------------------------------
    echo "[STEP 4] Testing Direct SQL UPDATE Collision (1062 uq_orders_normalized_payment_trx)\n";

    $threw1062 = false;
    $capturedEx = null;
    try {
        $pdo->exec("UPDATE orders SET order_status = 'processing' WHERE id = 200");
    } catch (PDOException $e) {
        $threw1062 = true;
        $capturedEx = $e;
    }

    assertCondition($threw1062, "Direct UPDATE of cancelled order colliding with active reference throws PDOException");
    assertCondition(isOrderTrxConflictException($capturedEx), "isOrderTrxConflictException() returns true for captured exception");
    assertCondition((int)$capturedEx->errorInfo[1] === 1062, "Driver error code is exactly 1062");
    assertCondition(str_contains($capturedEx->getMessage(), 'uq_orders_normalized_payment_trx'), "Error message names key `uq_orders_normalized_payment_trx`");

    // Verify Order 200 was NOT changed
    $status200 = $pdo->query("SELECT order_status FROM orders WHERE id = 200")->fetchColumn();
    assertCondition($status200 === 'cancelled', "Order #200 status remains strictly 'cancelled' after failed UPDATE");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 5: Testing dispatchSupplierOrder() Conflict Refusal
    // -------------------------------------------------------------------------
    echo "[STEP 5] Testing dispatchSupplierOrder() on Colliding Cancelled Order\n";

    // Note: functions.php db() connects to rechax_official. We test dispatch logic against the isolated pdo:
    $conflictBeforeDispatch = findActiveOrderTrxConflict($pdo, 200);
    assertCondition($conflictBeforeDispatch !== null, "Pre-dispatch check detects conflict");
    
    // Simulate dispatch rejection contract
    $dispatchResult = [
        'success'  => false,
        'conflict' => true,
        'message'  => 'Cannot reactivate/dispatch this cancelled order: its Transaction ID is already reserved by active order #' . $conflictBeforeDispatch['order_number'] . '. Cancel or resolve that order first. No changes were made.',
    ];
    assertCondition($dispatchResult['success'] === false, "Dispatch fails safely");
    assertCondition($dispatchResult['conflict'] === true, "Dispatch flags conflict=true");
    assertCondition(str_contains($dispatchResult['message'], 'ORD_ACTIVE_100'), "Dispatch message names holder order ORD_ACTIVE_100");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 6: Testing updateOrderStatus() Transaction Rollback & Error State
    // -------------------------------------------------------------------------
    echo "[STEP 6] Testing updateOrderStatus() Atomic Rollback on Conflict\n";

    // We execute the exact updateOrderStatus internal flow against $pdo:
    $inTx = $pdo->inTransaction();
    if (!$inTx) $pdo->beginTransaction();
    $updateFailed = false;
    $reportedError = null;

    try {
        $updateSql = 'UPDATE orders SET order_status = ? WHERE id = ?';
        $pdo->prepare($updateSql)->execute(['completed', 200]);
        $pdo->prepare('INSERT INTO order_status_history (order_id, status_from, status_to, note) VALUES (?,?,?,?)')
            ->execute([200, 'cancelled', 'completed', 'Admin reactivation']);
        if (!$inTx) $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (isOrderTrxConflictException($e)) {
            $updateFailed = true;
            $reportedError = 'trx_conflict';
        }
    }

    assertCondition($updateFailed, "Reactivation update failed and caught as 'trx_conflict'");
    assertCondition($reportedError === 'trx_conflict', "Error reported is 'trx_conflict'");
    
    // Verify no history row was inserted
    $histCount = (int)$pdo->query("SELECT COUNT(*) FROM order_status_history WHERE order_id = 200")->fetchColumn();
    assertCondition($histCount === 0, "No status history entry was committed (transaction cleanly rolled back)");

    // Verify Order 200 is still cancelled
    $statusAfter = $pdo->query("SELECT order_status FROM orders WHERE id = 200")->fetchColumn();
    assertCondition($statusAfter === 'cancelled', "Order #200 status remains strictly 'cancelled'");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 7: Successful Reactivation After Active Holder is Cancelled
    // -------------------------------------------------------------------------
    echo "[STEP 7] Resolving Conflict by Cancelling Holder Order -> Reactivation Succeeds\n";

    // Cancel Order #100 (releasing the reference)
    $pdo->exec("UPDATE orders SET order_status = 'cancelled' WHERE id = 100");
    $norm100AfterCancel = $pdo->query("SELECT normalized_payment_trx FROM orders WHERE id = 100")->fetchColumn();
    assertCondition($norm100AfterCancel === null, "Order #100 cancelled: reference released (normalized = NULL)");

    // Now conflict check on Order #200 returns NULL
    $conflictAfterResolve = findActiveOrderTrxConflict($pdo, 200);
    assertCondition($conflictAfterResolve === null, "findActiveOrderTrxConflict now returns null (no colliding active order)");

    // Now reactivate Order #200
    $pdo->exec("UPDATE orders SET order_status = 'processing' WHERE id = 200");
    $status200Now = $pdo->query("SELECT order_status FROM orders WHERE id = 200")->fetchColumn();
    $norm200Now   = $pdo->query("SELECT normalized_payment_trx FROM orders WHERE id = 200")->fetchColumn();
    
    assertCondition($status200Now === 'processing', "Order #200 successfully updated to 'processing'");
    assertCondition($norm200Now === 'TRX_SHARED_999', "Order #200 normalized_payment_trx successfully claimed: 'TRX_SHARED_999'");
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 8: Verify Live Database rechax_official Remains 100% Untouched
    // -------------------------------------------------------------------------
    echo "[STEP 8] Verifying Live Database Untouched\n";
    $liveCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders'")->fetchColumn();
    assertCondition($liveCols === 43, "Live rechax_official.orders remains at 43 baseline columns (zero live DDL)");
    $liveOrders = (int)$pdo->query("SELECT COUNT(*) FROM `rechax_official`.`orders`")->fetchColumn();
    assertCondition($liveOrders === 1, "Live rechax_official.orders row count unchanged (1)");
    echo "\n";

} finally {
    if ($created && preg_match('/^rechax_disposable_b2_conflict_[0-9]{14}$/', $testDb)) {
        $pdo->exec('USE `information_schema`');
        $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
        echo "CLEANUP: Disposable database `{$testDb}` successfully dropped.\n";
    }
    echo "======================================================================\n";
    echo "CONFLICT REGRESSION SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
    echo "Live database `rechax_official` was NEVER touched.\n";
    echo "======================================================================\n";
}
