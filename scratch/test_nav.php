<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$secs = getHomepageSections(true);
echo "Rendered Navigation Labels:\n";
foreach ($secs as $s) {
    $label = !empty($s['nav_title']) ? $s['nav_title'] : $s['title'];
    echo "  [{$s['icon']}] {$label} (Full: {$s['title']})\n";
}
