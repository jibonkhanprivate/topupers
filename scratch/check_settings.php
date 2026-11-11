<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("DESCRIBE settings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = db()->query("SELECT * FROM settings LIMIT 10");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
