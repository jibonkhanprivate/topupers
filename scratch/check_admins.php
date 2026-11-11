<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("SELECT id, username, email, role, is_active FROM admins");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
