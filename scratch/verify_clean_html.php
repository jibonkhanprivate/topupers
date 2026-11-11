<?php
$html = file_get_contents('http://localhost/RechaX%20Official/game.php?slug=mobile-legends');

$warnings = [];
if (preg_match_all('/(Warning|Fatal error|Notice|Deprecated):.*?(?:<br|\n)/i', $html, $m)) {
    $warnings = $m[0];
}

if (!empty($warnings)) {
    echo "PHP ERRORS/WARNINGS DETECTED:\n";
    foreach ($warnings as $w) {
        echo strip_tags($w) . "\n";
    }
} else {
    echo "CLEAN! ZERO PHP warnings or errors in output.\n";
}

// Check package count rendered
$count = substr_count($html, 'class="rx-modern-card');
echo "Rendered rx-modern-card count: " . $count . "\n";

// Check if packageGrid contains only rx-modern-card
if (preg_match('/<div class="rx-pkg-grid-2col" id="packageGrid">(.*?)<\/div>\s*<!-- Show More/s', $html, $gridMatch)) {
    $gridContent = $gridMatch[1];
    $hasWarnings = strpos($gridContent, 'Warning') !== false;
    echo "packageGrid contains warnings? " . ($hasWarnings ? 'YES' : 'NO') . "\n";
}
