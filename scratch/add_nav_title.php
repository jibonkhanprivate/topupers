<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();
$stmt = $db->query("SHOW COLUMNS FROM homepage_sections LIKE 'nav_title'");
if (!$stmt->fetch()) {
    $db->exec("ALTER TABLE homepage_sections ADD COLUMN nav_title VARCHAR(80) NULL AFTER title");
    echo "Added nav_title column to homepage_sections.\n";
} else {
    echo "nav_title already exists.\n";
}

// Update default sections with nice concise nav titles
$defaults = [
    'popular-games' => 'Popular',
    'topup-player-id' => 'Player ID',
    'topup-login-method' => 'Login Method',
    'redeem-gift-cards' => 'Gift Cards',
    'subscriptions' => 'Subscriptions',
    'pc-games' => 'PC Games',
];

$up = $db->prepare("UPDATE homepage_sections SET nav_title = ? WHERE section_key = ?");
foreach ($defaults as $k => $v) {
    $up->execute([$v, $k]);
}
echo "Updated default nav titles.\n";
