<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock session to bypass redirect in auth_check.php
session_start();
$_SESSION['user_id'] = 1; // Any ID will do for parse/execution check

// Mock the role check within superadmin.php
// Note: superadmin.php re-fetches the role from DB, so we need a real ID if possible.
// Or we can mock the $pdo to return 'admin'. But let's try a simple include first.

echo "<h1>Debug Admin Mode - Capturing 500 Error</h1>";

try {
    // We might need to redefine some constants or include required files manually
    // if superadmin.php assumes certain environment.
    
    require_once 'superadmin.php';
    
} catch (Throwable $e) {
    echo "<h2>Caught Exception:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

// Catch fatal errors that aren't Throwables (like parse errors in included files)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h2>Caught Fatal Error:</h2>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    }
});
?>
