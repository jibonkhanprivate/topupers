<?php
$backupDir = __DIR__ . '/../backups/backup_phase_c4_20261013_144500';
$targetDir = __DIR__ . '/..';

$files = [
    'includes/currency.php',
    'game.php',
    'checkout.php',
    'order-success.php',
    'user/orders.php',
    'admin/orders.php'
];

foreach ($files as $rel) {
    $backupFile = $backupDir . '/' . $rel;
    $currFile = $targetDir . '/' . $rel;
    
    echo "======================================================================\n";
    echo "DIFF FOR: {$rel}\n";
    echo "Backup size: " . filesize($backupFile) . " bytes | Current size: " . filesize($currFile) . " bytes\n";
    echo "======================================================================\n";
    
    $oldLines = file($backupFile, FILE_IGNORE_NEW_LINES);
    $newLines = file($currFile, FILE_IGNORE_NEW_LINES);
    
    // Quick diff by line chunks
    $oldLen = count($oldLines);
    $newLen = count($newLines);
    echo "Old line count: {$oldLen} | New line count: {$newLen}\n\n";
}
