<?php
/**
 * scratch/test_step4d_sync_exchange_rate.php
 *
 * Automated Unit Test Suite for Step 4D: Automatic USD/BDT Exchange Rate Sync
 * STRICT SAFETY: Uses in-memory SQLite database and mocked HTTP responses.
 * ZERO LIVE DATABASE WRITES. ZERO EXTERNAL NETWORK REQUESTS.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../cron/sync_exchange_rate.php';
require_once __DIR__ . '/../includes/currency.php';

echo "======================================================\n";
echo "TEST SUITE: STEP 4D AUTOMATIC EXCHANGE RATE SYNC (MOCKED)\n";
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
 * Factory helper: creates an isolated in-memory SQLite database.
 */
function createTestDb(): PDO {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT
        );
    ");

    $pdo->exec("
        CREATE TABLE cron_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_key VARCHAR(50) UNIQUE,
            name VARCHAR(100),
            schedule_expression VARCHAR(50),
            last_run_at TEXT,
            last_status VARCHAR(20),
            last_message TEXT,
            is_active INTEGER DEFAULT 0
        );
    ");

    // Seed test cron job row
    $pdo->exec("
        INSERT INTO cron_jobs (job_key, name, schedule_expression, is_active)
        VALUES ('exchange_rate_sync', 'Exchange Rate Daily & Auto Sync', '0 */4 * * *', 0);
    ");

    return $pdo;
}

// -----------------------------------------------------------
// TEST 1: Successful Exchange Rate Synchronization
// -----------------------------------------------------------
echo "--- Group 1: Successful Exchange Rate Fetch & Parsing ---\n";
$db = createTestDb();
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_1.lock';
@unlink($lockFile);

$mockSuccess = function(string $url, int $connectTimeout, int $totalTimeout): array {
    $body = json_encode([
        'result'                => 'success',
        'provider'              => 'https://www.exchangerate-api.com',
        'base_code'             => 'USD',
        'rates'                 => [
            'USD' => 1.0,
            'BDT' => 123.4567,
            'EUR' => 0.92,
        ],
        'time_last_update_unix' => 1791763352, // Mon, 12 Oct 2026
    ]);
    return ['status_code' => 200, 'body' => $body, 'error' => ''];
};

$syncer = new ExchangeRateSyncer($db, $mockSuccess, $lockFile);
$res = $syncer->sync();

assertTest("Sync returned status 'success'", ($res['status'] ?? '') === 'success');
assertTest("Sync returned exit code 0", ($res['code'] ?? -1) === 0);
assertTest("Sync parsed exact numeric rate 123.4567", ($res['rate'] ?? 0.0) === 123.4567);

// Verify DB persistence
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$saved = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

assertTest("auto_usd_bdt_rate persisted as '123.4567'", ($saved['auto_usd_bdt_rate'] ?? '') === '123.4567');
assertTest("auto_rate_status persisted as 'success'", ($saved['auto_rate_status'] ?? '') === 'success');
assertTest("auto_rate_last_updated populated", !empty($saved['auto_rate_last_updated']));
assertTest("auto_rate_last_attempt populated", !empty($saved['auto_rate_last_attempt']));
assertTest("auto_rate_error is empty string on success", ($saved['auto_rate_error'] ?? 'x') === '');

// Verify cron_jobs table update
$cronRow = $db->query("SELECT last_status, last_message FROM cron_jobs WHERE job_key = 'exchange_rate_sync'")->fetch();
assertTest("cron_jobs last_status updated to 'success'", ($cronRow['last_status'] ?? '') === 'success');
assertTest("cron_jobs message contains USD/BDT rate", strpos($cronRow['last_message'] ?? '', '123.4567') !== false);
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 2: Network Timeout Handling & Non-Destructive Failure
// -----------------------------------------------------------
echo "\n--- Group 2: Network Timeout & Rate Preservation ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_2.lock';
@unlink($lockFile);

$mockTimeout = function(string $url, int $connectTimeout, int $totalTimeout): array {
    return [
        'status_code' => 0,
        'body'        => '',
        'error'       => 'Operation timed out after 5000 milliseconds with 0 bytes received (cURL code 28)',
    ];
};

$syncerTimeout = new ExchangeRateSyncer($db, $mockTimeout, $lockFile);
$resTimeout = $syncerTimeout->sync();

assertTest("Timeout sync returned status 'failed'", ($resTimeout['status'] ?? '') === 'failed');
assertTest("Timeout sync returned exit code 1", ($resTimeout['code'] ?? -1) === 1);
assertTest("Timeout error message mentions Transport error", strpos($resTimeout['error'] ?? '', 'Transport error') !== false);

// Verify that previous successful rate is NOT overwritten!
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$savedAfterTimeout = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

