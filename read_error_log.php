<?php
header('Content-Type: text/plain');
$files = ['error_log', 'php_error.log', '../error_log', 'admin/error_log'];
foreach ($files as $f) {
    if (file_exists($f)) {
        echo "--- $f ---\n";
        echo shell_exec("tail -n 20 " . escapeshellarg($f));
        echo "\n";
    }
}
?>
