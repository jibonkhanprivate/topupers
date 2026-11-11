<?php
require 'config/database.php';
$stmt = db()->query('DESCRIBE users');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
