<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();
$createTable = $db->query("SHOW CREATE TABLE orders")->fetch(PDO::FETCH_ASSOC);
echo $createTable['Create Table'] . "\n";
