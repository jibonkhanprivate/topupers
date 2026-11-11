<?php
$urls = [
    'Home'          => 'http://localhost/RechaX%20Official/',
    'Game'          => 'http://localhost/RechaX%20Official/game.php?slug=free-fire',
    'Checkout'      => 'http://localhost/RechaX%20Official/checkout.php',
    'Shop'          => 'http://localhost/RechaX%20Official/shop.php',
    'Auth Login'    => 'http://localhost/RechaX%20Official/auth/login.php',
    'Auth Register' => 'http://localhost/RechaX%20Official/auth/register.php',
];

foreach ($urls as $name => $url) {
    echo "Testing $name ($url)...\n";
    $html = @file_get_contents($url);
    if ($html === false) {
        echo "  [FAIL] Could not load $url\n";
        continue;
    }
    preg_match_all('/(Warning|Fatal error|Notice|Deprecated):.*?(?:<br|\n)/i', $html, $matches);
    if (!empty($matches[0])) {
        echo "  [FOUND ERRORS/WARNINGS]:\n";
        foreach ($matches[0] as $err) {
            echo "    - " . strip_tags($err) . "\n";
        }
    } else {
        echo "  [OK] Clean, no PHP warnings/errors in output (" . strlen($html) . " bytes).\n";
    }

    if ($name === 'Auth Login') {
        echo "    Has 'Welcome Back!': " . (strpos($html, 'Welcome Back!') !== false ? 'YES' : 'NO') . "\n";
        echo "    Has Login tab active: " . (strpos($html, 'id="tabBtnLogin"') !== false && strpos($html, 'aria-selected="true"') !== false ? 'YES' : 'NO') . "\n";
    }
    if ($name === 'Auth Register') {
        echo "    Has 'Join Us!': " . (strpos($html, 'Join Us!') !== false ? 'YES' : 'NO') . "\n";
        echo "    Has Register tab active: " . (strpos($html, 'id="tabBtnRegister"') !== false && strpos($html, 'aria-selected="true"') !== false ? 'YES' : 'NO') . "\n";
        echo "    Has WhatsApp field: " . (strpos($html, 'WhatsApp Number') !== false ? 'YES' : 'NO') . "\n";
    }
}
