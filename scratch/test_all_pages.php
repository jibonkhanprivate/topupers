<?php
$pages = [
    'index.php',
    'shop.php',
    'about.php',
    'contact.php',
    'faq.php',
    'privacy.php',
    'refund.php',
    'terms.php',
    'search.php',
    'checkout.php',
    'game.php',
    'auth/login.php',
    'auth/register.php',
    'user/add-money.php',
    'ajax/switch_currency.php',
    'includes/currency.php',
    'includes/header.php',
    'includes/write_freeze.php',
    'cron/sync_exchange_rate.php'
];

$errors = [];

foreach ($pages as $p) {
    $cmd = '"C:\xampp\php\php.exe" -l "' . __DIR__ . '/../' . $p . '"';
    $output = shell_exec($cmd);
    if (strpos($output, 'No syntax errors detected') === false) {
        $errors[] = "$p: Syntax Error -> $output";
    } else {
        echo " [✓] Syntax OK: $p\n";
    }
}

if (empty($errors)) {
    echo "\nAll primary pages syntax verified successfully!\n";
    exit(0);
} else {
    echo "\nErrors found:\n" . implode("\n", $errors) . "\n";
    exit(1);
}
