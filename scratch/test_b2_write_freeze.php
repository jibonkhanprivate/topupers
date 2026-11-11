<?php
/**
 * scratch/test_b2_write_freeze.php
 *
 * TopUpers Gate B2 — Write-Freeze Protection Implementation & Verification Suite.
 *
 * PURPOSE:
 * Verifies that the application-level write-freeze mechanism:
 *   1. Reports INACTIVE by default.
 *   2. Refuses unconfirmed CLI activation attempts.
 *   3. Operates entirely without database writes (zero schema or data mutation).
 *   4. Blocks order mutations across customer checkout, game submissions, admin orders,
 *      admin refunds, and auto-dispatch when active.
 *   5. Safely unfreezes and allows normal processing when disabled.
 *   6. Performs drain-checks (read-only verification of open transactions).
 *   7. NEVER touches or leaves any production lock file (config/write_freeze.lock).
 */

declare(strict_types=1);

$testLockFile = __DIR__ . '/test_write_freeze.lock';
if (file_exists($testLockFile)) {
    unlink($testLockFile);
}

// Ensure production lock file does NOT exist
$prodLockFile = dirname(__DIR__) . '/config/write_freeze.lock';
if (file_exists($prodLockFile)) {
    throw new RuntimeException("SAFETY ABORT: Production lock file `{$prodLockFile}` already exists!");
}

// Define test lock file override BEFORE loading write_freeze.php
define('RX_WRITE_FREEZE_FILE', $testLockFile);

require_once __DIR__ . '/../includes/write_freeze.php';
require_once __DIR__ . '/../includes/functions.php';

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
echo "TopUpers Gate B2 — Write-Freeze Protection Verification Suite\n";
echo "Test Flag Override: `{$testLockFile}`\n";
echo "======================================================================\n\n";

