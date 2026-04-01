<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log to screen
echo "DEBUG: Starting find.php simulation...\n";

try {
    require_once 'includes/db.php';
    echo "DEBUG: db.php loaded.\n";
    
    // Test if includes/functions.php exists
    if (!file_exists('includes/functions.php')) {
        echo "WARNING: includes/functions.php NOT FOUND.\n";
    } else {
        require_once 'includes/functions.php';
        echo "DEBUG: includes/functions.php loaded.\n";
    }

    if (file_exists('includes_functions.php')) {
        require_once 'includes_functions.php';
        echo "DEBUG: includes_functions.php loaded.\n";
    }

} catch (Throwable $e) {
    echo "FATAL ERROR caught: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
echo "DEBUG: Checks finished.\n";
