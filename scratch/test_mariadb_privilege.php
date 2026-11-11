<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->exec("CREATE DATABASE IF NOT EXISTS rechax_disposable_c59_test");
echo "Created rechax_disposable_c59_test successfully!\n";
$pdo->exec("DROP DATABASE IF EXISTS rechax_disposable_c59_test");
echo "Dropped rechax_disposable_c59_test successfully!\n";
