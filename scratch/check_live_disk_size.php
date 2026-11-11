<?php
$pdo = new PDO('mysql:host=localhost;dbname=rechax_official;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("
    SELECT 
        table_name,
        round(((data_length + index_length) / 1024 / 1024), 4) AS total_mb,
        round((data_length / 1024 / 1024), 4) AS data_mb,
        round((index_length / 1024 / 1024), 4) AS index_mb,
        table_rows
    FROM information_schema.TABLES 
    WHERE table_schema = 'rechax_official' AND table_name = 'orders'
");
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";

// Also check free disk space on drive C:
$drive = 'C:';
$freeBytes = disk_free_space($drive);
$totalBytes = disk_total_space($drive);
echo "Free Disk Space on $drive: " . round($freeBytes / 1024 / 1024 / 1024, 2) . " GB / " . round($totalBytes / 1024 / 1024 / 1024, 2) . " GB\n";
