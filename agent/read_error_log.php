<?php
// agent/read_error_log.php
header('Content-Type: text/plain');

$log_files = ['../error_log', '../../error_log', 'error_log', '../php_error.log'];
foreach ($log_files as $f) {
    if (file_exists($f)) {
        echo "--- LOG: $f ---\n";
        $lines = file($f);
        echo implode("", array_slice($lines, -30));
        echo "\n\n";
    }
}

if (empty($log_files)) echo "No log files found.";
