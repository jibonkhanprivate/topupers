<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "CONNECTED TO DB: " . DB_NAME . "\n";
    echo "TABLES COUNT: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "- $t ($count rows)\n";
    }
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
