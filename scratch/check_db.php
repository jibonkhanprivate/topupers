<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    echo "DB Connected successfully.\n\n";

    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n" . implode("\n", $tables) . "\n\n";

    foreach (['games', 'users', 'products', 'orders', 'payment_methods', 'activity_logs'] as $table) {
        if (in_array($table, $tables)) {
            echo "--- Columns in $table ---\n";
            $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo "  {$c['Field']} ({$c['Type']})\n";
            }
            echo "\n";
        } else {
            echo "--- Table $table DOES NOT EXIST ---\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
