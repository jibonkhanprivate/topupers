<?php
/**
 * includes/currency.php
 *
 * TopUpers / RechaX Official — Core Multi-Currency & Exchange Rate Engine
 * Strict BDT Default | Session-Persisted BDT/USD | Mathematical Precision | Zero-Rate Lockout
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (!function_exists('getSetting')) {
    require_once __DIR__ . '/functions.php';
}

// ============================================================
// 1. Constants & Supported Currencies Contract
// ============================================================
if (!defined('CURRENCY_DEFAULT')) {
    define('CURRENCY_DEFAULT', 'BDT');
}

const SUPPORTED_CURRENCIES = [
    'BDT' => [
        'code'          => 'BDT',
        'symbol'        => '৳',
        'name'          => 'Bangladeshi Taka',
        'decimal_places'=> 0,
        'format_prefix' => '৳ ',
    ],
    'USD' => [
        'code'          => 'USD',
        'symbol'        => '$',
        'name'          => 'United States Dollar',
        'decimal_places'=> 2,
        'format_prefix' => '$ ',
    ],
];
// ============================================================
// 2. Country Detection & Currency State Engine
// ============================================================

/**
 * Official Cloudflare Public Proxy IP CIDR Ranges (IPv4 & IPv6).
 * https://www.cloudflare.com/ips/
 */
const CLOUDFLARE_IPV4_CIDRS = [
    '173.245.48.0/20',
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '141.101.64.0/18',
    '108.162.192.0/18',
    '190.93.240.0/20',
    '188.114.96.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17',
    '162.158.0.0/15',
    '104.16.0.0/13',
    '104.24.0.0/14',
    '172.64.0.0/13',
    '131.0.72.0/22',
];

const CLOUDFLARE_IPV6_CIDRS = [
    '2400:cb00::/32',
    '2606:4700::/32',
    '2803:f800::/32',
    '2405:b500::/32',
    '2405:8100::/32',
    '2a06:98c0::/29',
    '2c0f:f248::/32',
];

/**
 * Evaluate if an IPv4 address belongs to a CIDR range.
 */
function ipInIpv4Cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    [$subnet, $mask] = explode('/', $cidr, 2);
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    $maskBits = (int)$mask;
    if ($maskBits <= 0) return true;
    if ($maskBits >= 32) return $ipLong === $subnetLong;
    $netmask = ~((1 << (32 - $maskBits)) - 1);
    return ($ipLong & $netmask) === ($subnetLong & $netmask);
}

/**
 * Evaluate if an IPv6 address belongs to a CIDR range.
 */
function ipInIpv6Cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) {
        return @inet_pton($ip) === @inet_pton($cidr);
    }
    [$subnet, $mask] = explode('/', $cidr, 2);
    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);
    if ($ipBin === false || $subnetBin === false) {
        return false;
    }
    $maskBits = (int)$mask;
    $bytes = (int)($maskBits / 8);
    $bits = $maskBits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
        return false;
    }
    if ($bits > 0) {
        $byteMask = ~(0xFF >> $bits) & 0xFF;
        $ipByte = ord($ipBin[$bytes]);
        $subnetByte = ord($subnetBin[$bytes]);
        if (($ipByte & $byteMask) !== ($subnetByte & $byteMask)) {
            return false;
        }
    }
    return true;
}

/**
 * Check whether a given remote IP belongs to trusted Cloudflare proxy or custom configured trusted proxies.
 */
function isTrustedProxy(string $ip): bool {
    $ip = trim($ip);
    if (empty($ip)) {
        return false;
    }

    // Check Cloudflare ranges
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        foreach (CLOUDFLARE_IPV4_CIDRS as $cidr) {
            if (ipInIpv4Cidr($ip, $cidr)) {
                return true;
            }
        }
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        foreach (CLOUDFLARE_IPV6_CIDRS as $cidr) {
            if (ipInIpv6Cidr($ip, $cidr)) {
                return true;
            }
        }
    }

    // Check custom trusted proxies from settings or mock config
    global $MOCK_EXCHANGE_RATE_CONFIG;
    $customProxies = '';
    if (is_array($MOCK_EXCHANGE_RATE_CONFIG) && isset($MOCK_EXCHANGE_RATE_CONFIG['trusted_proxies'])) {
        $customProxies = trim((string)$MOCK_EXCHANGE_RATE_CONFIG['trusted_proxies']);
    } elseif (function_exists('getSetting')) {
        $customProxies = trim((string)getSetting('trusted_proxies', ''));
    }
    if (!empty($customProxies)) {
        $proxyList = array_map('trim', explode(',', $customProxies));
        foreach ($proxyList as $proxyCidr) {
            if (empty($proxyCidr)) continue;
            if (strpos($proxyCidr, ':') !== false) {
                if (ipInIpv6Cidr($ip, $proxyCidr)) return true;
            } else {
                if (ipInIpv4Cidr($ip, $proxyCidr)) return true;
            }
        }
    }

    return false;
}

