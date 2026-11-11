<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startAdminSession();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Super Admin';
$_SESSION['admin_role'] = 'superadmin';

$_SERVER['REQUEST_URI'] = '/RechaX%20Official/admin/index.php';

echo "Testing admin/index.php render...\n";
ob_start();
include __DIR__ . '/../admin/index.php';
$content = ob_get_clean();

echo "Render succeeded! HTML length: " . strlen($content) . " bytes.\n";
if (strpos($content, 'Fatal error') !== false || strpos($content, 'Uncaught') !== false) {
    echo "ERROR FOUND IN OUTPUT:\n" . substr($content, 0, 500) . "\n";
} else {
    echo "No fatal errors found in admin dashboard.\n";
}
