<?php
$log = __DIR__ . '/includes/smtp_error.log';
if (file_exists($log)) {
    echo "=== SMTP ERROR LOG ===\n";
    echo file_get_contents($log);
} else {
    echo "SMTP error log not found at $log\n";
}
?>
