<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("SELECT id, name, slug, requires_server_id, server_label, player_label, validation_provider FROM games ORDER BY id");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Slug: {$r['slug']} | ReqServer: {$r['requires_server_id']} | Provider: " . ($r['validation_provider'] ?? 'NULL') . "\n";
}
