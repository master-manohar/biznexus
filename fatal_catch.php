<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        echo "<h1>Fatal Error Caught:</h1><pre>";
        print_r($error);
        echo "</pre>";
    }
});

// Mock session to bypass auth_check
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 is an admin for test

echo "<h1>Starting superadmin.php load...</h1>";
require_once 'superadmin.php';
echo "<h1>superadmin.php loaded successfully!</h1>";
?>
