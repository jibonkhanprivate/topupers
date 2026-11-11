<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = db();
$cols = $db->query('SHOW FULL COLUMNS FROM coupons')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo sprintf("%-20s %-15s %-6s %-10s\n", $c['Field'], $c['Type'], $c['Null'], var_export($c['Default'], true));
}
