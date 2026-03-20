<?php
// /includes/emails/referral.php
function sendReferralEmail($receiver_email, $receiver_name, $sender_name, $referred_name, $referred_business, $estimated_value) {
    if (!function_exists('sendEmail')) {
        require_once __DIR__ . '/../email_config.php';
    }

    $subject = "🤝 New Referral Received from $sender_name";
    
    $val_str = $estimated_value > 0 ? "₹" . number_format($estimated_value) : "Not specified";

    $content = "
        <h2>Hi $receiver_name,</h2>
        <p>Great news! You have received a new business referral from <strong>$sender_name</strong>.</p>
        
        <div style='background-color:#13131a; padding:15px; border-left:4px solid #FFD700; margin:20px 0; border-radius:4px;'>
            <p style='margin:0 0 10px 0; color:#FFD700; font-weight:bold;'>Referral Snapshot:</p>
            <p style='margin:0 0 5px 0;'><strong>Prospect Name:</strong> $referred_name</p>
            <p style='margin:0 0 5px 0;'><strong>Business Needed:</strong> $referred_business</p>
            <p style='margin:0 0 5px 0;'><strong>Estimated Value:</strong> $val_str</p>
        </div>
        
        <p style='margin-top:20px'>Log in to view their contact information and reach out!</p>
        <a href='https://biznexus.in/referrals/received.php' class='btn'>View Contacts</a>
    ";

    $html = emailTemplate("New Referral Received", $content);
    return sendEmail($receiver_email, $subject, $html);
}
?>
