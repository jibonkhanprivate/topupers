<?php
require_once __DIR__ . '/../config/database.php';
$pdo = db();

echo "--- SETTINGS TABLE SCHEMA ---\n";
foreach ($pdo->query("DESCRIBE settings")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']}\n";
}

echo "\n--- PAYMENT_METHODS TABLE SCHEMA ---\n";
foreach ($pdo->query("DESCRIBE payment_methods")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']}\n";
}

echo "\n--- PAYMENT_METHODS ROWS ---\n";
foreach ($pdo->query("SELECT * FROM payment_methods")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row) . "\n";
}
