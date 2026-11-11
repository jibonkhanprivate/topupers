<?php
$dir = realpath(__DIR__ . '/..');
$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($it);
$count = 0;
$matches = [];

foreach ($files as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $rel = str_replace($dir, '', $path);
    if (strpos($rel, '\\scratch\\') === 0 || strpos($rel, '/scratch/') === 0 || strpos($rel, '\\uploads\\') === 0 || strpos($rel, '/.git') === 0) {
        continue;
    }
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'sql', 'js', 'css', 'html', 'json', 'md'])) continue;
    
    $lines = file($path);
    foreach ($lines as $num => $line) {
        if (stripos($line, 'rechax') !== false) {
            $matches[$rel][] = ($num + 1) . ': ' . trim($line);
        }
    }
}

foreach ($matches as $file => $fileLines) {
    echo "\n=== $file (" . count($fileLines) . ") ===\n";
    foreach ($fileLines as $fl) {
        echo "  $fl\n";
    }
}
