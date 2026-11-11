<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();

echo "=== DATABASE SCHEMA AUDIT ===\n";
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schema = [];
foreach ($tables as $t) {
    $cols = $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    $schema[$t] = array_map(function($c) {
        return $c['Field'] . ' (' . $c['Type'] . ($c['Null'] === 'NO' ? ' NOT NULL' : '') . ($c['Default'] !== null ? ' DEFAULT ' . $c['Default'] : '') . ')';
    }, $cols);
}

foreach ($schema as $t => $cols) {
    echo "\nTABLE: $t (" . count($cols) . " columns)\n";
    foreach ($cols as $c) {
        echo "  - $c\n";
    }
}
