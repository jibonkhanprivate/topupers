<?php
require_once __DIR__ . '/run_unified_diff.php';

function printUnifiedDiffC44(string $relPath) {
    $oldPath = __DIR__ . '/../backups/backup_phase_c4_4_20261013_180000/' . $relPath;
    $newPath = __DIR__ . '/../' . $relPath;

    $old = file($oldPath);
    $new = file($newPath);

    echo "======================================================================\n";
    echo "PHASE C4.4 DIFF FOR: {$relPath}\n";
    echo "======================================================================\n";

    if (implode('', $old) === implode('', $new)) {
        echo "No changes.\n\n";
        return;
    }

    $diff = diffLines($old, $new);
    $context = 3;
    $len = count($diff);

    for ($idx = 0; $idx < $len; $idx++) {
        $type = $diff[$idx][0];
        if ($type !== ' ') {
            $start = max(0, $idx - $context);
            $end = min($len - 1, $idx + $context);
            while ($end < $len - 1) {
                $nextChange = -1;
                for ($k = $end + 1; $k <= min($len - 1, $end + $context * 2); $k++) {
                    if ($diff[$k][0] !== ' ') {
                        $nextChange = $k;
                        break;
                    }
                }
                if ($nextChange !== -1) {
                    $end = min($len - 1, $nextChange + $context);
                } else {
                    break;
                }
            }

            for ($p = $start; $p <= $end; $p++) {
                echo $diff[$p][0] . ' ' . rtrim($diff[$p][1]) . "\n";
            }
            echo "----------------------------------------------------------------------\n";
            $idx = $end;
        }
    }
    echo "\n";
}

printUnifiedDiffC44('admin/orders.php');
