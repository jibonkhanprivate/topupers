<?php
/**
 * scratch/check_app_insert_queries.php
 * Scan application files for INSERT and SELECT queries on orders and payments.
 */

$root = 'c:/xampp/htdocs/RechaX Official';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$insertOrdersMatches = [];
$insertPaymentsMatches = [];

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    // Exclude backups and scratch
    if (strpos($path, 'backups') !== false || strpos($path, 'scratch') !== false) continue;
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    $content = file_get_contents($path);

    if (stripos($content, 'INSERT INTO orders') !== false || stripos($content, 'INSERT INTO `orders`') !== false) {
        preg_match_all('/INSERT\s+INTO\s+`?orders`?\s*(\([^\)]+\)|VALUES)/i', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $m) {
            $insertOrdersMatches[] = [
                'file' => str_replace($root . DIRECTORY_SEPARATOR, '', $path),
                'snippet' => substr($content, max(0, $m[1] - 20), 200)
            ];
        }
    }

    if (stripos($content, 'INSERT INTO payments') !== false || stripos($content, 'INSERT INTO `payments`') !== false) {
        preg_match_all('/INSERT\s+INTO\s+`?payments`?\s*(\([^\)]+\)|VALUES)/i', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $m) {
            $insertPaymentsMatches[] = [
                'file' => str_replace($root . DIRECTORY_SEPARATOR, '', $path),
                'snippet' => substr($content, max(0, $m[1] - 20), 200)
            ];
        }
    }
}

echo "=== INSERT INTO ORDERS QUERIES IN APPLICATION ===\n";
foreach ($insertOrdersMatches as $m) {
    echo "File: {$m['file']}\nSnippet: " . trim($m['snippet']) . "\n---\n";
}

echo "\n=== INSERT INTO PAYMENTS QUERIES IN APPLICATION ===\n";
foreach ($insertPaymentsMatches as $m) {
    echo "File: {$m['file']}\nSnippet: " . trim($m['snippet']) . "\n---\n";
}
