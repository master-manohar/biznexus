<?php
echo "Current Dir: " . __DIR__ . "\n";
echo "Files in root:\n";
print_r(scandir(__DIR__));
echo "\nFiles in includes:\n";
if (is_dir('includes')) {
    print_r(scandir('includes'));
} else {
    echo "No includes dir found!\n";
}
?>
