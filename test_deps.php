<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing includes/auth_check.php...<br>";
include 'includes/auth_check.php';
echo "Auth Check OK!<br>";

echo "Testing includes/db.php...<br>";
include 'includes/db.php';
echo "DB Check OK!<br>";
?>
