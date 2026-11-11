<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$html = file_get_contents(__DIR__ . '/../admin/includes/header.php');
preg_match_all('/admin\/([a-zA-Z0-9_\-\.]+\.php)/', $html, $m);
$files = array_unique($m[1]);

echo "Found " . count($files) . " unique admin page links in header.php\n\n";

$missing = [];
$errors = [];

startAdminSession();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Super Admin';
$_SESSION['admin_role'] = 'superadmin';

foreach ($files as $f) {
    $path = __DIR__ . '/../admin/' . $f;
    if (!file_exists($path)) {
        echo "[MISSING FILE] admin/$f does not exist!\n";
        $missing[] = $f;
        continue;
    }
    
    if ($f === 'logout.php') continue;

    $_SERVER['REQUEST_URI'] = '/RechaX%20Official/admin/' . $f;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    $_POST = [];
    
    ob_start();
    try {
        include $path;
        $out = ob_get_clean();
        
        $hasError = false;
        $errMsg = '';
        if (preg_match('/(Fatal error|Parse error|Uncaught Exception|PDOException|SQLSTATE)/i', $out, $errMatch)) {
            $hasError = true;
            $errMsg = $errMatch[0];
        }
        
        if ($hasError) {
            echo "[ERROR] admin/$f -> $errMsg\n";
            $errors[$f] = substr(strip_tags($out), 0, 300);
        } else {
            echo "[OK] admin/$f (" . strlen($out) . " bytes)\n";
        }
    } catch (Throwable $t) {
        ob_end_clean();
        echo "[EXCEPTION] admin/$f -> " . $t->getMessage() . " at " . $t->getFile() . ":" . $t->getLine() . "\n";
        $errors[$f] = $t->getMessage();
    }
}

echo "\n=== SUMMARY ===\n";
echo "Missing files: " . count($missing) . "\n";
print_r($missing);
echo "Error pages: " . count($errors) . "\n";
if (!empty($errors)) {
    print_r($errors);
}
