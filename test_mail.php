<?php
require_once "includes/email_config.php";

$to = "manohar.nch@gmail.com";
$subject = "BizNexus Email Test";
$content = "<h2>This is a test email</h2><p>If you receive this, the email system is working correctly.</p>";
$message = emailTemplate("Email System Test", $content);

echo "Attempting to send email to $to...\n";
if (sendEmail($to, $subject, $message)) {
    echo "SUCCESS: Email sent successfully.\n";
} else {
    echo "FAILURE: Email sending failed. Check error logs.\n";
}
?>
