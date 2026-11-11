<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startAdminSession();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Super Admin';
$_SESSION['admin_role'] = 'superadmin';

$adminPages = [
    'games.php',
    'products.php',
    'orders.php',
    'content.php',
    'logs.php',
    'settings.php',
    'coupons.php',
    'api-keys.php',
    'users.php',
    'deposits.php',
    'payments.php'
];

$failedPages = 0;
foreach ($adminPages as $ap) {
    $_SERVER['REQUEST_URI'] = '/RechaX%20Official/admin/' . $ap;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    ob_start();
    try {
        include __DIR__ . '/../admin/' . $ap;
        $out = ob_get_clean();
        if (strpos($out, 'Fatal error') !== false || strpos($out, 'Uncaught') !== false) {
            $failedPages++;
            echo " [X] ERROR in admin/$ap\n";
        } else {
            echo " [✓] OK: admin/$ap (" . strlen($out) . " bytes)\n";
        }
    } catch (Throwable $t) {
        ob_end_clean();
        $failedPages++;
        echo " [X] EXCEPTION in admin/$ap: " . $t->getMessage() . "\n";
    }
}

if ($failedPages > 0) {
    echo "\nADMIN PAGES TEST FAILED: {$failedPages} pages had errors.\n";
    exit(1);
}
echo "\nALL 11 ADMIN PAGES RENDERED CLEANLY.\n";
exit(0);
