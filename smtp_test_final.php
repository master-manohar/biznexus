<?php
require_once 'includes/email_config.php';

$to = 'manohar.nch@gmail.com';
$subject = 'BizNexus SMTP Physical Check';
$content = "<h2>SMTP Reliability Test</h2><p>This is a manual verification check performed by Gemini to confirm your email system is active.</p><p>Time: " . date('Y-m-d H:i:s') . "</p>";
$html = emailTemplate($subject, $content);

echo "<h3>Starting Physical SMTP Check...</h3>";
if (sendEmail($to, $subject, $html)) {
    echo "<b style='color:green'>SUCCESS:</b> Email accepted for delivery to $to.";
} else {
    echo "<b style='color:red'>FAILED:</b> Could not send email. Check logs.";
}
?>
