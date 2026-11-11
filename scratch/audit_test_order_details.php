<?php
require_once __DIR__ . '/../config/database.php';
$db = db();

$stmt = $db->query("SELECT * FROM orders WHERE id = 32 OR order_number = 'RX6ACE242D8221B13'");
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    echo "=== TEST ORDER DETAILS (SAFE MASKED VIEW) ===\n";
    echo "ID: " . $order['id'] . "\n";
    echo "Order Number: " . $order['order_number'] . "\n";
    echo "User ID: " . var_export($order['user_id'], true) . " (" . ($order['user_id'] ? 'Registered User' : 'Guest Checkout') . ")\n";
    echo "Product ID: " . $order['product_id'] . "\n";
    echo "Game ID: " . $order['game_id'] . "\n";
    echo "Quantity: " . $order['quantity'] . "\n";
    echo "Unit Price: " . $order['unit_price'] . "\n";
    echo "Total Price: " . $order['total_price'] . "\n";
    echo "Order Status: " . $order['order_status'] . "\n";
    echo "Payment Status: " . $order['payment_status'] . "\n";
    echo "Payment Method: " . $order['payment_method'] . "\n";
    
    // Masked PII
    $pId = $order['player_id'];
    $maskedPlayer = strlen($pId) > 4 ? substr($pId, 0, 2) . '****' . substr($pId, -2) : '****';
    echo "Player ID: " . $maskedPlayer . " (Length: " . strlen($pId) . ")\n";

    $trx = $order['payment_trx'];
    $maskedTrx = strlen($trx) > 8 ? substr($trx, 0, 4) . '****' . substr($trx, -4) : '****';
    echo "Payment Trx: " . $maskedTrx . " (Length: " . strlen($trx) . ")\n";

    $phone = $order['customer_phone'];
    $maskedPhone = strlen($phone) > 6 ? substr($phone, 0, 3) . '****' . substr($phone, -3) : '****';
    echo "Customer Phone: " . $maskedPhone . "\n";

    echo "Created At: " . $order['created_at'] . "\n";
    echo "Completed At: " . var_export($order['completed_at'], true) . "\n";
    echo "IP Address: " . (filter_var($order['ip_address'], FILTER_VALIDATE_IP) ? 'Captured (Valid IP format)' : 'None') . "\n";
} else {
    echo "Order not found.\n";
}
