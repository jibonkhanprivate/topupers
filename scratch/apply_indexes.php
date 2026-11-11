<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();

function addIndexIfNotExists(PDO $db, string $table, string $indexName, string $columns) {
    $existing = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'")->fetchAll();
    if (empty($existing)) {
        $db->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)");
        echo "✓ Added index `$indexName` on `$table` ($columns)\n";
    } else {
        echo "- Index `$indexName` on `$table` already exists.\n";
    }
}

echo "Applying optimized database indexes...\n";
addIndexIfNotExists($db, 'banners', 'idx_banners_active_sort', 'is_active, sort_order');
addIndexIfNotExists($db, 'games', 'idx_games_active_sort', 'is_active, sort_order');
addIndexIfNotExists($db, 'products', 'idx_products_popular_active', 'is_popular, is_active');
addIndexIfNotExists($db, 'payment_methods', 'idx_pm_active_sort', 'is_active, sort_order');

echo "Database indexes applied successfully.\n";
