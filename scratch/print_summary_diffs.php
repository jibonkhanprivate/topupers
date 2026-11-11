<?php
$backupDir = 'backups/backup_phase_c4_20261013_144500';
$files = [
    'includes/currency.php',
    'game.php',
    'checkout.php',
    'order-success.php',
    'user/orders.php',
    'admin/orders.php'
];

foreach ($files as $f) {
    echo "=== SUMMARY DIFF: {$f} ===\n";
    $orig = file($backupDir . '/' . $f, FILE_IGNORE_NEW_LINES);
    $curr = file($f, FILE_IGNORE_NEW_LINES);
    
    // Output line additions, deletions, replacements
    // Simple line matcher:
    $diffOutput = [];
    $i = 0; $j = 0;
    while ($i < count($orig) || $j < count($curr)) {
        if (isset($orig[$i]) && isset($curr[$j]) && $orig[$i] === $curr[$j]) {
            $i++; $j++;
            continue;
        }
        $lineO = $i + 1;
        $lineC = $j + 1;
        
        // Find match
        $foundMatch = false;
        for ($scanC = $j; $scanC < min($j + 150, count($curr)); $scanC++) {
            for ($scanO = $i; $scanO < min($i + 150, count($orig)); $scanO++) {
                if ($orig[$scanO] === $curr[$scanC] && trim($orig[$scanO]) !== '') {
                    echo "--- Change at Orig Line {$lineO} / Curr Line {$lineC} ---\n";
                    for ($k = $i; $k < $scanO; $k++) {
                        echo "- " . $orig[$k] . "\n";
                    }
                    for ($k = $j; $k < $scanC; $k++) {
                        echo "+ " . $curr[$k] . "\n";
                    }
                    $i = $scanO;
                    $j = $scanC;
                    $foundMatch = true;
                    break 2;
                }
            }
        }
        if (!$foundMatch) {
            echo "--- Trailing Change at Orig Line {$lineO} / Curr Line {$lineC} ---\n";
            while ($i < count($orig)) echo "- " . $orig[$i++] . "\n";
            while ($j < count($curr)) echo "+ " . $curr[$j++] . "\n";
        }
    }
    echo "\n";
}
