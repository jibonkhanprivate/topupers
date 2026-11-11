<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();

echo "=========================================================\n";
echo "TEST 1: Master category_nav_enabled Switch\n";
echo "=========================================================\n";

// Disable master category nav
setSetting('category_nav_enabled', '0');
$htmlDisabled = file_get_contents("http://localhost/RechaX%20Official/");
$hasNavWhenDisabled = strpos($htmlDisabled, 'rx-cat-nav-sticky') !== false;
echo "Category nav bar in HTML when category_nav_enabled=0: " . ($hasNavWhenDisabled ? "FAIL (Found)" : "PASS (Hidden!)") . "\n";

// Re-enable master category nav
setSetting('category_nav_enabled', '1');
$htmlEnabled = file_get_contents("http://localhost/RechaX%20Official/");
$hasNavWhenEnabled = strpos($htmlEnabled, 'rx-cat-nav-sticky') !== false;
echo "Category nav bar in HTML when category_nav_enabled=1: " . ($hasNavWhenEnabled ? "PASS (Visible!)" : "FAIL (Not found)") . "\n";

echo "\n=========================================================\n";
echo "TEST 2: Individual Pill Visibility (show_in_nav)\n";
echo "=========================================================\n";

// Hide "Player ID" pill specifically (section_key = 'topup-player-id')
$db->exec("UPDATE homepage_sections SET show_in_nav = 0 WHERE section_key = 'topup-player-id'");
$htmlPlayerPillHidden = file_get_contents("http://localhost/RechaX%20Official/");

$hasPopularPill = strpos($htmlPlayerPillHidden, 'id="catPill-popular-games"') !== false;
$hasPlayerPill = strpos($htmlPlayerPillHidden, 'id="catPill-topup-player-id"') !== false;
$hasPlayerSection = strpos($htmlPlayerPillHidden, 'id="section-topup-player-id"') !== false;

echo "Popular pill visible: " . ($hasPopularPill ? "PASS (Yes)" : "FAIL (No)") . "\n";
echo "Player ID pill hidden: " . (!$hasPlayerPill ? "PASS (Correctly Hidden from Nav!)" : "FAIL (Still in Nav)") . "\n";
echo "Player ID section still visible on homepage: " . ($hasPlayerSection ? "PASS (Section intact!)" : "FAIL (Section missing)") . "\n";

// Restore "Player ID" pill
$db->exec("UPDATE homepage_sections SET show_in_nav = 1 WHERE section_key = 'topup-player-id'");
$htmlRestored = file_get_contents("http://localhost/RechaX%20Official/");
$hasPlayerPillRestored = strpos($htmlRestored, 'id="catPill-topup-player-id"') !== false;
echo "Player ID pill restored: " . ($hasPlayerPillRestored ? "PASS (Visible again!)" : "FAIL (Still hidden)") . "\n";

echo "\n=========================================================\n";
echo "TEST 3: All Pills Hidden -> Nav Bar Automatically Omitted\n";
echo "=========================================================\n";
$db->exec("UPDATE homepage_sections SET show_in_nav = 0");
$htmlAllHidden = file_get_contents("http://localhost/RechaX%20Official/");
$hasNavWhenAllPillsHidden = strpos($htmlAllHidden, 'rx-cat-nav-sticky') !== false;
echo "Category nav bar when all pills marked hidden: " . (!$hasNavWhenAllPillsHidden ? "PASS (Omitted cleanly!)" : "FAIL (Empty container rendered)") . "\n";

// Restore all pills
$db->exec("UPDATE homepage_sections SET show_in_nav = 1");
echo "Restored all show_in_nav to 1.\n";

echo "\nALL TESTS COMPLETED!\n";
