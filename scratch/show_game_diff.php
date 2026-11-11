<?php
$live = file(__DIR__ . '/../game.php');
$back = file(__DIR__ . '/../backups/backup_phase_c4_2_20261013_152500/game.php');

echo "=== game.php diff ===\n";
for ($i = 0; $i < max(count($live), count($back)); $i++) {
    $l = $live[$i] ?? '';
    $b = $back[$i] ?? '';
    if ($l !== $b) {
        echo "L" . ($i+1) . " B: " . rtrim($b) . "\n";
        echo "L" . ($i+1) . " L: " . rtrim($l) . "\n";
    }
}
