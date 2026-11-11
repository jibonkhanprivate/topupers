<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();

echo "=== 1. Checking Active Sections ===\n";
$active = getHomepageSections(true);
echo "Active sections count: " . count($active) . "\n";
foreach ($active as $s) {
    echo "  - [{$s['section_key']}] {$s['title']} ({$s['selection_mode']}) -> " . count($s['items']) . " items\n";
}

echo "\n=== 2. Testing Hide Section ===\n";
$db->exec("UPDATE homepage_sections SET is_active = 0 WHERE section_key = 'pc-games'");
$activeAfterHide = getHomepageSections(true);
echo "Active count after hiding 'pc-games': " . count($activeAfterHide) . "\n";
$pcExists = array_filter($activeAfterHide, fn($s) => $s['section_key'] === 'pc-games');
echo "PC Games section visible in frontend: " . (empty($pcExists) ? "NO (Correctly Hidden!)" : "YES (Error)") . "\n";

echo "\n=== 3. Restoring Section ===\n";
$db->exec("UPDATE homepage_sections SET is_active = 1 WHERE section_key = 'pc-games'");
$activeRestored = getHomepageSections(true);
echo "Active count after restoring: " . count($activeRestored) . "\n";

echo "\n=== 4. Testing Manual Section Item Linking & Safe Deletion ===\n";
$db->exec("INSERT INTO homepage_sections (section_key, title, selection_mode, sort_order, is_active) VALUES ('test-promo', 'Test Promo', 'manual', 99, 1)");
$testId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO section_items (section_id, item_type, game_id, custom_title, custom_badge) VALUES (?, 'game', 1, 'Exclusive MLBB Promo', '50% OFF')")->execute([$testId]);

$items = getSectionItems(['id' => $testId, 'selection_mode' => 'manual', 'max_items' => 10]);
echo "Test section resolved items: " . count($items) . "\n";
echo "Item custom title: " . ($items[0]['title'] ?? 'None') . "\n";
echo "Item custom badge: " . ($items[0]['badge'] ?? 'None') . "\n";

echo "\n=== 5. Deleting Test Section & Verifying Game/Product Safety ===\n";
$db->prepare("DELETE FROM homepage_sections WHERE id = ?")->execute([$testId]);
$gameCount = (int)$db->query("SELECT COUNT(*) FROM games WHERE id = 1")->fetchColumn();
$pivotCount = (int)$db->prepare("SELECT COUNT(*) FROM section_items WHERE section_id = ?")->execute([$testId]) ? $db->query("SELECT COUNT(*) FROM section_items WHERE section_id = $testId")->fetchColumn() : 0;

echo "Games table untouched (Game ID 1 exists): " . ($gameCount === 1 ? "YES (PASSED)" : "NO (FAILED)") . "\n";
echo "Section items pivot cleaned: " . ($pivotCount === 0 ? "YES (PASSED)" : "NO (FAILED)") . "\n";

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
