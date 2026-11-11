<?php
// scratch/test_responsive_user_section.php
// Validates mobile & desktop responsive structures across customer section

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$results = [];

function check($title, $condition, $details = '') {
    global $results;
    $status = $condition ? 'PASS' : 'FAIL';
    $results[] = compact('title', 'status', 'details');
    echo "[$status] $title" . ($details ? " - $details" : "") . "\n";
}

echo "=== TESTING AUTO-RESPONSIVE CUSTOMER SECTION ===\n\n";

// 1. Check CSS file media queries and responsive rules
$cssContent = file_get_contents(__DIR__ . '/../assets/css/customer-dashboard.css');

check("CSS Contains Mobile Breakpoint (max-width: 480px)", strpos($cssContent, '@media (max-width: 480px)') !== false);
check("CSS Contains Tablet/Phone Breakpoint (max-width: 767px)", strpos($cssContent, '@media (max-width: 767px)') !== false);
check("CSS Contains Small Screen Breakpoint (max-width: 575px)", strpos($cssContent, '@media (max-width: 575px)') !== false);
check("CSS Contains Desktop Breakpoint (min-width: 992px)", strpos($cssContent, '@media (min-width: 992px)') !== false);
check("CSS Contains Sticky Desktop Sidebar", strpos($cssContent, 'position: sticky;') !== false && strpos($cssContent, 'top: 90px;') !== false);
check("CSS Contains Mobile Nav Wrapper", strpos($cssContent, '.tp-mobile-nav-wrapper') !== false);
check("CSS Contains Responsive Filter Bar", strpos($cssContent, '.tp-filter-bar') !== false && strpos($cssContent, '.tp-filter-scroll') !== false);
check("CSS Contains Responsive Pagination", strpos($cssContent, '.tp-pagination-wrap') !== false);
check("CSS Contains Responsive Submit Buttons", strpos($cssContent, '.tp-btn-submit-responsive') !== false);
check("CSS Contains 16px iOS Input Zoom Prevention", strpos($cssContent, 'font-size: 1rem; /* 16px prevents iOS Safari auto-zoom */') !== false);

// 2. Fetch authenticated pages via local curl
$cookieFile = __DIR__ . '/cookie_test.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

function curlReq($url, $post = null, $follow = true) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
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

// Log in with test user
$loginPage = curlReq(APP_URL . '/auth/login.php');
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['html'], $m);
$csrf = $m[1] ?? '';

$loginRes = curlReq(APP_URL . '/auth/login.php', [
    'csrf_token' => $csrf,
    'email'      => 'testuser',
    'password'   => 'password'
]);

// Ensure testuser has a sample order so both Desktop Table and Mobile Cards render
$uStmt = db()->prepare("SELECT id FROM users WHERE username = 'testuser'");
$uStmt->execute();
$testUserId = (int)$uStmt->fetchColumn();

$pStmt = db()->query("SELECT id, game_id FROM products LIMIT 1");
$prod = $pStmt->fetch();

db()->exec("DELETE FROM orders WHERE order_number = 'RX-RESP-TEST'");
$ins = db()->prepare("
    INSERT INTO orders 
    (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_method, payment_status, order_status, created_at)
    VALUES ('RX-RESP-TEST', ?, ?, ?, '987654321', 120.00, 120.00, 'bkash', 'paid', 'completed', NOW())
");
$ins->execute([$testUserId, $prod['id'], $prod['game_id']]);

// Test Dashboard Responsive Elements
$dashRes = curlReq(APP_URL . '/user/dashboard.php');
$dashHtml = $dashRes['html'];

check("Dashboard: Contains Desktop Sidebar inside col-lg-3 d-none d-lg-block", 
    strpos($dashHtml, 'col-lg-3 d-none d-lg-block') !== false);
check("Dashboard: Contains Mobile Nav inside d-lg-none", 
    strpos($dashHtml, 'tp-mobile-nav-wrapper d-lg-none') !== false);
check("Dashboard: Contains Mobile Nav Auto-Scroll Script", 
    strpos($dashHtml, 'navContainer.scrollTo') !== false);
check("Dashboard: Contains Responsive Header Actions (tp-header-actions)", 
    strpos($dashHtml, 'tp-header-actions') !== false);
check("Dashboard: Contains Responsive Stats Grid (tp-stats-grid)", 
    strpos($dashHtml, 'tp-stats-grid') !== false);
check("Dashboard: Contains Desktop Table (d-none d-md-block)", 
    strpos($dashHtml, 'tp-table-wrap d-none d-md-block') !== false);
check("Dashboard: Contains Mobile Card View (d-md-none)", 
    strpos($dashHtml, 'tp-mobile-order-card') !== false || strpos($dashHtml, 'tp-empty-state') !== false);

// Test Orders Responsive Elements
$ordersRes = curlReq(APP_URL . '/user/orders.php');
$ordersHtml = $ordersRes['html'];

check("Orders: Contains Desktop Sidebar (col-lg-3 d-none d-lg-block)", 
    strpos($ordersHtml, 'col-lg-3 d-none d-lg-block') !== false);
check("Orders: Contains Mobile Nav (tp-mobile-nav-wrapper d-lg-none)", 
    strpos($ordersHtml, 'tp-mobile-nav-wrapper d-lg-none') !== false);
check("Orders: Contains Responsive Header Actions (tp-header-actions)", 
    strpos($ordersHtml, 'tp-header-actions') !== false);
check("Orders: Contains Responsive Filter Bar (tp-filter-bar)", 
    strpos($ordersHtml, 'tp-filter-bar') !== false);
check("Orders: Contains Mobile Filter Scroll (tp-filter-scroll)", 
    strpos($ordersHtml, 'tp-filter-scroll') !== false);
check("Orders: Contains Desktop Orders Table (d-none d-md-block)", 
    strpos($ordersHtml, 'tp-table-wrap d-none d-md-block') !== false);
check("Orders: Contains Mobile Orders Cards (d-md-none)", 
    strpos($ordersHtml, 'tp-mobile-order-card') !== false);

// Test Profile Responsive Elements
$profileRes = curlReq(APP_URL . '/user/profile.php');
$profileHtml = $profileRes['html'];

check("Profile: Contains Desktop Sidebar (col-lg-3 d-none d-lg-block)", 
    strpos($profileHtml, 'col-lg-3 d-none d-lg-block') !== false);
check("Profile: Contains Mobile Nav (tp-mobile-nav-wrapper d-lg-none)", 
    strpos($profileHtml, 'tp-mobile-nav-wrapper d-lg-none') !== false);
check("Profile: Contains Responsive Header Actions (tp-header-actions)", 
    strpos($profileHtml, 'tp-header-actions') !== false);
check("Profile: Contains Responsive Avatar Box (flex-column flex-sm-row)", 
    strpos($profileHtml, 'flex-column flex-sm-row') !== false);
check("Profile: Contains Responsive Submit Button (tp-btn-submit-responsive)", 
    strpos($profileHtml, 'tp-btn-submit-responsive') !== false);

if (file_exists($cookieFile)) @unlink($cookieFile);
db()->exec("DELETE FROM orders WHERE order_number = 'RX-RESP-TEST'");

$passedCount = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$totalCount = count($results);

echo "\n=== RESPONSIVE TESTS COMPLETED: $passedCount / $totalCount PASSED ===\n";
exit($passedCount === $totalCount ? 0 : 1);
