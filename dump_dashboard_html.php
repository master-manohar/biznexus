<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user_id'] = 1; // Assuming user 1 exists
ob_start();
require_once 'dashboard/index.php';
$html = ob_get_clean();
echo "<pre>";
echo htmlspecialchars($html);
echo "</pre>";
?>
