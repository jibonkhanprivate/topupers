<?php
foreach (['mobile-legends', 'pubg-mobile', 'free-fire'] as $slug) {
    $html = file_get_contents("http://localhost/RechaX%20Official/game.php?slug=$slug");
    $footerCount = substr_count($html, 'class="rx-footer');
    echo "$slug footer count: $footerCount\n";
}
