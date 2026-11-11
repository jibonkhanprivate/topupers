<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$stmt = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "TOTAL SETTINGS IN DB: " . count($rows) . "\n\n";
foreach ($rows as $r) {
    echo sprintf("%-30s => %s\n", $r['setting_key'], json_encode($r['setting_value']));
}
