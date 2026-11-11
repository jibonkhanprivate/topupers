<?php
require_once __DIR__ . '/run_unified_diff.php';

foreach (['checkout.php', 'game.php'] as $file) {
    echo "=== DIFF FOR: $file ===\n";
    $old = file(__DIR__ . '/../backups/backup_phase_c5_2_20261013_182600/' . $file);
    $new = file(__DIR__ . '/../' . $file);
    $diff = diffLines($old, $new);
    foreach ($diff as $d) {
        if ($d[0] !== ' ') {
            echo $d[0] . ' ' . rtrim($d[1]) . "\n";
        }
    }
    echo "\n";
}
