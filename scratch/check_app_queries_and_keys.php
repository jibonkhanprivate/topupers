<?php
/**
 * scratch/check_app_queries_and_keys.php
 * Check indexes, foreign keys, and application queries affecting orders & payments.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();

echo "=== 1. FOREIGN KEYS ON ORDERS & PAYMENTS ===\n";
$fks = $pdo->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'rechax_official'
      AND TABLE_NAME IN ('orders', 'payments')
      AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($fks as $fk) {
    echo "  - Table `{$fk['TABLE_NAME']}`: Column `{$fk['COLUMN_NAME']}` references `{$fk['REFERENCED_TABLE_NAME']}`(`{$fk['REFERENCED_COLUMN_NAME']}`) via `{$fk['CONSTRAINT_NAME']}`\n";
}

echo "\n=== 2. INDEXES ON ORDERS & PAYMENTS ===\n";
$indexes = $pdo->query("
    SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'rechax_official'
      AND TABLE_NAME IN ('orders', 'payments')
    ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($indexes as $idx) {
    echo "  - `{$idx['TABLE_NAME']}` index `{$idx['INDEX_NAME']}` (Col: {$idx['COLUMN_NAME']}, Seq: {$idx['SEQ_IN_INDEX']}, NonUnique: {$idx['NON_UNIQUE']})\n";
}
