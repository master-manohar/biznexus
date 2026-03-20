<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user_id'] = 1; // Mock logged-in user

// Include the dashboard logic but with session mocked
require_once 'dashboard/index.php';
?>
