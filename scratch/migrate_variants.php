<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    
    // Add parent_id
    $db->exec("ALTER TABLE games ADD COLUMN IF NOT EXISTS parent_id INT DEFAULT 0");
    
    // Add is_stock_out
    $db->exec("ALTER TABLE games ADD COLUMN IF NOT EXISTS is_stock_out TINYINT DEFAULT 0");
    
    // Add badge_text
    $db->exec("ALTER TABLE games ADD COLUMN IF NOT EXISTS badge_text VARCHAR(50) DEFAULT NULL");
    
    echo "Migration successful: Columns added to 'games' table.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
