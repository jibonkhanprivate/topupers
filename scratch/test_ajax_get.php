<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startAdminSession();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';

$_GET['action'] = 'get_option';
$_GET['option_id'] = 1;

ob_start();
require __DIR__ . '/../ajax/admin_selector.php';
$out = ob_get_clean();
echo "AJAX OUTPUT:\n" . $out;
