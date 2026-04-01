<?php
header('Content-Type: text/plain');
$dir = __DIR__ . '/../includes';
echo "Listing $dir:\n";
if (is_dir($dir)) {
    print_r(scandir($dir));
} else {
    echo "Directory NOT found!\n";
}
?>
