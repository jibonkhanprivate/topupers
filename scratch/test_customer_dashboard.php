<?php
// scratch/test_customer_dashboard.php
// Comprehensive verification test suite for TOPUPERS Customer Dashboard

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$baseUrl = 'http://localhost/RechaX%20Official';
$results = [];

function record($testName, $passed, $details = '') {
    global $results;
    $results[] = [
        'name' => $testName,
        'passed' => $passed,
        'details' => $details
    ];
    echo ($passed ? "[PASS] " : "[FAIL] ") . $testName . ($details ? " - $details" : "") . "\n";
}

echo "=== STARTING TOPUPERS CUSTOMER DASHBOARD TEST SUITE ===\n\n";

// 1. PHP Syntax Checks
$filesToLint = [
    'user/dashboard.php',
    'user/orders.php',
    'user/profile.php',
    'user/includes/dashboard_sidebar.php',
    'includes/functions.php',
    'includes/header.php',
    'config/config.php'
];

foreach ($filesToLint as $file) {
    $fullPath = APP_ROOT . '/' . $file;
    $output = [];
    $returnCode = 0;
    exec("c:\\xampp\\php\\php.exe -l \"$fullPath\"", $output, $returnCode);
    record("Lint $file", $returnCode === 0, implode(' ', $output));
}

// Helper for HTTP requests
function httpRequest($url, $method = 'GET', $data = [], $cookieJar = null, $followRedirect = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

// 2. Unauthenticated Access Protection
$unauthDash = httpRequest("$baseUrl/user/dashboard.php");
$unauthOrders = httpRequest("$baseUrl/user/orders.php");
$unauthProfile = httpRequest("$baseUrl/user/profile.php");

record("Unauthenticated Dashboard 302 Redirect", $unauthDash['code'] === 302 && strpos($unauthDash['headers'], 'login.php') !== false, "Code: {$unauthDash['code']}");
record("Unauthenticated Orders 302 Redirect", $unauthOrders['code'] === 302 && strpos($unauthOrders['headers'], 'login.php') !== false, "Code: {$unauthOrders['code']}");
record("Unauthenticated Profile 302 Redirect", $unauthProfile['code'] === 302 && strpos($unauthProfile['headers'], 'login.php') !== false, "Code: {$unauthProfile['code']}");

// 3. User Login & Authenticated Session
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

// Get login page to grab CSRF token
$loginPage = httpRequest("$baseUrl/auth/login.php", 'GET', [], $cookieFile);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? '';

record("Fetch Login CSRF Token", !empty($csrfToken), "Token found: " . substr($csrfToken, 0, 10) . "...");

// Submit login form
$loginRes = httpRequest("$baseUrl/auth/login.php", 'POST', [
    'csrf_token' => $csrfToken,
    'email' => 'testuser',
    'password' => 'password'
], $cookieFile);

record("Login Test User", $loginRes['code'] === 302, "HTTP Code: {$loginRes['code']}");

// 4. Authenticated Dashboard Load
$authDash = httpRequest("$baseUrl/user/dashboard.php", 'GET', [], $cookieFile);
record("Authenticated Dashboard 200 OK", $authDash['code'] === 200, "HTTP Code: {$authDash['code']}");
record("Dashboard Contains TOPUPERS Brand", strpos($authDash['body'], 'TOPUPERS') !== false, "Brand rendered");
record("Dashboard Contains Customer Name", strpos($authDash['body'], 'testuser') !== false, "User handle rendered");
record("Dashboard Contains customer-dashboard.css", strpos($authDash['body'], 'customer-dashboard.css') !== false, "CSS linked");
record("Dashboard Contains Statistics Cards", strpos($authDash['body'], 'Wallet Balance') !== false && strpos($authDash['body'], 'Total Orders') !== false, "Stats cards rendered");
record("Dashboard Empty State Displayed", strpos($authDash['body'], 'No Orders Placed Yet') !== false, "Empty state rendered for zero orders");

// 5. Data Isolation & Real DB Order Count
$db = db();
$testUserStmt = $db->prepare("SELECT id FROM users WHERE username = 'testuser'");
$testUserStmt->execute();
$testUserId = (int)$testUserStmt->fetchColumn();

$johnnyStmt = $db->prepare("SELECT id FROM users WHERE username = 'johnnyislam112'");
$johnnyStmt->execute();
$johnnyId = (int)$johnnyStmt->fetchColumn();

// Insert an order for testuser and another for johnny
$pStmt = $db->query("SELECT id, game_id FROM products LIMIT 1");
$prod = $pStmt->fetch();

$orderNumTest = 'RX-TEST-001';
$orderNumJohnny = 'RX-TEST-999';

$db->prepare("DELETE FROM orders WHERE order_number IN (?, ?)")->execute([$orderNumTest, $orderNumJohnny]);

$insOrder = $db->prepare("
    INSERT INTO orders 
    (order_number, user_id, product_id, game_id, player_id, unit_price, total_price, payment_method, payment_status, order_status, created_at)
    VALUES (?, ?, ?, ?, ?, 100.00, 100.00, 'bkash', 'paid', 'completed', NOW())
");
$insOrder->execute([$orderNumTest, $testUserId, $prod['id'], $prod['game_id'], '12345678']);
$insOrder->execute([$orderNumJohnny, $johnnyId, $prod['id'], $prod['game_id'], '99999999']);

// Fetch dashboard again as testuser
$authDashOrders = httpRequest("$baseUrl/user/dashboard.php", 'GET', [], $cookieFile);
record("Test User Sees Their Own Order", strpos($authDashOrders['body'], $orderNumTest) !== false, "Order $orderNumTest found");
record("Customer Isolation: Test User CANNOT See Other User Order", strpos($authDashOrders['body'], $orderNumJohnny) === false, "Order $orderNumJohnny isolated");

// Fetch orders page
$authOrdersPage = httpRequest("$baseUrl/user/orders.php", 'GET', [], $cookieFile);
record("Orders Page Shows Own Order", strpos($authOrdersPage['body'], $orderNumTest) !== false, "Order found in orders page");
record("Orders Page Does NOT Show Other Customer Order", strpos($authOrdersPage['body'], $orderNumJohnny) === false, "Other customer order filtered out");

// Clean up test orders
$db->prepare("DELETE FROM orders WHERE order_number IN (?, ?)")->execute([$orderNumTest, $orderNumJohnny]);

// 6. Test Profile Editing
preg_match('/name="csrf_token" value="([^"]+)"/', $authDash['body'], $dashCsrfMatches);
$dashCsrf = $dashCsrfMatches[1] ?? '';

$updateProfileRes = httpRequest("$baseUrl/user/dashboard.php", 'POST', [
    'action' => 'update_profile',
    'csrf_token' => $dashCsrf,
    'full_name' => 'Test Gamer Pro',
    'phone' => '01712345678',
    'wallet_balance' => '999999.00' // Attempted privilege escalation
], $cookieFile, true);

$checkUserStmt = $db->prepare("SELECT full_name, phone, wallet_balance FROM users WHERE id = ?");
$checkUserStmt->execute([$testUserId]);
$updatedUser = $checkUserStmt->fetch();

record("Profile Full Name Updated in DB", $updatedUser['full_name'] === 'Test Gamer Pro', "Name: {$updatedUser['full_name']}");
record("Profile Phone Updated in DB", $updatedUser['phone'] === '01712345678', "Phone: {$updatedUser['phone']}");
record("Security: Wallet Balance Tampering Ignored", (float)$updatedUser['wallet_balance'] === 0.00, "Balance remained 0.00");

// 7. Test Password Change
// Correct change
$changePwRes = httpRequest("$baseUrl/user/dashboard.php", 'POST', [
    'action' => 'change_password',
    'csrf_token' => $dashCsrf,
    'current_password' => 'password',
    'new_password' => 'newpassword123',
    'confirm_password' => 'newpassword123'
], $cookieFile, true);

$userPwStmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$userPwStmt->execute([$testUserId]);
$newHash = $userPwStmt->fetchColumn();
record("Password Change Successfully Hashed", password_verify('newpassword123', $newHash), "Verified bcrypt hash");

// Revert password back to 'password'
$db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash('password', PASSWORD_DEFAULT), $testUserId]);

