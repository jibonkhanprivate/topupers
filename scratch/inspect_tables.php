<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("DESCRIBE suppliers");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r["Field"] . " (" . $r["Type"] . ")\n";
}
echo "--- Rows ---\n";
$stmt = db()->query("SELECT * FROM suppliers");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
