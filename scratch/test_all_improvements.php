<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TopUpers System Quality & Improvement Verification ===\n\n";

$testsPassed = 0;
$testsTotal = 0;

function runTest($name, $callable) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    try {
        $result = $callable();
        if ($result === true) {
            echo " [PASS] $name\n";
            $testsPassed++;
        } else {
            echo " [FAIL] $name: " . (is_string($result) ? $result : 'Returned false') . "\n";
        }
    } catch (Throwable $e) {
        echo " [ERROR] $name: " . $e->getMessage() . "\n";
    }
}

// 1. Database Connectivity & TopUpers Settings
runTest("Database connectivity & settings verification", function() {
    $db = db();
    $sName = getSetting('site_name');
    if ($sName !== 'TopUpers') {
        return "Expected site_name 'TopUpers', got '$sName'";
    }
    $channels = ['support_whatsapp', 'support_messenger', 'support_telegram', 'support_email'];
    foreach ($channels as $c) {
        $val = getSetting($c);
        if (empty($val)) {
            return "Setting $c is empty";
        }
    }
    return true;
});

// 2. Order Tracking AJAX Endpoint Test
runTest("AJAX Order Tracking endpoint with dummy query", function() {
    $url = 'http://localhost/RechaX%20Official/ajax/track_order.php?query=NONEXISTENT_ORDER_123';
    $res = @file_get_contents($url);
    if (!$res) return "Failed to reach endpoint";
    $json = json_decode($res, true);
    if (!isset($json['success']) || $json['success'] !== false) {
        return "Expected success:false for nonexistent order, got: $res";
    }
    return true;
});

// 3. Order Tracking AJAX Endpoint with real order if available
runTest("AJAX Order Tracking endpoint with real order", function() {
    $order = db()->query("SELECT order_number FROM orders ORDER BY id DESC LIMIT 1")->fetch();
    if (!$order) {
        return true; // No orders yet in fresh DB
    }
    $num = $order['order_number'];
    $url = 'http://localhost/RechaX%20Official/ajax/track_order.php?query=' . urlencode($num);
    $res = @file_get_contents($url);
    $json = json_decode($res, true);
    if (!isset($json['success']) || $json['success'] !== true) {
        return "Failed to track real order #$num";
    }
    return true;
});

// 4. Submit Ticket AJAX Endpoint Test
runTest("AJAX Ticket Submission endpoint validation", function() {
    $ch = curl_init('http://localhost/RechaX%20Official/ajax/submit_ticket.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'name' => 'Automated Test User',
        'contact' => 'test@topupers.com',
        'category' => 'general',
        'subject' => 'Test Ticket System Integrity',
        'message' => 'Automated system validation test message.'
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($res, true);
    if (!isset($json['success']) || $json['success'] !== true) {
        return "Ticket submission failed: " . ($json['message'] ?? $res);
    }
    // Clean up test ticket
    $ticketNum = $json['ticket_number'];
    $tId = db()->query("SELECT id FROM support_tickets WHERE ticket_number = '$ticketNum'")->fetchColumn();
    if ($tId) {
        db()->exec("DELETE FROM ticket_replies WHERE ticket_id = $tId");
        db()->exec("DELETE FROM support_tickets WHERE id = $tId");
    }
    return true;
});

// 5. Check Homepage HTML for TopUpers branding and Support Widget
runTest("Homepage HTML contains TopUpers branding and support widget", function() {
    $html = @file_get_contents('http://localhost/RechaX%20Official/index.php');
    if (!$html) return "Could not fetch index.php";
    if (strpos($html, 'TopUpers') === false) return "TopUpers not found in homepage HTML";
    if (strpos($html, 'floatingSupportPopup') === false) return "floatingSupportPopup element not found";
    if (strpos($html, 'fswTriggerBtn') === false) return "fswTriggerBtn not found";
    return true;
});

// 6. Check Admin Orders PHP page compilation
runTest("Admin Orders syntax and compilation", function() {
    $out = [];
    $ret = 0;
    exec('C:\\xampp\\php\\php.exe -l "admin/orders.php"', $out, $ret);
    return $ret === 0;
});

echo "\nSummary: $testsPassed / $testsTotal tests passed successfully!\n";
