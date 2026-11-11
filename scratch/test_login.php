<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['username'] = 'admin';
$_POST['password'] = 'password';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startAdminSession();
$_SESSION['csrf_token'] = 'testtoken';
$_POST['csrf_token'] = 'testtoken';

echo "Testing login verification...\n";
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    echo "CSRF Failed!\n";
} else {
    echo "CSRF Passed!\n";
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$stmt = db()->prepare('SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
$stmt->execute([$username, $username]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    echo "Password verification succeeded! Admin ID: {$admin['id']}, Name: {$admin['name']}\n";
} else {
    echo "Password verification failed!\n";
}
