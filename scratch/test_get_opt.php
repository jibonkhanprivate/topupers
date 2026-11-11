<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$opt = getSelectorOptionById(1);
echo "Result for ID 1:\n";
print_r($opt);
