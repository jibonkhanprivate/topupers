<?php
function showDiff(string $relPath) {
    $live = file(__DIR__ . '/../' . $relPath);
    $back = file(__DIR__ . '/../backups/backup_phase_c4_2_20261013_152500/' . $relPath);
    echo "=== DIFF FOR {$relPath} ===\n";
    $max = max(count($live), count($back));
    for ($i = 0; $i < $max; $i++) {
        $l = $live[$i] ?? null;
        $b = $back[$i] ?? null;
        if ($l !== $b) {
            echo "Line " . ($i + 1) . ":\n";
            echo "  BACKUP: " . ($b !== null ? rtrim($b) : '[EOF]') . "\n";
            echo "  LIVE:   " . ($l !== null ? rtrim($l) : '[EOF]') . "\n";
        }
    }
}

showDiff('game.php');
echo "\n";
showDiff('includes/currency.php');
