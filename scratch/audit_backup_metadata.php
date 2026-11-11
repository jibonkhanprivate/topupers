<?php
$backupFile = __DIR__ . '/../backups/backup_gate2b_20261012_035539/db_gate2b_preflight_20261012_035539.sql';

$sha256 = hash_file('sha256', $backupFile);
echo "File: " . realpath($backupFile) . "\n";
echo "Size: " . filesize($backupFile) . " bytes\n";
echo "SHA-256: " . $sha256 . "\n";

$lines = file($backupFile);
echo "\n--- HEADER (First 15 lines) ---\n";
for ($i = 0; $i < min(15, count($lines)); $i++) {
    echo $lines[$i];
}

echo "\n--- FOOTER (Last 10 lines) ---\n";
for ($i = max(0, count($lines) - 10); $i < count($lines); $i++) {
    echo $lines[$i];
}
