<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['setting_key'] . " = " . $r['setting_value'] . "\n";
}
