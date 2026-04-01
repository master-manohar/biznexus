<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Attempting to include admin/index.php...\n";
try {
    include __DIR__ . '/../admin/index.php';
} catch (Throwable $e) {
    echo "CAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
echo "\nInclude complete.";
