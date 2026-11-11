<?php
require_once __DIR__ . '/../config/database.php';
$stmt = db()->query("DESCRIBE orders");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r["Field"] . " (" . $r["Type"] . ")\n";
}
