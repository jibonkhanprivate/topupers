<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    $username = 'testuser';
    $email = 'test@example.com';
    $password = password_hash('password', PASSWORD_DEFAULT);
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update password and reset balance to 0 for clean test
        $db->prepare("UPDATE users SET password = ?, wallet_balance = 0.00, is_active = 1 WHERE id = ?")
           ->execute([$password, $user['id']]);
        echo "testuser credentials updated. Password is 'password'. Balance is ৳0.\n";
    } else {
        // Insert new
        $db->prepare("INSERT INTO users (username, email, password, full_name, is_active, wallet_balance) VALUES (?, ?, ?, 'Test User', 1, 0.00)")
           ->execute([$username, $email, $password]);
        echo "testuser created. Password is 'password'. Balance is ৳0.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
