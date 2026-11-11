<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();

    // 1. Create activity_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NULL,
        user_id INT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action_type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Activity logs table created successfully.\n";

    // 2. Add badge column to products table
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'badge'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN badge VARCHAR(50) NULL AFTER is_active;");
        echo "Badge column added to products table successfully.\n";
    } else {
        echo "Badge column already exists in products table.\n";
    }

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
