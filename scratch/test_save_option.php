<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startAdminSession();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';

$_POST['action'] = 'save_option';
$_POST['option_id'] = 1;
$_POST['config_id'] = 1;
$_POST['game_id'] = 1;
$_POST['name'] = 'Mobile Legends Bangladesh';
$_POST['slug'] = 'bangladesh';
$_POST['region_id'] = 1;
$_POST['badge_text'] = 'HOT';
$_POST['badge_color'] = 'hot';
$_POST['region_custom_text'] = 'Bangladesh Region';
$_POST['custom_icon'] = 'BD';
$_POST['sort_order'] = 1;
$_POST['is_active'] = 1;

ob_start();
require __DIR__ . '/../ajax/admin_selector.php';
$out = ob_get_clean();
echo "SAVE OPTION OUTPUT:\n" . $out;