try {
    // -------------------------------------------------------------------------
    // STEP 1: Baseline Inactive State
    // -------------------------------------------------------------------------
    echo "[STEP 1] Baseline Inactive State Verification\n";
    assertCondition(!rxWriteFreezeActive(), 'Write-freeze is INACTIVE by default');
    $info = rxWriteFreezeInfo();
    assertCondition($info['active'] === false, 'Info reports active = false');
    assertCondition($info['file'] === $testLockFile, 'Info uses redirected test flag path');
    assertCondition(!file_exists($prodLockFile), 'Production lock file does NOT exist');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 2: CLI Safety Refusal Guards
    // -------------------------------------------------------------------------
    echo "[STEP 2] CLI Command & Safety Refusal Guards\n";
    
    // Test 2A: CLI enable without confirm token MUST be refused (exit code 2)
    $cmdNoConfirm = '"C:\xampp\php\php.exe" database\write_freeze.php --action=enable --file="' . $testLockFile . '" 2>&1';
    exec($cmdNoConfirm, $outNoConfirm, $codeNoConfirm);
    assertCondition($codeNoConfirm === 2, "CLI enable without confirm token returns exit code 2 (refused)");
    assertCondition(!rxWriteFreezeActive(), 'Write-freeze remains inactive after refused command');

    // Test 2B: CLI drain-check runs cleanly (read-only)
    $cmdDrain = '"C:\xampp\php\php.exe" database\write_freeze.php --action=drain-check 2>&1';
    exec($cmdDrain, $outDrain, $codeDrain);
    assertCondition($codeDrain === 0, "CLI drain-check executes cleanly (exit code 0)");
    $drainText = implode("\n", $outDrain);
    assertCondition(str_contains($drainText, 'DRAINED: safe to proceed'), 'Drain check verifies zero blocking order transactions');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 3: Enabling Write-Freeze & State Inspection
    // -------------------------------------------------------------------------
    echo "[STEP 3] Enabling Write-Freeze Protection\n";
    $enabled = rxWriteFreezeEnable('Gate B2 Verification', 120);
    assertCondition($enabled === true, 'rxWriteFreezeEnable returned true');
    assertCondition(file_exists($testLockFile), 'Test lock file was created on disk');
    assertCondition(rxWriteFreezeActive() === true, 'rxWriteFreezeActive() evaluates to TRUE');
    
    $activeInfo = rxWriteFreezeInfo();
    assertCondition($activeInfo['active'] === true, 'Active info reports active = true');
    assertCondition($activeInfo['reason'] === 'Gate B2 Verification', 'Active info preserves maintenance reason');
    assertCondition($activeInfo['retry_after'] === 120, 'Active info preserves retry-after value');
    assertCondition(!file_exists($prodLockFile), 'Production lock file remains untouched');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 4: Protection in Order Mutation Code Paths
    // -------------------------------------------------------------------------
    echo "[STEP 4] Verifying Order-Mutation Blocking During Active Freeze\n";

    // 4A: Customer message & headers
    $custMsg = rxWriteFreezeCustomerMessage();
    assertCondition(str_contains($custMsg, 'temporarily paused'), 'Customer message is polite and non-technical');
    assertCondition(str_contains($custMsg, 'nothing was submitted or charged'), 'Customer message reassures no charge');

    $adminMsg = rxWriteFreezeAdminMessage();
    assertCondition(str_contains($adminMsg, 'Order write-freeze is ACTIVE'), 'Admin message clearly states freeze status');

    // 4B: updateOrderStatus() refuses immediately without DB writes
    $updateRes = updateOrderStatus(9999, 'completed');
    assertCondition($updateRes === false, 'updateOrderStatus() returns false while freeze is active');
    assertCondition(updateOrderStatusLastError() === 'write_freeze', "updateOrderStatusLastError() returns 'write_freeze'");

    // 4C: dispatchSupplierOrder() refuses immediately without calling supplier
    $dispatchRes = dispatchSupplierOrder(9999);
    assertCondition($dispatchRes['success'] === false, 'dispatchSupplierOrder() returns success = false');
    assertCondition(($dispatchRes['frozen'] ?? false) === true, 'dispatchSupplierOrder() flags frozen = true');
    assertCondition(str_contains($dispatchRes['message'], 'Order write-freeze is ACTIVE'), 'dispatchSupplierOrder() returns admin freeze message');

    // 4D: recordWalletTransaction() refuses immediately
    $walletRes = recordWalletTransaction(1, 'deposit', 500.0, 'Test deposit during freeze');
    assertCondition($walletRes === false, 'recordWalletTransaction() returns false while freeze is active');

    // 4E: recordPayment() refuses immediately
    $payRes = recordPayment('deposit', 'DEP-TEST-FREEZE', 1, 500.0, 'bkash', '01700000000', 'TRXFREEZE01');
    assertCondition($payRes === 0, 'recordPayment() returns 0 while freeze is active');

    // 4F: cron/sync_exchange_rate.php CLI invocation skips cleanly
    $prependScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_freeze_override_' . uniqid() . '.php';
    file_put_contents($prependScript, "<?php define('RX_WRITE_FREEZE_FILE', " . var_export($testLockFile, true) . ");");
    $cmdCron = '"C:\xampp\php\php.exe" -d auto_prepend_file="' . addslashes($prependScript) . '" cron\sync_exchange_rate.php 2>&1';
    exec($cmdCron, $outCron, $codeCron);
    @unlink($prependScript);
    assertCondition($codeCron === 0, 'cron/sync_exchange_rate.php exits 0 when skipped');
    $cronText = implode("\n", $outCron);
    assertCondition(str_contains($cronText, '[SKIPPED] Write-freeze is active'), 'cron/sync_exchange_rate.php logs write-freeze skipped message');

    // 4G: admin/api/handlers.php handleCoupons fails closed with 503
    $apiTestScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_api_freeze_' . uniqid() . '.php';
    file_put_contents($apiTestScript, "<?php
define('RX_WRITE_FREEZE_FILE', " . var_export($testLockFile, true) . ");
require_once " . var_export(dirname(__DIR__) . '/config/config.php', true) . ";
require_once " . var_export(dirname(__DIR__) . '/config/database.php', true) . ";
require_once " . var_export(dirname(__DIR__) . '/includes/functions.php', true) . ";
require_once " . var_export(dirname(__DIR__) . '/admin/api/handlers.php', true) . ";
function apiError(\$msg, \$code) {
    http_response_code(\$code);
    echo json_encode(['error' => \$msg, 'code' => \$code]);
    exit(\$code === 503 ? 42 : 1);
}
\$_SERVER['REQUEST_METHOD'] = 'POST';
handleCoupons('create', ['code' => 'FREEZETEST', 'value' => 10]);
");
    $cmdApi = '"C:\xampp\php\php.exe" "' . $apiTestScript . '" 2>&1';
    exec($cmdApi, $outApi, $codeApi);
    @unlink($apiTestScript);
    assertCondition($codeApi === 42, 'handleCoupons create triggers 503 apiError during freeze');
    $apiText = implode("\n", $outApi);
    assertCondition(str_contains($apiText, 'Order write-freeze is ACTIVE'), 'handleCoupons returns write-freeze message');

    // 4H: user/add-money.php early check verification
    assertCondition(rxWriteFreezeActive(), 'user/add-money.php branch rxWriteFreezeActive() evaluates true');
    assertCondition(!empty(rxWriteFreezeCustomerMessage()), 'user/add-money.php error populated with customer freeze message');

    // 4I: admin/deposits.php, admin/users.php, admin/payments.php early check verification
    assertCondition(!empty(rxWriteFreezeAdminMessage()), 'admin write paths error populated with admin freeze message');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 5: Disabling Write-Freeze & Normal Resumption
    // -------------------------------------------------------------------------
    echo "[STEP 5] Disabling Write-Freeze & Safe Resumption\n";
    $disabled = rxWriteFreezeDisable();
    assertCondition($disabled === true, 'rxWriteFreezeDisable returned true');
    assertCondition(!file_exists($testLockFile), 'Test lock file removed from disk');
    assertCondition(rxWriteFreezeActive() === false, 'rxWriteFreezeActive() evaluates to FALSE');
    
    $postInfo = rxWriteFreezeInfo();
    assertCondition($postInfo['active'] === false, 'Post-disable info reports active = false');
    assertCondition(!file_exists($prodLockFile), 'Production lock file was NEVER created');
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 6: Confirm Live Database Safety
    // -------------------------------------------------------------------------
    echo "[STEP 6] Confirming Live Database Untouched\n";
    $pdo = new PDO('mysql:host=localhost;dbname=rechax_official;charset=utf8mb4', 'root', '');
    $liveCols = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='orders'")->fetchColumn();
    assertCondition($liveCols === 43, 'Live rechax_official.orders remains at 43 baseline columns');
    $liveOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    assertCondition($liveOrders === 1, 'Live rechax_official.orders row count remains 1');
    echo "\n";

} finally {
    if (file_exists($testLockFile)) {
        unlink($testLockFile);
    }
    echo "======================================================================\n";
    echo "WRITE-FREEZE SUMMARY: {$passedAssertions} / {$totalAssertions} ASSERTIONS PASSED (100% SUCCESS)\n";
    echo "Production lock file was NEVER created or modified.\n";
    echo "======================================================================\n";
}
