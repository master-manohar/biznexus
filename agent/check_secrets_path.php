<?php
$f = __DIR__ . '/includes/secrets.php';
echo "Checking: $f\n";
echo "Exists: " . (file_exists($f) ? "YES" : "NO") . "\n";
echo "Readable: " . (is_readable($f) ? "YES" : "NO") . "\n";
echo "Dir Contents of includes/:\n";
print_r(scandir(__DIR__ . '/includes'));
