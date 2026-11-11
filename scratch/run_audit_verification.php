<?php
declare(strict_types=1);

$tests = [
    'test_b2_order_trx_conflict.php',
    'test_b2_write_freeze.php',
    'test_b2_gate2b_mariadb_rehearsal.php',
    'test_mariadb_10_4_isolated.php',
    'test_mariadb_dup_exception.php',
    'test_standalone_migration_offline.php',
    'test_step4h_migration_safety.php',
    'test_c6_4_rollback_mock.php',
    'test_c6_4_rollback_disposable_mariadb.php',
    'test_c6_5_live_shaped_rehearsal.php',
    'test_all_pages.php',
    'test_all_admin_pages.php',
    'http_test.php'
];

echo str_repeat("=", 80) . "\n";
echo "GATE B2 INDEPENDENT AUDIT — RERUNNING ALL 13 TEST SUITES\n";
echo str_repeat("=", 80) . "\n\n";

$summary = [];
$totalFormalAssertions = 0;
$totalReachabilityChecks = 0;

foreach ($tests as $t) {
    $path = __DIR__ . '/' . $t;
    if (!file_exists($path)) {
        echo "MISSING: $t\n";
        continue;
    }
    $cmd = '"C:\\xampp\\php\\php.exe" "' . $path . '" 2>&1';
    $t0 = microtime(true);
    exec($cmd, $outputLines, $exitCode);
    $elapsed = microtime(true) - $t0;
    $fullOutput = implode("\n", $outputLines);

    // Count pattern matches as done in previous regex
    $passMatches = preg_match_all('/(\[PASS\]|\[✓\]|\[200 OK\]|PASS:|RESULT: PASS|100% SUCCESS)/', $fullOutput);
    $failMatches = preg_match_all('/(\[FAIL\]|\[X\]|\[FAILED\]|Fatal error|Uncaught Exception|PDOException)/i', $fullOutput);

    $summary[] = [
        'file'     => $t,
        'exitCode' => $exitCode,
        'elapsed'  => round($elapsed, 2),
        'passes'   => $passMatches,
        'fails'    => $failMatches,
        'output'   => $fullOutput
    ];

    printf("%-42s | Exit: %d | Time: %5.2fs | Matches: %3d | Fails: %d\n",
        $t, $exitCode, $elapsed, $passMatches, $failMatches
    );
    unset($outputLines);
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "EXECUTION COMPLETE\n";
echo str_repeat("=", 80) . "\n";
