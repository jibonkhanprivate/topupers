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
    echo "======================================================================\n";
    echo "SEMANTIC DIFF FOR: {$f}\n";
    echo "======================================================================\n";
    
    $orig = file($backupDir . '/' . $f, FILE_IGNORE_NEW_LINES);
    $curr = file($f, FILE_IGNORE_NEW_LINES);
    
    // We can do a simple LCS diff or unified diff simulation
    $origMap = array_flip($orig);
    
    // Let's print lines that were added or modified in curr
    // Or run git diff equivalent via a simple diff function
    $lenO = count($orig);
    $lenC = count($curr);
    
    $i = 0; $j = 0;
    while ($i < $lenO || $j < $lenC) {
        if ($i < $lenO && $j < $lenC && $orig[$i] === $curr[$j]) {
            $i++; $j++;
            continue;
        }
        // difference found
        $startO = $i; $startC = $j;
        // look ahead to resync
        $syncO = -1; $syncC = -1;
        for ($scanC = $j; $scanC < min($j + 60, $lenC); $scanC++) {
            for ($scanO = $i; $scanO < min($i + 60, $lenO); $scanO++) {
                if ($orig[$scanO] === $curr[$scanC] && $orig[$scanO] !== '') {
                    $syncO = $scanO;
                    $syncC = $scanC;
                    break 2;
                }
            }
        }
        
        if ($syncO !== -1) {
            echo "@@ -{$startO} +{$startC} @@\n";
            for ($k = $startO; $k < $syncO; $k++) {
                echo "- " . $orig[$k] . "\n";
            }
            for ($k = $startC; $k < $syncC; $k++) {
                echo "+ " . $curr[$k] . "\n";
            }
            $i = $syncO;
            $j = $syncC;
        } else {
            echo "@@ -{$startO} +{$startC} @@\n";
            while ($i < $lenO) { echo "- " . $orig[$i++] . "\n"; }
            while ($j < $lenC) { echo "+ " . $curr[$j++] . "\n"; }
        }
    }
    echo "\n";
}
