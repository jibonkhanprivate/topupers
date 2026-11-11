<?php
require 'config/database.php';
$stmt = db()->query('DESCRIBE products');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
