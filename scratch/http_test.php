<?php
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// 1. GET Login Page to obtain csrf_token and session cookie
$ch = curl_init('http://localhost/RechaX%20Official/admin/login.php');
curl_setopt_array($ch, [
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_RETURNTRANSFER => true
]);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
$csrfToken = $m[1] ?? '';
echo "CSRF Token: $csrfToken\n";

// 2. POST Login with CSRF Token
$ch = curl_init('http://localhost/RechaX%20Official/admin/login.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => 'admin',
        'password' => 'password',
        'csrf_token' => $csrfToken
    ]),
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true
]);
$loginResp = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Login Effective URL: $effectiveUrl\n";
echo "Login HTTP Code: $loginCode\n\n";

$pages = [
    'admin/index.php',
    'admin/reports.php',
    'admin/refunds.php',
    'admin/pricing.php',
    'admin/vouchers.php',
    'admin/notifications.php',
    'admin/api-docs.php',
    'admin/tickets.php',
    'admin/media.php',
    'admin/blog.php',
    'admin/feature-flags.php',
    'admin/system-health.php',
    'admin/cron.php',
    'admin/backups.php',
    'admin/developer-tools.php',
    'admin/game-selectors.php',
    'admin/products.php',
    'admin/games.php',
    'admin/orders.php',
    'admin/users.php',
    'admin/wallet.php',
    'admin/withdrawals.php',
    'admin/deposits.php',
    'admin/payments.php',
    'admin/admins.php',
    'admin/roles.php',
    'admin/security.php',
    'admin/homepage-sections.php',
    'admin/coupons.php',
    'admin/promotions.php',
    'admin/resellers.php',
    'admin/suppliers.php',
    'admin/product-mapping.php',
    'admin/api-logs.php',
    'admin/content.php',
    'admin/logs.php',
    'admin/api-keys.php',
    'admin/regions.php',
    'admin/coins.php'
];

$allPassed = true;
foreach ($pages as $p) {
    $url = 'http://localhost/RechaX%20Official/' . $p;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $content = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $hasErr = false;
    $err = '';
    if ($code !== 200) {
        $hasErr = true;
        $err = "HTTP Status $code";
    } elseif (preg_match('/(Fatal error|Parse error|SQLSTATE|PDOException|Uncaught Exception)/i', $content, $m)) {
        $hasErr = true;
        $err = $m[0];
    }

    if ($hasErr) {
        $allPassed = false;
        echo "[FAILED] $p -> $err\n";
    } else {
        echo "[200 OK] $p (" . strlen($content) . " bytes)\n";
    }
}

echo "\nResult: " . ($allPassed ? "ALL 39 ADMIN PAGES WORKING PERFECTLY!" : "SOME PAGES FAILED!") . "\n";
exit($allPassed ? 0 : 1);
