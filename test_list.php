<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user_id'] = 1;
try {
    require_once 'list.php';
    echo "<h2>List Success!</h2>";
} catch (Throwable $e) {
    echo "<h2>List ERROR:</h2>";
    echo $e->getMessage();
}
?>
