<?php
/**
 * Pure PHP Myers diff / simple diff algorithm to produce standard unified diff.
 */
function diffLines(array $from, array $to): array {
    $n = count($from);
    $m = count($to);
    $matrix = [];
    for ($i = 0; $i <= $n; $i++) {
        $matrix[$i][0] = 0;
    }
    for ($j = 0; $j <= $m; $j++) {
        $matrix[0][$j] = 0;
    }
    for ($i = 1; $i <= $n; $i++) {
        for ($j = 1; $j <= $m; $j++) {
            if ($from[$i - 1] === $to[$j - 1]) {
                $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
            } else {
                $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
            }
        }
    }
    // backtrack
    $i = $n;
    $j = $m;
    $diff = [];
    while ($i > 0 || $j > 0) {
        if ($i > 0 && $j > 0 && $from[$i - 1] === $to[$j - 1]) {
            $diff[] = [' ', $from[$i - 1]];
            $i--;
            $j--;
        } elseif ($j > 0 && ($i === 0 || $matrix[$i][$j - 1] >= $matrix[$i - 1][$j])) {
            $diff[] = ['+', $to[$j - 1]];
            $j--;
        } elseif ($i > 0 && ($j === 0 || $matrix[$i][$j - 1] < $matrix[$i - 1][$j])) {
            $diff[] = ['-', $from[$i - 1]];
            $i--;
        }
    }
    return array_reverse($diff);
}

function printUnifiedDiff(string $relPath) {
    $oldPath = __DIR__ . '/../backups/backup_phase_c4_2_20261013_172500/' . $relPath;
    $newPath = __DIR__ . '/../' . $relPath;

    $old = file($oldPath);
    $new = file($newPath);

    // If file is large, only compare changed sections or use chunking
    echo "======================================================================\n";
    echo "DIFF FOR: {$relPath}\n";
    echo "======================================================================\n";

    // Fast check if identical
    if (implode('', $old) === implode('', $new)) {
        echo "No changes.\n\n";
        return;
    }

    $diff = diffLines($old, $new);
    $context = 3;
    $len = count($diff);
    $chunks = [];
    $currentChunk = [];

    for ($idx = 0; $idx < $len; $idx++) {
        $type = $diff[$idx][0];
        if ($type !== ' ') {
            // Include surrounding context
            $start = max(0, $idx - $context);
            $end = min($len - 1, $idx + $context);
            // look ahead for next change to merge
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

$files = [
    'admin/orders.php',
    'checkout.php',
    'game.php',
    'includes/currency.php'
];

foreach ($files as $f) {
    printUnifiedDiff($f);
}
