<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1; // Demo user

echo "<h1>Dashboard Error Diagnostic</h1>";
try {
    require_once 'dashboard/index.php';
    echo "<h2>Success!</h2>";
} catch (Throwable $e) {
    echo "<h2>FATAL ERROR:</h2>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack Trace:<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
