<?php
// Secure log viewer for Gemini to check email delivery
$key = $_GET['key'] ?? '';
if ($key !== 'BizCron2024') { die('Unauthorized'); }

$log_file = 'reset_log.txt';
if (file_exists($log_file)) {
    echo "<h3>Recent Reset Logs (Root):</h3><pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre>";
} else {
    echo "No log found in root.";
}

$auth_log = 'auth/reset_log.txt';
if (file_exists($auth_log)) {
    echo "<h3>Recent Reset Logs (Auth folder):</h3><pre>" . htmlspecialchars(file_get_contents($auth_log)) . "</pre>";
}
?>
