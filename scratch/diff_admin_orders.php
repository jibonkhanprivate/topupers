<?php
$orig = file('backups/backup_phase_c4_20261013_144500/admin/orders.php');
$curr = file('admin/orders.php');

echo "Backup lines: " . count($orig) . "\n";
echo "Current lines: " . count($curr) . "\n";

// Let's print unified diff
$tmpO = tempnam(sys_get_temp_dir(), 'orig_');
$tmpC = tempnam(sys_get_temp_dir(), 'curr_');
file_put_contents($tmpO, implode('', $orig));
file_put_contents($tmpC, implode('', $curr));

// We don't have git, but let's see differences in chunks
for ($i = 0; $i < min(count($orig), count($curr)); $i++) {
    if (trim($orig[$i]) !== trim($curr[$i])) {
        echo "Diff starting at line " . ($i + 1) . ":\n";
        echo "ORIG: " . $orig[$i];
        echo "CURR: " . $curr[$i];
        // print next 5 lines
        for ($k = 1; $k <= 4; $k++) {
            echo "  + " . ($curr[$i+$k] ?? '');
        }
        $i += 10;
    }
}
