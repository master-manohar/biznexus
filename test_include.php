<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function check_file($f) {
    echo "Testing $f...<br>";
    try {
        // We use a separate function to isolate the include scope
        include $f;
        echo " - [OK] $f loaded.<br>";
    } catch (Throwable $e) {
        echo " - [ERROR] in $f: " . $e->getMessage() . " at line " . $e->getLine() . "<br>";
    }
}

$files = [
    'includes/layout_start.php',
    'includes_functions.php',
    'dashboard/index.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        check_file($f);
    } else {
        echo "File $f missing!<br>";
    }
}
?>
