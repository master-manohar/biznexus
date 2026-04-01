<?php
echo "ROOT DIR CONTENTS:\n";
print_r(scandir(__DIR__));
echo "\nChecking 'includes':\n";
if (is_dir('includes')) {
    echo "Found 'includes' directory.\n";
    print_r(scandir('includes'));
} else {
    echo "'includes' NOT FOUND as directory.\n";
}
