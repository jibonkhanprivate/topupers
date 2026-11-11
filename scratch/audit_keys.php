<?php
/**
 * scratch/audit_keys.php
 * Read-only settings compatibility audit script
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrate_v4_gate2b_currency_binance.php';

$pdo = db();
$liveSettings = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Total live settings: " . count($liveSettings) . "\n\n";

$canonical18 = Gate2BMigrationRunner::CANONICAL_SETTINGS_18;

echo "--- CANONICAL_SETTINGS_18 in Migration Runner ---\n";
foreach ($canonical18 as $k => $v) {
    $inDb = array_key_exists($k, $liveSettings) ? 'EXISTS IN DB (val: ' . var_export($liveSettings[$k], true) . ')' : 'MISSING FROM DB';
    echo sprintf("%-32s => %-25s | %s\n", $k, var_export($v, true), $inDb);
}

echo "\n--- CURRENCY.PHP KEYS ---\n";
// Inspect getExchangeRateConfig() from currency.php
require_once __DIR__ . '/../includes/currency.php';
$currRef = new ReflectionFunction('getExchangeRateConfig');
$currCode = file_get_contents($currRef->getFileName());
// Parse getSetting('...') calls in includes/currency.php
preg_match_all("/getSetting\(['\"]([^'\"]+)['\"](?:,\s*['\"]?([^'\")]*)['\"]?)?\)/", $currCode, $currMatches);
$currencyPhpKeys = array_unique($currMatches[1]);
print_r($currencyPhpKeys);

echo "\n--- ADMIN/SETTINGS.PHP KEYS ---\n";
$adminCode = file_get_contents(__DIR__ . '/../admin/settings.php');
// Extract $keys array
preg_match('/\$keys\s*=\s*\[(.*?)\];/s', $adminCode, $adminKeysBlock);
if (!empty($adminKeysBlock[1])) {
    preg_match_all("/'([^']+)'/", $adminKeysBlock[1], $admKeyMatches);
    echo "Keys defined in admin/settings.php \$keys list:\n";
    print_r($admKeyMatches[1]);
}

// Find all $rows['...'] in admin/settings.php
preg_match_all('/\$rows\[\'([^\']+)\'\]/', $adminCode, $rowsMatches);
echo "\nKeys read via \$rows in admin/settings.php:\n";
print_r(array_unique($rowsMatches[1]));

// Find all $stmt->execute(['key' in admin/settings.php
preg_match_all("/\$stmt->execute\(\['([a-zA-Z0-9_]+)'/", $adminCode, $stmtMatches);
echo "\nKeys written via direct \$stmt->execute in admin/settings.php:\n";
print_r(array_unique($stmtMatches[1]));
