<?php
$dir = dirname(__DIR__);
$cutoff = time() - (4 * 3600); // last 4 hours

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
$modified = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = str_replace('\\', '/', $file->getPathname());
    // exclude scratch, backups, .gemini, .git
    if (preg_match('#/(scratch|backups|\.gemini|\.git)/#i', $path)) continue;
    
    if ($file->getMTime() >= $cutoff) {
        $relPath = str_replace(str_replace('\\', '/', $dir) . '/', '', $path);
        $modified[] = [
            'file' => $relPath,
            'time' => date('Y-m-d H:i:s', $file->getMTime()),
            'size' => $file->getSize()
        ];
    }
}

echo json_encode($modified, JSON_PRETTY_PRINT);
