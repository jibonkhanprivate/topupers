<?php
$backupDir = __DIR__ . '/../backups/backup_phase_c5_2_20261013_182600';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}
if (!is_dir($backupDir . '/admin')) {
    mkdir($backupDir . '/admin', 0777, true);
}

$files = [
    'checkout.php'     => $backupDir . '/checkout.php',
    'game.php'         => $backupDir . '/game.php',
    'admin/orders.php' => $backupDir . '/admin/orders.php',
];

foreach ($files as $src => $dst) {
    $srcPath = __DIR__ . '/../' . $src;
    if (file_exists($srcPath)) {
        copy($srcPath, $dst);
        echo "Backed up $src -> $dst (" . filesize($dst) . " bytes)\n";
    } else {
        echo "ERROR: File not found: $src\n";
    }
}
