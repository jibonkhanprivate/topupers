<?php
require_once __DIR__ . '/run_unified_diff.php';

$files = ['checkout.php', 'game.php', 'admin/orders.php'];

foreach ($files as $f) {
    echo "======================================================================\n";
    echo "EXACT C5.2 DIFF: $f\n";
    echo "======================================================================\n";
    $oldPath = __DIR__ . '/../backups/backup_phase_c5_2_20261013_182600/' . $f;
    $newPath = __DIR__ . '/../' . $f;

    $old = file($oldPath);
    $new = file($newPath);

    $diff = diffLines($old, $new);
    $hasChange = false;
    foreach ($diff as $d) {
        if ($d[0] !== ' ') {
            echo $d[0] . ' ' . rtrim($d[1]) . "\n";
            $hasChange = true;
        }
    }
    if (!$hasChange) {
        echo "No changes detected.\n";
    }
    echo "\n";
}
