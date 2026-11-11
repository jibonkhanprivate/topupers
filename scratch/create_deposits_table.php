<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    $sql = "CREATE TABLE IF NOT EXISTS wallet_deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deposit_number VARCHAR(20) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_number VARCHAR(50) NOT NULL,
        payment_trx VARCHAR(100) NOT NULL UNIQUE,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        admin_note TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB";
    
    $db->exec($sql);
    echo "wallet_deposits table created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
