<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("SELECT id, name, slug, image, cover_image, banner_1 FROM games");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
