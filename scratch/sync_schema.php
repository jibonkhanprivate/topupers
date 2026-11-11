<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    echo "=== Running Database Sync & Migration ===\n\n";

    // 1. GAMES table columns
    $gameCols = [
        "category" => "ALTER TABLE games ADD COLUMN category VARCHAR(50) DEFAULT 'game' AFTER sort_order",
        "parent_id" => "ALTER TABLE games ADD COLUMN parent_id INT DEFAULT 0 AFTER category",
        "is_stock_out" => "ALTER TABLE games ADD COLUMN is_stock_out TINYINT(1) DEFAULT 0 AFTER parent_id",
        "badge_text" => "ALTER TABLE games ADD COLUMN badge_text VARCHAR(50) DEFAULT NULL AFTER is_stock_out",
        "cover_image" => "ALTER TABLE games ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER badge_text",
        "banner_1" => "ALTER TABLE games ADD COLUMN banner_1 VARCHAR(255) DEFAULT NULL AFTER cover_image",
        "banner_2" => "ALTER TABLE games ADD COLUMN banner_2 VARCHAR(255) DEFAULT NULL AFTER banner_1",
        "banner_3" => "ALTER TABLE games ADD COLUMN banner_3 VARCHAR(255) DEFAULT NULL AFTER banner_2"
    ];

    $existingCols = $db->query("DESCRIBE games")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($gameCols as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $db->exec($sql);
            echo " [✓] Added column '$col' to 'games' table.\n";
        } else {
            echo " [-] Column '$col' already exists in 'games'.\n";
        }
    }

    // Set default category for any existing records where category is null or empty
    $db->exec("UPDATE games SET category = 'game' WHERE category IS NULL OR category = ''");

    // 2. USERS table columns
    $userCols = [
        "full_name" => "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) DEFAULT NULL AFTER username"
    ];
    $existingCols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($userCols as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $db->exec($sql);
            echo " [✓] Added column '$col' to 'users' table.\n";
        } else {
            echo " [-] Column '$col' already exists in 'users'.\n";
        }
    }

    // 3. PRODUCTS table columns
    $prodCols = [
        "image" => "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER is_active",
        "badge" => "ALTER TABLE products ADD COLUMN badge VARCHAR(50) DEFAULT NULL AFTER image"
    ];
    $existingCols = $db->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($prodCols as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $db->exec($sql);
            echo " [✓] Added column '$col' to 'products' table.\n";
        } else {
            echo " [-] Column '$col' already exists in 'products'.\n";
        }
    }

    // 4. ORDERS table columns
    $orderCols = [
        "coupon_code" => "ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER total_price",
        "discount_amount" => "ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER coupon_code"
    ];
    $existingCols = $db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($orderCols as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $db->exec($sql);
            echo " [✓] Added column '$col' to 'orders' table.\n";
        } else {
            echo " [-] Column '$col' already exists in 'orders'.\n";
        }
    }

    // 5. PAYMENT_METHODS table
    $db->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        method_key VARCHAR(50) NOT NULL UNIQUE,
        method_label VARCHAR(100) NOT NULL,
        account_number VARCHAR(100) DEFAULT NULL,
        icon VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo " [✓] Table 'payment_methods' verified/created.\n";

    // Ensure account_number column exists in payment_methods if created with older definition
    $pmCols = $db->query("DESCRIBE payment_methods")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('account_number', $pmCols)) {
        $db->exec("ALTER TABLE payment_methods ADD COLUMN account_number VARCHAR(100) DEFAULT NULL AFTER method_label");
        echo " [✓] Added column 'account_number' to 'payment_methods'.\n";
    }

    $pmCount = $db->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
    if ($pmCount == 0) {
        $stmt = $db->prepare("INSERT INTO payment_methods (method_key, method_label, account_number, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute(['bkash', 'bKash', '01XXXXXXXXX', 1]);
        $stmt->execute(['nagad', 'Nagad', '01XXXXXXXXX', 2]);
        $stmt->execute(['rocket', 'Rocket', '01XXXXXXXXX', 3]);
        echo " [✓] Seeded default payment methods (bKash, Nagad, Rocket).\n";
    }

    // 6. ACTIVITY_LOGS table
    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo " [✓] Table 'activity_logs' verified/created.\n";

    echo "\n=== Database Migration Completed Successfully! ===\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
}
