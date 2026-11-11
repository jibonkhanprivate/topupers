<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();

echo "======================================================================\n";
echo "1. MariaDB Version & Server Variables\n";
echo "======================================================================\n";
$version = $db->query("SELECT VERSION()")->fetchColumn();
echo "Version: " . $version . "\n";

$vars = $db->query("SHOW VARIABLES WHERE Variable_name IN ('innodb_version', 'innodb_file_per_table', 'innodb_default_row_format', 'character_set_server', 'collation_server', 'sql_mode')")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($vars as $k => $v) {
    echo "  $k = $v\n";
}

echo "\n======================================================================\n";
echo "2. Table Engine, Row Format & Collation for `orders`\n";
echo "======================================================================\n";
$stmt = $db->query("
    SELECT TABLE_NAME, ENGINE, ROW_FORMAT, TABLE_COLLATION, CREATE_OPTIONS 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
");
$tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
foreach ($tableInfo as $k => $v) {
    echo "  $k: " . var_export($v, true) . "\n";
}

echo "\n======================================================================\n";
echo "3. Columns `payment_trx` & `order_status` in `orders`\n";
echo "======================================================================\n";
$stmt = $db->query("
    SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME IN ('payment_trx', 'order_status')
");
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - {$col['COLUMN_NAME']}: {$col['COLUMN_TYPE']}, Null: {$col['IS_NULLABLE']}, Default: {$col['COLUMN_DEFAULT']}, Charset: {$col['CHARACTER_SET_NAME']}, Collation: {$col['COLLATION_NAME']}\n";
}

echo "\n======================================================================\n";
echo "4. Existing Indexes & Constraints on `orders`\n";
echo "======================================================================\n";
$stmt = $db->query("SHOW INDEXES FROM orders");
$indexes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $indexes[$row['Key_name']][] = $row['Column_name'];
}
foreach ($indexes as $keyName => $cols) {
    echo "  - Index '$keyName' on (" . implode(', ', $cols) . ")\n";
}

echo "\n======================================================================\n";
echo "5. Dry-Run Expression Evaluation (Read-Only SELECT)\n";
echo "======================================================================\n";
$testQuery = "
    SELECT 
        status_val, 
        trx_val, 
        (CASE 
            WHEN status_val = 'cancelled' THEN NULL
            WHEN trx_val IS NULL THEN NULL
            WHEN TRIM(trx_val) = '' THEN NULL
            ELSE UPPER(TRIM(trx_val))
        END) AS computed_trx
    FROM (
        SELECT 'pending' AS status_val, '  trx_abc_123  ' AS trx_val
        UNION ALL SELECT 'pending', 'TRX_ABC_123'
        UNION ALL SELECT 'payment_pending', 'trx_pending'
        UNION ALL SELECT 'paid', 'TRX_PAID'
        UNION ALL SELECT 'processing', 'trx_proc'
        UNION ALL SELECT 'completed', 'trx_done'
        UNION ALL SELECT 'failed', 'trx_failed'
        UNION ALL SELECT 'refunded', 'trx_refunded'
        UNION ALL SELECT 'on_hold', 'trx_hold'
        UNION ALL SELECT 'cancelled', 'trx_cancelled_1'
        UNION ALL SELECT 'cancelled', 'trx_cancelled_2'
        UNION ALL SELECT 'pending', NULL
        UNION ALL SELECT 'pending', ''
        UNION ALL SELECT 'pending', '   '
    ) test_cases
";
$stmt = $db->query($testQuery);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  Status: %-16s | Raw Trx: %-18s => Computed: %s\n", 
        $row['status_val'], 
        var_export($row['trx_val'], true), 
        var_export($row['computed_trx'], true)
    );
}
