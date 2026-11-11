<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