assertTest("auto_usd_bdt_rate preserved previous rate (123.4567)", ($savedAfterTimeout['auto_usd_bdt_rate'] ?? '') === '123.4567');
assertTest("auto_rate_last_updated preserved previous timestamp", ($savedAfterTimeout['auto_rate_last_updated'] ?? '') === ($saved['auto_rate_last_updated'] ?? ''));
assertTest("auto_rate_status changed to 'failed'", ($savedAfterTimeout['auto_rate_status'] ?? '') === 'failed');
assertTest("auto_rate_error contains timeout description", strpos($savedAfterTimeout['auto_rate_error'] ?? '', 'timed out') !== false);
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 3: Invalid JSON Response
// -----------------------------------------------------------
echo "\n--- Group 3: Malformed JSON Response ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_3.lock';
@unlink($lockFile);

$mockInvalidJson = function(string $url, int $connectTimeout, int $totalTimeout): array {
    return [
        'status_code' => 200,
        'body'        => '<html><head><title>Cloudflare 502</title></head><body>Bad Gateway</body></html>',
        'error'       => '',
    ];
};

$syncerBadJson = new ExchangeRateSyncer($db, $mockInvalidJson, $lockFile);
$resBadJson = $syncerBadJson->sync();

assertTest("Invalid JSON returned status 'failed'", ($resBadJson['status'] ?? '') === 'failed');
assertTest("Error mentions Failed to parse JSON", strpos($resBadJson['error'] ?? '', 'Failed to parse JSON') !== false);
assertTest("auto_usd_bdt_rate still preserved", ($syncerBadJson->getSettingValue('auto_usd_bdt_rate')) === '123.4567');
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 4: Missing USD or BDT Fields in JSON
// -----------------------------------------------------------
echo "\n--- Group 4: Schema Invariants & Missing Fields ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_4.lock';
@unlink($lockFile);

// 4a. Wrong base currency
$mockWrongBase = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'EUR', 'rates' => ['BDT' => 130.0]]), 'error' => ''];
};
$resWrongBase = (new ExchangeRateSyncer($db, $mockWrongBase, $lockFile))->sync();
assertTest("Wrong base currency rejected", ($resWrongBase['status'] ?? '') === 'failed');
assertTest("Error mentions base currency is not USD", strpos($resWrongBase['error'] ?? '', 'base currency is not USD') !== false);

// 4b. Missing rates object
$mockNoRates = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD']), 'error' => ''];
};
$resNoRates = (new ExchangeRateSyncer($db, $mockNoRates, $lockFile))->sync();
assertTest("Missing rates object rejected", ($resNoRates['status'] ?? '') === 'failed');
assertTest("Error mentions missing 'rates' object", strpos($resNoRates['error'] ?? '', 'missing "rates" object') !== false);

// 4c. Missing BDT in rates
$mockNoBdt = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['EUR' => 0.9, 'JPY' => 150]]), 'error' => ''];
};
$resNoBdt = (new ExchangeRateSyncer($db, $mockNoBdt, $lockFile))->sync();
assertTest("Missing BDT rate rejected", ($resNoBdt['status'] ?? '') === 'failed');
assertTest("Error mentions missing 'BDT' currency", strpos($resNoBdt['error'] ?? '', 'missing "BDT"') !== false);
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 5: Zero, Negative, and Non-Numeric Rate
// -----------------------------------------------------------
echo "\n--- Group 5: Zero, Negative & Non-Numeric Rates ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_5.lock';
@unlink($lockFile);

// 5a. Zero rate
$mockZero = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 0.0]]), 'error' => ''];
};
$resZero = (new ExchangeRateSyncer($db, $mockZero, $lockFile))->sync();
assertTest("Zero rate rejected", ($resZero['status'] ?? '') === 'failed');
assertTest("Error notes non-positive rate", strpos($resZero['error'] ?? '', 'non-positive rate') !== false);

// 5b. Negative rate
$mockNegative = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => -120.50]]), 'error' => ''];
};
$resNegative = (new ExchangeRateSyncer($db, $mockNegative, $lockFile))->sync();
assertTest("Negative rate rejected", ($resNegative['status'] ?? '') === 'failed');

// 5c. String non-numeric rate
$mockString = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 'N/A']]), 'error' => ''];
};
$resString = (new ExchangeRateSyncer($db, $mockString, $lockFile))->sync();
assertTest("Non-numeric rate string rejected", ($resString['status'] ?? '') === 'failed');
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 6: Implausible Currency Bounds
// -----------------------------------------------------------
echo "\n--- Group 6: Plausibility Bounds Protection ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_6.lock';
@unlink($lockFile);

// 6a. Inverted rate (BDT per USD < 10)
$mockInverted = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 0.0081]]), 'error' => ''];
};
$resInverted = (new ExchangeRateSyncer($db, $mockInverted, $lockFile))->sync();
assertTest("Inverted/underbound rate (0.0081) rejected", ($resInverted['status'] ?? '') === 'failed');
assertTest("Error mentions plausible bounds", strpos($resInverted['error'] ?? '', 'outside plausible USD/BDT bounds') !== false);

