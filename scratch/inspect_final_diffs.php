<?php
$backupDir = __DIR__ . '/../backups/backup_phase_c4_2_20261013_172500';
$files = [
    'admin/orders.php',
    'checkout.php',
    'game.php',
    'includes/currency.php'
];

foreach ($files as $f) {
    echo "======================================================================\n";
    echo "FILE DIFF: $f\n";
    echo "======================================================================\n";
    $liveLines = file(__DIR__ . '/../' . $f);
    $backLines = file($backupDir . '/' . $f);

    // Simple line diff
    $max = max(count($liveLines), count($backLines));
    $inDiff = false;
    for ($i = 0; $i < $max; $i++) {
        $l = $liveLines[$i] ?? null;
        $b = $backLines[$i] ?? null;
        if ($l !== $b) {
            echo "Line " . ($i + 1) . ":\n";
            if ($b !== null) echo "  - " . rtrim($b) . "\n";
            if ($l !== null) echo "  + " . rtrim($l) . "\n";
        }
    }
    echo "\n";
}
