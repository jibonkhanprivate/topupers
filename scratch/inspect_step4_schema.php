<?php
require_once __DIR__ . '/../config/database.php';
$pdo = db();

echo "--- SERVER & DB IDENTITY ---\n";
echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "Version:  " . $pdo->query("SELECT VERSION()")->fetchColumn() . "\n\n";

echo "--- ORDERS TABLE COLUMNS ---\n";
foreach ($pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo sprintf("%-20s | %-20s | %-4s | %s\n", $c['Field'], $c['Type'], $c['Null'], var_export($c['Default'], true));
}

echo "\n--- PAYMENTS TABLE COLUMNS ---\n";
foreach ($pdo->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo sprintf("%-20s | %-20s | %-4s | %s\n", $c['Field'], $c['Type'], $c['Null'], var_export($c['Default'], true));
}

echo "\n--- PAYMENT_METHODS ROWS ---\n";
foreach ($pdo->query("SELECT id, method_key, method_label, is_active, sort_order FROM payment_methods")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo sprintf("%-4d | %-12s | %-15s | active:%d | sort:%d\n", $r['id'], $r['method_key'], $r['method_label'], $r['is_active'], $r['sort_order']);
}

echo "\n--- CRON_JOBS ROWS ---\n";
foreach ($pdo->query("SELECT id, job_key, name, is_active, schedule_expression FROM cron_jobs")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo sprintf("%-4d | %-22s | %-25s | active:%d | %s\n", $r['id'], $r['job_key'], $r['name'], $r['is_active'], $r['schedule_expression']);
}
