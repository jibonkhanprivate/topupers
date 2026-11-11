<?php
$html = file_get_contents('http://localhost/RechaX%20Official/game.php?slug=mobile-legends');

// Extract packageGrid section
if (preg_match('/<div class="rx-pkg-grid-2col" id="packageGrid">(.*?)<\/div>\s*<!-- Show More/s', $html, $m)) {
    echo "=== FOUND packageGrid ===\n";
    // Show first 2000 chars
    echo substr($m[0], 0, 2000) . "\n";
} else {
    echo "=== packageGrid NOT MATCHED! Searching for packageGrid ===\n";
    $pos = strpos($html, 'packageGrid');
    if ($pos !== false) {
        echo substr($html, $pos - 100, 1000);
    } else {
        echo "packageGrid not found in HTML!\n";
    }
}