// 8. Logout Test
$logoutRes = httpRequest("$baseUrl/auth/logout.php", 'GET', [], $cookieFile);
record("Logout Redirects to Login", $logoutRes['code'] === 302, "HTTP Code: {$logoutRes['code']}");
$afterLogoutDash = httpRequest("$baseUrl/user/dashboard.php", 'GET', [], $cookieFile);
record("Session Destroyed: Re-access Redirects to Login", $afterLogoutDash['code'] === 302, "HTTP Code: {$afterLogoutDash['code']}");

// 9. Existing Unrelated Features Integrity Check
$homeRes = httpRequest("$baseUrl/", 'GET');
$shopRes = httpRequest("$baseUrl/shop.php", 'GET');
$searchRes = httpRequest("$baseUrl/search.php", 'GET');
$adminLoginRes = httpRequest("$baseUrl/admin/login.php", 'GET');

record("Homepage Functional (200 OK)", $homeRes['code'] === 200, "HTTP Code: {$homeRes['code']}");
record("Shop Page Functional (200 OK)", $shopRes['code'] === 200, "HTTP Code: {$shopRes['code']}");
record("Search Page Functional (200 OK)", $searchRes['code'] === 200, "HTTP Code: {$searchRes['code']}");
record("Admin Login Functional (200 OK)", $adminLoginRes['code'] === 200, "HTTP Code: {$adminLoginRes['code']}");

// Clean up cookie file
if (file_exists($cookieFile)) @unlink($cookieFile);

echo "\n=== TEST SUITE COMPLETED ===\n";
$passCount = count(array_filter($results, fn($r) => $r['passed']));
$totalCount = count($results);
echo "Total Tests: $totalCount | Passed: $passCount | Failed: " . ($totalCount - $passCount) . "\n";
