<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$admin = $pdo->query("SELECT id, username, password FROM admins WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
echo "Hash: " . $admin['password'] . "\n";
foreach (['rechax', 'rechax123', 'RechaX@2026', 'password', 'root', '12345678', 'admin2026', 'TalhaX', 'talha123'] as $p) {
    if (password_verify($p, $admin['password'])) {
        echo "MATCH: $p\n";
    }
}



