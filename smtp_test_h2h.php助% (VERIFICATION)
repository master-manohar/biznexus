<?php
require_once __DIR__ . '/includes/email_config.php';

$to = 'hello@biznexus.in'; // Test recipient
$name = 'Test H2H Member';

echo "Sending H2H Welcome Email to $to...\n";
if (sendH2HWelcomeEmail($to, $name)) {
    echo "SUCCESS: Email sent. Please check hello@biznexus.in to verify contrast (White on Black).";
} else {
    echo "ERROR: Email failed to send. Check includes/smtp_error.log for details.";
}
?>
