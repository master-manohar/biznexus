<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Superadmin Debug</h1>";

try {
    echo "Checking includes...<br>";
    if (!file_exists('includes/db.php')) die("ERROR: includes/db.php missing");
    if (!file_exists('includes/auth_check.php')) die("ERROR: includes/auth_check.php missing");
    if (!file_exists('includes_functions.php')) die("ERROR: includes_functions.php missing");
    
    echo "Files exist. Testing DB connection...<br>";
    require_once 'includes/db.php';
    echo "DB Connected.<br>";

    echo "Running superadmin.php via include to catch errors...<br>";
    // Mock session for debug
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = 7; // Manohar ID
    
    include 'superadmin.php';
    
} catch (Error $e) {
    echo "<div style='color:red; font-family:monospace; white-space:pre;'>";
    echo "PHP ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color:orange; font-family:monospace; white-space:pre;'>";
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "</div>";
}
?>
