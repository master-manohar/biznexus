<?php
$files = [
    'includes/layout_start.php',
    'includes_functions.php',
    'dashboard/index.php',
    'superadmin.php',
    'analytics/index.php',
    'community/index.php',
    'meetings/list.php'
];

foreach ($files as $f) {
    echo "Checking $f: ";
    $output = [];
    $retval = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $output, $retval);
    echo implode("\n", $output) . "\n\n";
}
?>
