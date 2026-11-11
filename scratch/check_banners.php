<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "BANNERS:\n";
$banners = getBanners(true);
print_r($banners);

echo "\nHOMEPAGE SECTIONS:\n";
$sections = getHomepageSections(true);
print_r($sections);

echo "\nSETTINGS:\n";
$stmt = db()->query("SELECT `key`, `value` FROM settings WHERE `key` LIKE '%banner%' OR `key` LIKE '%hero%'");
print_r($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
