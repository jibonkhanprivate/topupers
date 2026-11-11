<?php
$css = file_get_contents(__DIR__ . '/../assets/css/customer-dashboard.css');
preg_match_all('/([^{]+)\{/', $css, $m);
$selectors = array_map('trim', $m[1]);
$counts = array_count_values($selectors);
$dups = array_filter($counts, fn($c) => $c > 1);
if (!empty($dups)) {
    echo "Duplicate CSS selectors:\n";
    print_r($dups);
} else {
    echo "No duplicate CSS selectors found.\n";
}