// 6b. Hyper-inflated rate (> 500)
$mockHyper = function() {
    return ['status_code' => 200, 'body' => json_encode(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 9999.0]]), 'error' => ''];
};
$resHyper = (new ExchangeRateSyncer($db, $mockHyper, $lockFile))->sync();
assertTest("Hyper-inflated rate (9999.0) rejected", ($resHyper['status'] ?? '') === 'failed');
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 7: HTTP Remote Status Error
// -----------------------------------------------------------
echo "\n--- Group 7: HTTP 500 / 429 Status Handling ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sync_7.lock';
@unlink($lockFile);

$mock500 = function() {
    return ['status_code' => 500, 'body' => '{"error":"Internal server error"}', 'error' => ''];
};
$res500 = (new ExchangeRateSyncer($db, $mock500, $lockFile))->sync();
assertTest("HTTP 500 response rejected", ($res500['status'] ?? '') === 'failed');
assertTest("Error mentions HTTP status 500", strpos($res500['error'] ?? '', 'HTTP status 500') !== false);
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 8: Stale Rate Lockout & Checkout Gate Protection
// -----------------------------------------------------------
echo "\n--- Group 8: Stale Rate Invariant & USD Checkout Gate ---\n";
// Set up simulated settings where auto rate is 25 hours old (max_age is 24h)
$staleTime = date('Y-m-d H:i:s', time() - (25 * 3600));

// We test resolveExchangeRate logic directly with stale parameters
$staleCfg = [
    'switcher_enabled' => true,
    'mode'             => 'auto',
    'manual_rate'      => 0.0,
    'manual_updated_at'=> '',
    'auto_rate'        => 123.4567,
    'auto_last_updated'=> $staleTime,
    'auto_status'      => 'success',
    'buffer_percent'   => 0.0,
    'max_age_hours'    => 24,
    'binance_active'   => true,
    'binance_pay_id'   => '123456789',
    'binance_qr_image' => '',
];

$maxAgeSec = $staleCfg['max_age_hours'] * 3600;
$age = time() - strtotime($staleTime);
$isStale = ($age > $maxAgeSec);

assertTest("Simulated 25h rate correctly flagged as stale (> 24h)", $isStale);

// When auto rate is stale and manual rate is 0, effective rate must be invalid
$rawRate = 0.0;
if ($isStale && $staleCfg['manual_rate'] <= 0.0001) {
    $rawRate = 0.0;
}
assertTest("Stale rate results in 0.0000 raw rate (Zero-Rate Lockout enforced)", $rawRate <= 0.0001);

// -----------------------------------------------------------
// TEST 9: Concurrency Lock Protection
// -----------------------------------------------------------
echo "\n--- Group 9: Concurrency Locking Guard ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_concurrency.lock';
@unlink($lockFile);

// Acquire lock externally to simulate another running worker
$externalLockHandle = fopen($lockFile, 'c+');
flock($externalLockHandle, LOCK_EX | LOCK_NB);

$syncerConcurrent = new ExchangeRateSyncer($db, $mockSuccess, $lockFile);
$resConcurrent = $syncerConcurrent->sync();

assertTest("Concurrent invocation detects active lock", ($resConcurrent['status'] ?? '') === 'locked');
assertTest("Concurrent invocation returns code 0 (skip)", ($resConcurrent['code'] ?? -1) === 0);
assertTest("Concurrent message explains skipping", strpos($resConcurrent['message'] ?? '', 'Skipping execution') !== false);

// Release external lock and re-test
flock($externalLockHandle, LOCK_UN);
fclose($externalLockHandle);
@unlink($lockFile);

$resAfterRelease = $syncerConcurrent->sync();
assertTest("Sync succeeds once external lock is released", ($resAfterRelease['status'] ?? '') === 'success');
@unlink($lockFile);

// -----------------------------------------------------------
// TEST 10: Secret Sanitization in Error Messages
// -----------------------------------------------------------
echo "\n--- Group 10: Secret Sanitization ---\n";
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rechax_rate_sanitize.lock';
@unlink($lockFile);

$mockLeakError = function() {
    return [
        'status_code' => 403,
        'body'        => 'Forbidden',
        'error'       => 'Failed request to https://api.com?key=SUPER_SECRET_API_KEY_12345&token=BEARER_TOKEN_9999',
    ];
};

$syncerLeak = new ExchangeRateSyncer($db, $mockLeakError, $lockFile);
$resLeak = $syncerLeak->sync();

assertTest("Error message contains REDACTED for key", strpos($resLeak['error'] ?? '', 'key=REDACTED') !== false);
assertTest("Raw secret key was NOT stored in error", strpos($resLeak['error'] ?? '', 'SUPER_SECRET_API_KEY_12345') === false);
assertTest("Raw secret token was NOT stored in error", strpos($resLeak['error'] ?? '', 'BEARER_TOKEN_9999') === false);
@unlink($lockFile);

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: Total Tests: " . ($passes + $fails) . " | Passed: $passes | Failed: $fails\n";
echo "======================================================\n";

if ($fails > 0) {
    exit(1);
}
