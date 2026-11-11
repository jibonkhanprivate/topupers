<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$t = $pdo->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA='rechax_official' AND TABLE_NAME='product_packages'")->fetch(PDO::FETCH_ASSOC);
echo json_encode($t, JSON_PRETTY_PRINT) . "\n";

$backupPath = 'C:\\xampp\\htdocs\\RechaX Official\\backups\\backup_phase_c6_0_20261013_202915\\db_rechax_official_c6_0_20261013_202915.sql';
$content = file_get_contents($backupPath);
if (preg_match('/product_packages/', $content)) {
    echo "product_packages is mentioned in dump!\n";
    preg_match_all('/.*product_packages.*/', $content, $matches);
    foreach (array_slice($matches[0], 0, 10) as $line) {
        echo "  " . trim($line) . "\n";
    }
}
