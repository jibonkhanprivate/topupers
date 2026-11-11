<?php
require __DIR__ . '/../config/database.php';
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables);
