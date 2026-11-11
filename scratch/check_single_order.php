<?php
require_once __DIR__ . '/../config/database.php';
$db = db();

$stmt = $db->query("SELECT * FROM orders LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "Order Number: " . $row['order_number'] . "\n";
    echo "Order Status: " . $row['order_status'] . "\n";
    echo "Payment Status: " . $row['payment_status'] . "\n";
    echo "Payment Method: " . $row['payment_method'] . "\n";
    
    // Check available currency fields
    $currencyFields = [];
    foreach (['currency', 'base_amount_bdt', 'exchange_rate', 'rate_buffer_percent', 'rate_mode', 'rate_source', 'charge_amount', 'payment_details'] as $cf) {
        if (array_key_exists($cf, $row)) {
            $currencyFields[$cf] = $row[$cf];
        }
    }
    echo "Currency Snapshot Fields in Current Schema: " . (empty($currencyFields) ? "None (Baseline schema, no snapshot columns)" : json_encode($currencyFields)) . "\n";
    
    // Total price & unit price
    echo "Unit Price: " . $row['unit_price'] . "\n";
    echo "Total Price: " . $row['total_price'] . "\n";
    
    // Payment Trx status (masked)
    $trx = $row['payment_trx'];
    $isPopulated = (!empty($trx) && trim($trx) !== '');
    $maskedTrx = $isPopulated ? substr($trx, 0, 4) . '****' . substr($trx, -4) : 'EMPTY';
    echo "Payment Reference Populated: " . ($isPopulated ? 'YES' : 'NO') . " (Masked: " . $maskedTrx . ", Length: " . strlen($trx ?? '') . ")\n";
    echo "Created At: " . $row['created_at'] . "\n";
} else {
    echo "No orders found.\n";
}
