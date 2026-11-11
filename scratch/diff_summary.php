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
    echo "=== FILE: {$f} ===\n";
    $orig = file_get_contents($backupDir . '/' . $f);
    $curr = file_get_contents($f);
    if ($orig === $curr) {
        echo "NO CHANGES\n\n";
        continue;
    }
    
    $origLines = explode("\n", str_replace("\r\n", "\n", $orig));
    $currLines = explode("\n", str_replace("\r\n", "\n", $curr));
    
    echo "Original lines: " . count($origLines) . ", Current lines: " . count($currLines) . "\n";
    
    // Find modified regions
    $max = max(count($origLines), count($currLines));
    $diffBlocks = [];
    $inDiff = false;
    $start = 0;
    
    for ($i = 0; $i < $max; $i++) {
        $o = $origLines[$i] ?? null;
        $c = $currLines[$i] ?? null;
        if ($o !== $c) {
            if (!$inDiff) {
                $inDiff = true;
                $start = $i;
            }
        } else {
            if ($inDiff) {
                $diffBlocks[] = [$start + 1, $i];
                $inDiff = false;
            }
        }
    }
    if ($inDiff) {
        $diffBlocks[] = [$start + 1, $max];
    }
    
    echo "Diff ranges (approx): " . json_encode($diffBlocks) . "\n\n";
}
