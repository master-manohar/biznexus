<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user_id'] = 7; // Manohar
$_SESSION['user_name'] = 'Manohar';
require_once 'bizfeed.php';
?>
