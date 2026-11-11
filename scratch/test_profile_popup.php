<?php
// scratch/test_profile_popup.php
// Validates profile avatar trigger button & popup dropdown menu matching user screenshot

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$results = [];

function check($title, $condition, $details = '') {
    global $results;
    $status = $condition ? 'PASS' : 'FAIL';
    $results[] = compact('title', 'status', 'details');
    echo "[$status] $title" . ($details ? " - $details" : "") . "\n";
}

echo "=== TESTING CUSTOMER PROFILE POPUP MENU ===\n\n";

$cookieFile = __DIR__ . '/cookie_popup_test.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

function curlReq($url, $post = null) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $html = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['html' => $html, 'info' => $info];
}

// 1. Unauthenticated Home Page: shows login link
$unauthHome = curlReq(APP_URL . '/index.php');
check("Unauthenticated Header: Has Sign-in Icon", 
    strpos($unauthHome['html'], 'auth/login.php') !== false);
check("Unauthenticated Header: Does NOT render Profile Button Element", 
    strpos($unauthHome['html'], 'id="tpProfileMenuBtn"') === false);

// 2. Log in with test user
$loginPage = curlReq(APP_URL . '/auth/login.php');
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['html'], $m);
$csrf = $m[1] ?? '';

$loginRes = curlReq(APP_URL . '/auth/login.php', [
    'csrf_token' => $csrf,
    'email'      => 'testuser',
    'password'   => 'password'
]);

// 3. Authenticated Home Page: shows Profile Trigger & Popup
$authHome = curlReq(APP_URL . '/index.php');
$html = $authHome['html'];

check("Authenticated Header: Contains Profile Trigger Button (.tp-profile-trigger-btn)", 
    strpos($html, 'tp-profile-trigger-btn') !== false);
check("Profile Trigger: Has data-bs-toggle='dropdown'", 
    strpos($html, 'data-bs-toggle="dropdown"') !== false);
check("Profile Trigger: Shows User Initial Badge", 
    strpos($html, 'tp-profile-trigger-initial') !== false || strpos($html, 'tp-profile-trigger-img') !== false);
check("Header: Contains Popup Menu Container (.tp-profile-popup)", 
    strpos($html, 'tp-profile-popup') !== false);
check("Header Actions: Search icon link is REMOVED", 
    strpos($html, 'search.php') === false);
check("Header Actions: Cart icon link is REMOVED", 
    strpos($html, 'checkout.php') === false);
check("Popup Menu: Overview item is REMOVED", 
    strpos($html, '<span>Overview</span>') === false);
check("Popup Item 1: My Orders (orders.php with bi-box-seam)", 
    strpos($html, 'user/orders.php') !== false && strpos($html, 'bi-box-seam') !== false);
check("Popup Item 2: Wallet (add-money.php with bi-wallet2)", 
    strpos($html, 'user/add-money.php') !== false && strpos($html, 'bi-wallet2') !== false);
check("Popup Item 3: Profile links to user/dashboard.php (Default User Dashboard)", 
    strpos($html, 'href="' . APP_URL . '/user/dashboard.php"') !== false && strpos($html, '<span>Profile</span>') !== false);
check("Popup Menu: Contains Divider (.tp-popup-divider)", 
    strpos($html, 'tp-popup-divider') !== false);
check("Popup Item 4: Logout (logout.php with bi-box-arrow-right)", 
    strpos($html, 'auth/logout.php') !== false && strpos($html, 'bi-box-arrow-right') !== false);

// 4. Check CSS definitions in style.css and customer-dashboard.css
$styleCss = file_get_contents(__DIR__ . '/../assets/css/style.css');
$dashCss  = file_get_contents(__DIR__ . '/../assets/css/customer-dashboard.css');

check("style.css: Contains .tp-profile-trigger-btn", strpos($styleCss, '.tp-profile-trigger-btn') !== false);
check("style.css: Contains .tp-profile-popup", strpos($styleCss, '.tp-profile-popup') !== false);
check("style.css: Contains .tp-popup-item", strpos($styleCss, '.tp-popup-item') !== false);
check("customer-dashboard.css: Contains .tp-profile-popup", strpos($dashCss, '.tp-profile-popup') !== false);

if (file_exists($cookieFile)) @unlink($cookieFile);

$passedCount = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$totalCount = count($results);

echo "\n=== PROFILE POPUP TESTS COMPLETED: $passedCount / $totalCount PASSED ===\n";
exit($passedCount === $totalCount ? 0 : 1);
