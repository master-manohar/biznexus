<?php
// Debug wrapper for registration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start capture
ob_start();

try {
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST["name"] = "PHP Error Tester";
    $_POST["email"] = "debug_".time()."@biznexus.in";
    $_POST["phone"] = "9876543213";
    $_POST["password"] = "Demo@2024";
    $_POST["confirm_password"] = "Demo@2024";
    
    // Attempt the exact logic from register.php
    require_once "auth/register.php";
    
} catch (Throwable $e) {
    echo "<h1>CRITICAL ERROR CAUGHT</h1>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Dump whatever errors auth/register.php threw before exiting
$output = ob_get_clean();
echo $output;
?>
