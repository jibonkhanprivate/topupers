<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    
    // Create payment_methods table
    $db->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        method_key VARCHAR(50) NOT NULL UNIQUE,
        method_label VARCHAR(100) NOT NULL,
        icon VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Seed default methods if table is empty
    $count = $db->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO payment_methods (method_key, method_label, sort_order) VALUES (?, ?, ?)");
        $stmt->execute(['bkash', 'bKash', 1]);
        $stmt->execute(['nagad', 'Nagad', 2]);
        $stmt->execute(['rocket', 'Rocket', 3]);
        echo "Table created and seeded successfully.";
    } else {
        echo "Table already exists and is not empty.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
