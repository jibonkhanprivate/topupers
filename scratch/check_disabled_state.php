<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$db = db();

echo "=== 1. USD CHECKOUT & CURRENCY SWITCHER ===\n";
$switcherSetting = getSetting('currency_switcher_enabled', '0');
echo "Setting `currency_switcher_enabled`: " . var_export($switcherSetting, true) . "\n";

$usdAllowed = isUsdCheckoutAllowed();
echo "isUsdCheckoutAllowed(): " . ($usdAllowed['allowed'] ? 'TRUE (ENABLED)' : 'FALSE (BLOCKED)') . "\n";
echo "Reason: " . ($usdAllowed['reason'] ?? 'N/A') . "\n";

echo "\n=== 2. BINANCE PAY ===\n";
$binanceSetting = getSetting('binance_active', '0');
echo "Setting `binance_active`: " . var_export($binanceSetting, true) . "\n";

$binanceMethod = $db->query("SELECT * FROM payment_methods WHERE method_key = 'binance'")->fetch(PDO::FETCH_ASSOC);
echo "Payment Method 'binance' in `payment_methods`: " . ($binanceMethod ? json_encode($binanceMethod) : "ABSENT (clean baseline)") . "\n";

echo "\n=== 3. EXCHANGE-RATE CRON JOBS ===\n";
$crons = $db->query("SELECT * FROM cron_jobs WHERE job_key LIKE '%currency%' OR job_key LIKE '%rate%' OR job_key LIKE '%exchange%'")->fetchAll(PDO::FETCH_ASSOC);
echo "Currency / Exchange Rate cron jobs in `cron_jobs`: " . (empty($crons) ? "ABSENT (clean baseline)" : json_encode($crons)) . "\n";

$allCrons = $db->query("SELECT id, name, job_key, is_active FROM cron_jobs")->fetchAll(PDO::FETCH_ASSOC);
echo "All Active Cron Jobs in database:\n";
foreach ($allCrons as $cj) {
    echo "  - [{$cj['job_key']}] {$cj['name']}: " . ($cj['is_active'] ? 'ACTIVE' : 'INACTIVE') . "\n";
}
