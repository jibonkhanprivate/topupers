<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();

// Check if show_in_nav column exists in homepage_sections
$stmt = $db->query("SHOW COLUMNS FROM homepage_sections LIKE 'show_in_nav'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$col) {
    $db->exec("ALTER TABLE homepage_sections ADD COLUMN show_in_nav TINYINT(1) NOT NULL DEFAULT 1 AFTER show_view_more");
    echo "Added show_in_nav column to homepage_sections successfully.\n";
} else {
    echo "show_in_nav column already exists in homepage_sections.\n";
}

// Check current value of category_nav_enabled in settings
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'category_nav_enabled'");
$stmt->execute();
$val = $stmt->fetchColumn();
echo "Current category_nav_enabled in settings: " . var_export($val, true) . "\n";

// List all sections with show_in_nav status
$stmt = $db->query("SELECT id, section_key, title, nav_title, is_active, show_in_nav FROM homepage_sections ORDER BY sort_order ASC, id ASC");
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Current sections:\n";
foreach ($sections as $s) {
    echo " - ID: {$s['id']}, Key: {$s['section_key']}, Title: {$s['title']}, Nav: {$s['nav_title']}, Active: {$s['is_active']}, ShowInNav: {$s['show_in_nav']}\n";
}