/**
 * Shared server-side Country Detection Helper.
 * Detects customer country code (2-letter ISO, e.g. 'BD', 'US').
 *
 * Safety & Security Rules:
 * 1. If country detection is disabled in settings ('country_detection_enabled' !== '1'),
 *    safely defaults to 'BD'.
 * 2. Only trusts CF-IPCountry when the request is confirmed to come through a trusted
 *    Cloudflare proxy or explicit trusted proxy.
 * 3. Never trusts arbitrary client-supplied CF-IPCountry, X-Country, etc. on untrusted/direct connections.
 * 4. In local development or when proxy verification fails, defaults safely to 'BD'.
 */
function detectCustomerCountry(?string $overrideIp = null): string {
    global $MOCK_CUSTOMER_COUNTRY;
    if (isset($MOCK_CUSTOMER_COUNTRY) && is_string($MOCK_CUSTOMER_COUNTRY) && preg_match('/^[A-Z]{2}$/', $MOCK_CUSTOMER_COUNTRY)) {
        return $MOCK_CUSTOMER_COUNTRY;
    }

    global $MOCK_EXCHANGE_RATE_CONFIG;
    $enabled = false;
    if (is_array($MOCK_EXCHANGE_RATE_CONFIG) && isset($MOCK_EXCHANGE_RATE_CONFIG['country_detection_enabled'])) {
        $enabled = ((string)$MOCK_EXCHANGE_RATE_CONFIG['country_detection_enabled'] === '1' || $MOCK_EXCHANGE_RATE_CONFIG['country_detection_enabled'] === true);
    } elseif (function_exists('getSetting')) {
        $enabled = (getSetting('country_detection_enabled', '0') === '1');
    }
    if (!$enabled) {
        return 'BD';
    }

    $remoteIp = $overrideIp ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    if (empty($remoteIp) || !isTrustedProxy($remoteIp)) {
        // Untrusted connection (direct origin access or local development) — default safe to 'BD'
        return 'BD';
    }

    $cfCountry = strtoupper(trim((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    if (preg_match('/^[A-Z]{2}$/', $cfCountry) && $cfCountry !== 'XX' && $cfCountry !== 'T1') {
        return $cfCountry;
    }

    return 'BD';
}

/**
 * Internal safe session initializer that respects existing sessions and headers.
 */
function ensureCurrencySessionActive(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (function_exists('startUserSession')) {
        @startUserSession();
    } elseif (session_status() === PHP_SESSION_NONE) {
        if (defined('SESSION_NAME')) {
            @session_name(SESSION_NAME);
        }
        @session_start();
    }
}

/**
 * Check if a currency code is strictly valid (BDT or USD).
 */
function isValidCurrency(string $code): bool {
    $normalized = strtoupper(trim($code));
    return isset(SUPPORTED_CURRENCIES[$normalized]);
}

/**
 * Retrieve the current active customer currency.
 * Automatically resolved from server-side country detection:
 * - Bangladesh customers ('BD') strictly use 'BDT'.
 * - International customers (non-'BD') automatically use 'USD'.
 * No customer-facing switcher is permitted.
 */
function getActiveCurrency(): string {
    global $MOCK_ACTIVE_CURRENCY;
    if (isset($MOCK_ACTIVE_CURRENCY) && isValidCurrency($MOCK_ACTIVE_CURRENCY)) {
        return strtoupper($MOCK_ACTIVE_CURRENCY);
    }

    $country = detectCustomerCountry();
    if ($country === 'BD') {
        return 'BDT';
    }

    return 'USD';
}

/**
 * Explicitly set the active currency in session (internal / programmatic only).
 */
function setActiveCurrency(string $code): bool {
    $normalized = strtoupper(trim($code));
    if (!isValidCurrency($normalized)) {
        return false;
    }

    ensureCurrencySessionActive();

    if (session_status() === PHP_SESSION_ACTIVE || isset($_SESSION)) {
        $_SESSION['currency'] = $normalized;
    }
    return true;
}

/**
 * Get symbol for a currency ('৳' for BDT, '$' for USD).
 */
function getCurrencySymbol(?string $currency = null): string {
    $curr = $currency ? strtoupper(trim($currency)) : getActiveCurrency();
    return SUPPORTED_CURRENCIES[$curr]['symbol'] ?? '৳';
}

// ============================================================
// 3. Exchange Rate Invariants & Mathematical Resolution
// ============================================================

/**
 * Read exchange-rate configurations from settings (or safe defaults).
 */
function getExchangeRateConfig(): array {
    global $MOCK_EXCHANGE_RATE_CONFIG;
    if (is_array($MOCK_EXCHANGE_RATE_CONFIG)) {
        return array_merge([
            'switcher_enabled'          => false,
            'country_detection_enabled' => false,
            'mode'                      => 'auto',
            'manual_rate'               => 0.0,
            'manual_updated_at'         => '',
            'auto_rate'                 => 0.0,
            'auto_last_updated'         => '',
            'auto_status'               => 'unconfigured',
            'buffer_percent'            => 3.0,
            'decimal_precision'         => 2,
            'max_age_hours'             => 24,
            'binance_active'            => false,
            'binance_pay_id'            => '',
            'binance_qr_image'          => '',
            'binance_instructions'      => '',
            'api_provider'              => 'exchangerate-api',
            'auto_api_url'              => 'https://open.er-api.com/v6/latest/USD',
            'trusted_proxies'           => '',
        ], $MOCK_EXCHANGE_RATE_CONFIG);
    }

    return [
        'switcher_enabled'          => function_exists('getSetting') ? (getSetting('currency_switcher_enabled', '0') === '1') : false,
        'country_detection_enabled' => function_exists('getSetting') ? (getSetting('country_detection_enabled', '0') === '1') : false,
        'mode'                      => function_exists('getSetting') ? strtolower(getSetting('exchange_rate_mode', 'auto')) : 'auto',
        'manual_rate'               => function_exists('getSetting') ? (float)getSetting('manual_usd_bdt_rate', '0.0000') : 0.0,
        'manual_updated_at'         => function_exists('getSetting') ? getSetting('manual_rate_updated_at', '') : '',
        'auto_rate'                 => function_exists('getSetting') ? (float)getSetting('auto_usd_bdt_rate', '0.0000') : 0.0,
        'auto_last_updated'         => function_exists('getSetting') ? getSetting('auto_rate_last_updated', '') : '',
        'auto_status'               => function_exists('getSetting') ? getSetting('auto_rate_status', 'unconfigured') : 'unconfigured',
        'buffer_percent'            => function_exists('getSetting') ? max(0.0, (float)getSetting('rate_buffer_percent', '3.00')) : 3.0,
        'decimal_precision'         => function_exists('getSetting') ? max(0, min(4, (int)getSetting('usd_decimal_precision', '2'))) : 2,
        'max_age_hours'             => function_exists('getSetting') ? max(1, (int)getSetting('rate_max_age_hours', '24')) : 24,
        'binance_active'            => function_exists('getSetting') ? (getSetting('binance_active', '0') === '1') : false,
        'binance_pay_id'            => function_exists('getSetting') ? getSetting('binance_pay_id', '') : '',
        'binance_qr_image'          => function_exists('getSetting') ? getSetting('binance_qr_image', '') : '',
        'binance_instructions'      => function_exists('getSetting') ? getSetting('binance_instructions', '') : '',
        'api_provider'              => function_exists('getSetting') ? getSetting('exchange_rate_api_provider', 'exchangerate-api') : 'exchangerate-api',
        'auto_api_url'              => function_exists('getSetting') ? getSetting('auto_rate_api_url', 'https://open.er-api.com/v6/latest/USD') : 'https://open.er-api.com/v6/latest/USD',
        'trusted_proxies'           => function_exists('getSetting') ? getSetting('trusted_proxies', '') : '',
    ];
}

/**
 * Resolve effective exchange rate and evaluate safety invariants.
 *
 * Invariant Rules:
 * - Rate must be strictly greater than 0.0001 (Zero-Rate Lockout).
 * - Automatic mode evaluates freshness against rate_max_age_hours.
 * - If the provider temporarily fails while cached rate is still fresh, cached rate is used until expiry.
 * - If automated mode is stale or unconfigured, fall back to valid manual rate.
 * - If manual rate is also zero, flag as invalid.
 */
function resolveExchangeRate(): array {
    $cfg = getExchangeRateConfig();

    $chosenMode = $cfg['mode'] === 'auto' ? 'auto' : 'manual';
    $rawRate = 0.0;
    $source = 'unconfigured';
    $isStale = false;
    $lastUpdated = '';

    if ($chosenMode === 'auto') {
        // Evaluate auto freshness
        $lastUpdateTs = !empty($cfg['auto_last_updated']) ? strtotime($cfg['auto_last_updated']) : 0;
        $maxAgeSeconds = $cfg['max_age_hours'] * 3600;

        // If a positive auto rate was cached, check age:
        if ($cfg['auto_rate'] > 0.0001 && $lastUpdateTs > 0) {
            $ageSeconds = time() - $lastUpdateTs;
            if ($ageSeconds <= $maxAgeSeconds) {
                // Rate is fresh: use it even if temporary fetch failed on latest cron cycle
                $rawRate = $cfg['auto_rate'];
                $source = ($cfg['auto_status'] === 'success') ? 'auto_api' : 'auto_cached';
                $lastUpdated = $cfg['auto_last_updated'];
            } else {
                $isStale = true;
            }
        } else {
            $isStale = true;
        }

        // Stale or unconfigured auto fallback to valid manual rate
        if ($rawRate <= 0.0001) {
            if ($cfg['manual_rate'] > 0.0001) {
                $rawRate = $cfg['manual_rate'];
                $source = 'manual_fallback_due_to_stale_auto';
                $lastUpdated = $cfg['manual_updated_at'];
            }
        }
    } else {
        // Manual mode
        if ($cfg['manual_rate'] > 0.0001) {
            $rawRate = $cfg['manual_rate'];
            $source = 'manual_admin';
            $lastUpdated = $cfg['manual_updated_at'];
        }
    }

    $isValid = ($rawRate > 0.0001);
    $errorReason = null;

    if (!$isValid) {
        $errorReason = $isStale
            ? 'Automated exchange rate is stale (exceeds max age of ' . $cfg['max_age_hours'] . 'h) and no valid manual fallback rate is configured.'
            : 'Exchange rate is unconfigured or zero.';
    }

    // Effective rate is raw market rate (1 USD = rawRate BDT)
    return [
        'is_valid'                  => $isValid,
        'is_available'              => $isValid,
        'switcher_enabled'          => $cfg['switcher_enabled'],
        'country_detection_enabled' => $cfg['country_detection_enabled'],
        'mode'                      => $chosenMode,
        'source'            => $source,
        'raw_rate'          => round($rawRate, 4),
        'buffer_percent'    => round($cfg['buffer_percent'], 2),
        'decimal_precision' => (int)($cfg['decimal_precision'] ?? 2),
        'effective_rate'    => round($rawRate, 4),
        'last_updated'      => $lastUpdated,
        'error_reason'      => $errorReason,
        'binance_active'    => $cfg['binance_active'],
    ];
}

// ============================================================
// 4. Currency Conversion & Price Calculation Helpers
// ============================================================

/**
 * Convert a canonical catalog BDT amount into USD.
 *
 * Mathematical Formula:
 * raw_usd = bdt_amount / raw_rate
 * with_buffer = raw_usd * (1 + (buffer_percent / 100))
 * charged_usd = max(0.01, round(with_buffer, precision, PHP_ROUND_HALF_UP))
 *
 * Returns null if conversion cannot be performed safely (rate is 0 or invalid).
 */
function convertBdtToUsd(float $bdtAmount): ?array {
    $cfg = getExchangeRateConfig();
    $precision = (int)($cfg['decimal_precision'] ?? 2);

    if ($bdtAmount <= 0.0) {
        return [
            'bdt_amount'      => 0.00,
            'usd_amount'      => 0.00,
            'exchange_rate'   => 1.0000,
            'buffer_percent'  => 0.00,
            'rate_mode'       => 'canonical',
            'rate_source'     => 'canonical_bdt',
            'charge_amount'   => 0.00,
            'charge_currency' => 'USD',
        ];
    }

    $rateDetails = resolveExchangeRate();
    if (!$rateDetails['is_valid'] || $rateDetails['raw_rate'] <= 0.0001) {
        return null;
    }

    $rawRate = $rateDetails['raw_rate'];
    $buffer = $rateDetails['buffer_percent'];

    $rawUsd = $bdtAmount / $rawRate;
    $withBuffer = $rawUsd * (1.0 + ($buffer / 100.0));
    $chargeUsd = max(0.01, round($withBuffer, $precision, PHP_ROUND_HALF_UP));

    return [
        'bdt_amount'      => round($bdtAmount, 2),
        'usd_amount'      => $chargeUsd,
        'exchange_rate'   => $rawRate,
        'buffer_percent'  => $buffer,
        'rate_mode'       => $rateDetails['mode'],
        'rate_source'     => $rateDetails['source'],
        'charge_amount'   => $chargeUsd,
        'charge_currency' => 'USD',
    ];
}

/**
 * Format a catalog BDT price according to the active (or requested) currency.
 *
 * Example Outputs:
 * - BDT: "৳ 1,000"
 * - USD (Rate 120.00, Buffer 3%): "$ 8.58"
 * - USD (When rate is unconfigured/0): Safe fallback to "৳ 1,000"
 */
function formatPriceByCurrency(float $bdtAmount, ?string $currency = null): string {
    $targetCurrency = $currency ? strtoupper(trim($currency)) : getActiveCurrency();

    if ($targetCurrency === 'USD') {
        $converted = convertBdtToUsd($bdtAmount);
        if ($converted !== null && ($converted['usd_amount'] ?? 0) > 0) {
            $cfg = getExchangeRateConfig();
            $precision = (int)($cfg['decimal_precision'] ?? 2);
            return '$ ' . number_format($converted['usd_amount'], $precision, '.', ',');
        }
        // Safe fallback to BDT catalog price if USD rate is unavailable
    }

    return '৳ ' . number_format($bdtAmount, 0, '.', ',');
}

/**
 * Check whether the database schema contains all required Gate 2B currency snapshot columns on orders.
 * Verifies the 7 pricing snapshot columns specified in migrate_v4_gate2b_currency_binance.php:
 * currency, base_amount_bdt, exchange_rate, rate_buffer_percent, rate_mode, rate_source, charge_amount.
 */
function hasOrderCurrencySnapshotColumns(?PDO $pdo = null): bool {
    global $MOCK_EXCHANGE_RATE_CONFIG;
    if (is_array($MOCK_EXCHANGE_RATE_CONFIG) && array_key_exists('mock_has_snapshot_columns', $MOCK_EXCHANGE_RATE_CONFIG)) {
        return (bool)$MOCK_EXCHANGE_RATE_CONFIG['mock_has_snapshot_columns'];
    }

    static $hasCols = null;
    if ($hasCols !== null && $pdo === null) {
        return $hasCols;
    }

    $requiredColumns = [
        'currency',
        'base_amount_bdt',
        'exchange_rate',
        'rate_buffer_percent',
        'rate_mode',
        'rate_source',
        'charge_amount'
    ];

    try {
        $db = $pdo ?? (function_exists('db') ? db() : null);
        if (!$db) {
            return false;
        }

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $db->query("PRAGMA table_info(`orders`)");
            $existingCols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 1) : [];
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM `orders`");
            $existingCols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        }

        if (empty($existingCols)) {
            return false;
        }

        $existingMap = array_fill_keys(array_map('strtolower', $existingCols), true);

        $allPresent = true;
        foreach ($requiredColumns as $col) {
            if (!isset($existingMap[strtolower($col)])) {
                $allPresent = false;
                break;
            }
        }

        if ($pdo === null) {
            $hasCols = $allPresent;
        }
        return $allPresent;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Check whether USD checkout is allowed to proceed.
 * Requires:
 * 1. Exchange rate strictly positive and valid.
 * 2. Binance Pay active in configuration.
 * 3. Binance Pay merchant credentials (Pay ID or QR) configured.
 * 4. Database schema supports Gate 2B snapshot columns.
 */
function isUsdCheckoutAllowed(): array {
    $cfg = getExchangeRateConfig();
    $rateInfo = resolveExchangeRate();

    if (!$rateInfo['is_valid']) {
        return [
            'allowed' => false,
            'reason'  => $rateInfo['error_reason'] ?? 'Exchange rate is zero, unconfigured, or stale.',
        ];
    }

    if (!$cfg['binance_active']) {
        return [
            'allowed' => false,
            'reason'  => 'Binance Pay is not enabled by administrator.',
        ];
    }

    if (empty(trim((string)$cfg['binance_pay_id'])) && empty(trim((string)$cfg['binance_qr_image']))) {
        return [
            'allowed' => false,
            'reason'  => 'Binance Pay merchant credentials (Pay ID / QR) are not configured.',
        ];
    }

    if (!hasOrderCurrencySnapshotColumns()) {
        return [
            'allowed' => false,
            'reason'  => 'Database schema does not support currency snapshots (migration pending).',
        ];
    }

    return [
        'allowed' => true,
        'reason'  => null,
    ];
}

/**
 * Retrieve active customer instructions for Binance Pay checkout.
 */
function getBinanceInstructions(): string {
    $cfg = getExchangeRateConfig();
    $instructions = trim((string)($cfg['binance_instructions'] ?? ''));
    if ($instructions !== '') {
        return $instructions;
    }
    return 'Send the exact USD amount via Binance Pay (Pay ID / UID or QR scan). Enter your Binance Pay Order ID / Transaction ID below.';
}

/**
 * Safely format an order's monetary amount with complete historical backward-compatibility.
 * - USD orders format as "$ 10.50" with 2 decimals from charge_amount.
 * - Historical / BDT orders format as "৳ 1,000" with 0 decimals from base_amount_bdt or total_price.
 */
function formatOrderAmount(array $order): string {
    $curr = strtoupper(trim((string)($order['currency'] ?? 'BDT')));

    if ($curr === 'USD') {
        $usdAmount = isset($order['charge_amount']) && (float)$order['charge_amount'] > 0
            ? (float)$order['charge_amount']
            : (float)($order['total_price'] ?? 0);
        return '$ ' . number_format($usdAmount, 2, '.', ',');
    }

    // Default / Historical BDT
    $bdtAmount = isset($order['base_amount_bdt']) && (float)$order['base_amount_bdt'] > 0
        ? (float)$order['base_amount_bdt']
        : (float)($order['total_price'] ?? 0);
    return '৳ ' . number_format($bdtAmount, 0, '.', ',');
}

// ============================================================
// 5. Payment Methods & Order Validation Engine
// ============================================================

/**
 * Retrieve active, valid payment methods filtered by customer currency / location.
 *
 * Bangladesh Customers (BDT):
 * - Methods: bKash, Nagad, Rocket, Upay ONLY.
 * - Binance Pay is strictly excluded.
 *
 * International Customers (USD):
 * - Methods: Binance Pay ONLY.
 * - bKash, Nagad, Rocket, Upay are strictly excluded.
 * - If Binance Pay is unconfigured, disabled, or rate is stale/zero, returns empty array.
 */
function getAvailablePaymentMethods(string $currency = 'BDT', ?PDO $pdo = null): array {
    $db = $pdo ?? (function_exists('db') ? db() : null);
    $curr = strtoupper(trim($currency));

    if ($curr === 'BDT') {
        $allowedKeys = ['bkash', 'nagad', 'rocket', 'upay'];
        $methods = [];

        if ($db) {
            try {
                $stmt = $db->query("SELECT * FROM payment_methods WHERE is_active = 1 AND method_key IN ('bkash','nagad','rocket','upay') ORDER BY sort_order ASC");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($rows as $r) {
                    $k = strtolower(trim((string)$r['method_key']));
                    if (in_array($k, $allowedKeys, true)) {
                        $methods[$k] = [
                            'key'            => $k,
                            'method_key'     => $k,
                            'label'          => $r['method_label'] ?: ucfirst($k),
                            'method_label'   => $r['method_label'] ?: ucfirst($k),
                            'name'           => $r['method_label'] ?: ucfirst($k),
                            'icon'           => $r['icon'] ?? '',
                            'account_number' => $r['account_number'] ?? (function_exists('getSetting') ? getSetting("{$k}_number", '') : ''),
                            'instructions'   => $r['instructions'] ?? (function_exists('getSetting') ? getSetting("{$k}_instruction", '') : ''),
                        ];
                    }
                }
            } catch (Throwable $e) {}
        }

        // If database had no rows or was missing any allowed method, fall back to constants/settings
        if (empty($methods) && defined('PAYMENT_METHODS')) {
            foreach (PAYMENT_METHODS as $k => $info) {
                if (in_array($k, $allowedKeys, true)) {
                    $methods[$k] = [
                        'key'            => $k,
                        'method_key'     => $k,
                        'label'          => $info['label'] ?? ucfirst($k),
                        'method_label'   => $info['label'] ?? ucfirst($k),
                        'name'           => $info['label'] ?? ucfirst($k),
                        'icon'           => $info['icon'] ?? '',
                        'account_number' => function_exists('getSetting') ? getSetting("{$k}_number", '') : '',
                        'instructions'   => function_exists('getSetting') ? getSetting("{$k}_instruction", '') : '',
                    ];
                }
            }
        }

        return $methods;
    }

    if ($curr === 'USD') {
        $usdCheck = isUsdCheckoutAllowed();
        if (!$usdCheck['allowed']) {
            return [];
        }

        $cfg = getExchangeRateConfig();
        return [
            'binance' => [
                'key'            => 'binance',
                'method_key'     => 'binance',
                'label'          => 'Binance Pay',
                'method_label'   => 'Binance Pay',
                'name'           => 'Binance Pay',
                'icon'           => 'bi-currency-bitcoin',
                'account_number' => $cfg['binance_pay_id'] ?? '',
                'instructions'   => getBinanceInstructions(),
                'qr_image'       => $cfg['binance_qr_image'] ?? '',
            ]
        ];
    }

    return [];
}

/**
 * Validate whether a payment method key is allowed for the given currency.
 */
function isPaymentMethodAllowedForCurrency(string $methodKey, string $currency): bool {
    $k = strtolower(trim($methodKey));
    $c = strtoupper(trim($currency));

    if ($c === 'BDT') {
        return in_array($k, ['bkash', 'nagad', 'rocket', 'upay'], true);
    }
    if ($c === 'USD') {
        if ($k !== 'binance') {
            return false;
        }
        $usdCheck = isUsdCheckoutAllowed();
        return (bool)$usdCheck['allowed'];
    }
    return false;
}

/**
 * Shared server-side order validation and pricing snapshot calculator.
 * Enforces:
 * 1. Product price retrieval from database (never trust client amounts).
 * 2. Coupon validation in canonical BDT (limits, expiry, min purchase).
 * 3. Fail-closed USD checkout eligibility.
 * 4. Payment method allowlisting and currency cross-contamination separation.
 * 5. Duplicate transaction-reference protection.
 */
function validateAndPrepareOrder(
    PDO $pdo,
    int $productId,
    int $gameId,
    ?string $couponCode,
    string $currency,
    string $paymentMethod,
    string $paymentTrx,
    string $paymentNumber
): array {
    // 1. Verify Product & Canonical Price
    if ($productId <= 0 || $gameId <= 0) {
        return ['valid' => false, 'error' => 'Please select a valid game package.'];
    }
    $pStmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND game_id = ? AND is_active = 1');
    $pStmt->execute([$productId, $gameId]);
    $verifiedProduct = $pStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verifiedProduct) {
        return ['valid' => false, 'error' => 'Selected package is invalid or currently unavailable.'];
    }

    $basePriceBdt = (float)$verifiedProduct['amount'];
    if ($basePriceBdt <= 0.0) {
        return ['valid' => false, 'error' => 'Package has an invalid price configuration.'];
    }

    // 2. Server-Side Coupon Verification & Calculation in BDT
    $discountBdt = 0.0;
    $usedCouponCode = null;
    $couponIdToIncrement = null;

    if (!empty($couponCode)) {
        $cStmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1');
        $cStmt->execute([strtoupper(trim($couponCode))]);
        $c = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$c) {
            return ['valid' => false, 'error' => 'Invalid or expired promo code.'];
        }
        if (!empty($c['usage_limit']) && (int)$c['used_count'] >= (int)$c['usage_limit']) {
            return ['valid' => false, 'error' => 'Promo code usage limit has been reached.'];
        }
        if (!empty($c['expires_at']) && strtotime($c['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Promo code has expired.'];
        }
        $minPurchase = (float)($c['min_purchase'] ?? 0);
        if ($minPurchase > 0 && $basePriceBdt < $minPurchase) {
            return ['valid' => false, 'error' => 'Minimum spend for promo code not met (Min: ' . formatPriceByCurrency($minPurchase) . ').'];
        }

        if (($c['type'] ?? 'fixed') === 'percent') {
            $calc = $basePriceBdt * ((float)$c['value'] / 100.0);
            $maxDisc = (float)($c['max_discount'] ?? 0);
            if ($maxDisc > 0 && $calc > $maxDisc) {
                $calc = $maxDisc;
            }
            $discountBdt = round($calc, 2);
        } else {
            $discountBdt = round((float)$c['value'], 2);
        }

        if ($discountBdt > $basePriceBdt) {
            $discountBdt = $basePriceBdt;
        }

        $usedCouponCode = $c['code'];
        $couponIdToIncrement = (int)$c['id'];
    }

    $netBdtAmount = max(0.00, $basePriceBdt - $discountBdt);

    // 3. Currency Eligibility & Snapshot Calculation
    $currNormalized = strtoupper(trim($currency));
    if (!in_array($currNormalized, ['BDT', 'USD'], true)) {
        return ['valid' => false, 'error' => 'Unsupported checkout currency.'];
    }

    $cleanTrx = strtoupper(trim($paymentTrx));
    $cleanNumber = trim($paymentNumber);

    $snapshot = [];
    if ($currNormalized === 'BDT') {
        $snapshot = [
            'currency'            => 'BDT',
            'base_amount_bdt'     => $netBdtAmount,
            'exchange_rate'       => 1.0000,
            'rate_buffer_percent' => 0.00,
            'rate_mode'           => 'canonical',
            'rate_source'         => 'canonical_bdt',
            'charge_amount'       => $netBdtAmount,
            'total_price'         => $netBdtAmount,
            'payment_details'     => json_encode([
                'method'  => strtolower(trim($paymentMethod)),
                'account' => $cleanNumber,
                'trx_id'  => $cleanTrx,
                'currency'=> 'BDT',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    } else {
        // USD checkout
        $usdEligibility = isUsdCheckoutAllowed();
        if (!$usdEligibility['allowed']) {
            return [
                'valid' => false,
                'error' => 'USD checkout is currently unavailable. ' . ($usdEligibility['reason'] ?? 'Please place your order in BDT.')
            ];
        }

        $conversion = convertBdtToUsd($netBdtAmount);
        if ($conversion === null) {
            return ['valid' => false, 'error' => 'Unable to calculate USD exchange conversion.'];
        }

        $snapshot = [
            'currency'            => 'USD',
            'base_amount_bdt'     => $netBdtAmount,
            'exchange_rate'       => $conversion['exchange_rate'],
            'rate_buffer_percent' => $conversion['buffer_percent'],
            'rate_mode'           => $conversion['rate_mode'],
            'rate_source'         => $conversion['rate_source'],
            'charge_amount'       => $conversion['charge_amount'],
            'total_price'         => $netBdtAmount, // Canonical BDT in orders.total_price!
            'payment_details'     => json_encode([
                'method'        => 'binance',
                'pay_id'        => $cleanNumber,
                'trx_id'        => $cleanTrx,
                'currency'      => 'USD',
                'usd_amount'    => $conversion['charge_amount'],
                'base_bdt'      => $netBdtAmount,
                'exchange_rate' => $conversion['exchange_rate'],
                'buffer_percent'=> $conversion['buffer_percent'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    // 4. Payment Method Allowlist & Cross-Contamination Guard
    $pmKey = strtolower(trim($paymentMethod));
    if (empty($pmKey)) {
        return ['valid' => false, 'error' => 'Please select a payment method.'];
    }

    if ($currNormalized === 'BDT') {
        if ($pmKey === 'binance') {
            return ['valid' => false, 'error' => 'Binance Pay is only supported for USD orders. Please choose a local payment method for BDT checkout.'];
        }
        if (!in_array($pmKey, ['bkash', 'nagad', 'rocket', 'upay'], true)) {
            return ['valid' => false, 'error' => 'The selected payment method is invalid for BDT orders. Allowed methods: bKash, Nagad, Rocket, Upay.'];
        }
    } else { // USD
        if ($pmKey !== 'binance') {
            return ['valid' => false, 'error' => 'Only Binance Pay is accepted for international USD checkout.'];
        }
    }

    // Verify active in database (or settings for binance)
    if ($pmKey === 'binance') {
        $cfg = getExchangeRateConfig();
        if (!$cfg['binance_active']) {
            return ['valid' => false, 'error' => 'Binance Pay is not enabled by administrator.'];
        }
    } else {
        $pmStmt = $pdo->prepare("SELECT method_key FROM payment_methods WHERE method_key = ? AND is_active = 1");
        $pmStmt->execute([$pmKey]);
        if (!$pmStmt->fetch()) {
            // Check fallback settings
            $pmNum = function_exists('getSetting') ? getSetting("{$pmKey}_number", '') : '';
            if (empty(trim((string)$pmNum))) {
                return ['valid' => false, 'error' => 'The selected payment method is invalid or currently inactive.'];
            }
        }
    }

    // 5. Account Number & Duplicate Transaction Reference Protection
    if (empty($cleanNumber)) {
        return ['valid' => false, 'error' => ($currNormalized === 'USD' ? 'Please enter your Binance Pay ID / UID.' : 'Please enter your payment account number / wallet ID.')];
    }

    if (empty($cleanTrx)) {
        return ['valid' => false, 'error' => ($currNormalized === 'USD' ? 'Please enter the Binance Pay Order ID / Transaction Hash.' : 'Please enter the transaction reference / TrxID.')];
    }

    $trxCheck = $pdo->prepare("SELECT id, order_number FROM orders WHERE UPPER(TRIM(payment_trx)) = ? AND order_status != 'cancelled' LIMIT 1");
    $trxCheck->execute([$cleanTrx]);
    $existingOrder = $trxCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingOrder) {
        return [
            'valid' => false,
            'error' => "This Transaction ID (" . htmlspecialchars($paymentTrx) . ") has already been submitted for Order #" . htmlspecialchars($existingOrder['order_number']) . "."
        ];
    }

    return [
        'valid'                  => true,
        'verified_product'       => $verifiedProduct,
        'coupon_id_to_increment' => $couponIdToIncrement,
        'used_coupon_code'       => $usedCouponCode,
        'discount_amount'        => $discountBdt,
        'snapshot'               => $snapshot,
    ];
}
