<?php
$lines = file(__DIR__ . '/../assets/css/admin.css');
$depth = 0;
foreach ($lines as $num => $line) {
    // Strip comments
    $stripped = preg_replace('!/\*.*?\*/!', '', $line);
    $opens = substr_count($stripped, '{');
    $closes = substr_count($stripped, '}');
    $depth += $opens - $closes;
    if ($depth < 0) {
        echo "ERROR: Depth became negative at line " . ($num + 1) . ": $line\n";
        break;
    }
}
echo "Final depth: $depth\n";
