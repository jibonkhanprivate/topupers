<?php
require_once __DIR__ . '/../config/database.php';

$defaults = [
    'support_widget_active' => '1',
    'support_title'         => 'TopUpers Help Desk',
    'support_subtitle'      => 'Fast response • 24/7 Gaming Support',
    'support_message'       => 'Hello TopUpers Support! I need assistance with my order/account:',
    'support_whatsapp'      => '8801700000000',
    'support_messenger'     => 'topupers.official',
    'support_telegram'      => 'topupers_support',
    'support_email'         => 'support@topupers.com',
    'notify_new_order'      => '1',
    'notify_new_deposit'    => '1',
    'notify_new_user'       => '1',
    'site_name'             => 'TopUpers',
    'site_tagline'          => 'Fast & Reliable Gaming Top-Up Platform'
];

$stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value)");
foreach ($defaults as $k => $v) {
    $stmt->execute([$k, $v]);
    echo "Configured $k => $v\n";
}
echo "Default support and notification settings seeded successfully!\n";
