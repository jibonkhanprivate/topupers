<?php
$backupDir = __DIR__ . '/../backups/backup_phase_c4_4_20261013_180000';
$files = [
    'admin/orders.php'
];

foreach ($files as $f) {
    $src = __DIR__ . '/../' . $f;
    $dst = $backupDir . '/' . $f;
    $dir = dirname($dst);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    copy($src, $dst);
    echo "Backed up $f -> " . realpath($dst) . " (" . filesize($dst) . " bytes)\n";
}
