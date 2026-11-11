<?php
$file = __DIR__ . '/../backups/backup_gate2b_20261012_035539/db_gate2b_preflight_20261012_035539.sql';
if (!file_exists($file)) {
    echo "FILE NOT FOUND: $file\n";
    exit(1);
}

echo "EXISTS: YES\n";
echo "FILE PATH: " . realpath($file) . "\n";
echo "BYTE SIZE: " . filesize($file) . "\n";
echo "SHA256: " . hash_file('sha256', $file) . "\n";

$fp = fopen($file, 'r');
$dbName = 'unknown';
$tables = [];

while (($line = fgets($fp)) !== false) {
    if (preg_match('/--\s*Host:.*?Database:\s*([a-zA-Z0-9_]+)/i', $line, $m)) {
        $dbName = $m[1];
    } elseif (preg_match('/Current Database:\s*`([^`]+)`/i', $line, $m)) {
        $dbName = $m[1];
    }
    if (preg_match('/-- Table structure for table `([^`]+)`/i', $line, $m)) {
        if (!in_array($m[1], $tables)) {
            $tables[] = $m[1];
        }
    } elseif (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`([^`]+)`/i', $line, $m)) {
        if (!in_array($m[1], $tables)) {
            $tables[] = $m[1];
        }
    }
}
fclose($fp);

echo "DATABASE NAME: " . $dbName . "\n";
echo "TABLE COUNT: " . count($tables) . "\n";
echo "TABLES LIST:\n";
foreach ($tables as $i => $t) {
    echo "  [" . ($i + 1) . "] " . $t . "\n";
}
