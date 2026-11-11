<?php
$files = [
    'admin/orders.php',
    'checkout.php',
    'game.php',
    'includes/currency.php'
];

foreach ($files as $f) {
    $b = __DIR__ . '/../backups/backup_phase_c4_2_20261013_152500/' . $f;
    $livePath = __DIR__ . '/../' . $f;
    if (!file_exists($b)) {
        echo "[$f] No backup found.\n";
        continue;
    }
    $c1 = file_get_contents($livePath);
    $c2 = file_get_contents($b);
    if ($c1 === $c2) {
        echo "[$f] IDENTICAL to backup_phase_c4_2_20261013_152500\n";
    } else {
        echo "[$f] DIFFERENT (Live: " . strlen($c1) . " bytes, Backup: " . strlen($c2) . " bytes)\n";
    }
}
