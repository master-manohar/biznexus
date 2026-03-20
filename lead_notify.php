<?php
// /includes/emails/lead_notify.php
function sendLeadEmail($member_email, $member_name, $lead_category, $lead_city, $lead_query, $lead_name, $lead_phone) {
    if (!function_exists('sendEmail')) {
        require_once __DIR__ . '/../email_config.php';
    }

    $subject = "⚡ Hot Lead: New $lead_category requirement in $lead_city";
    
    $content = "
        <h2>Hi $member_name,</h2>
        <p>A new lead matching your business profile has been generated on BizNexus.</p>
        
        <div style='background-color:#13131a; padding:15px; border-left:4px solid #FFD700; margin:20px 0; border-radius:4px;'>
            <p style='margin:0 0 10px 0; color:#FFD700; font-weight:bold;'>Lead Details:</p>
            <p style='margin:0 0 5px 0;'><strong>Looking For:</strong> $lead_category</p>
            <p style='margin:0 0 5px 0;'><strong>City:</strong> $lead_city</p>
            <p style='margin:0 0 5px 0;'><strong>Specific Query:</strong> \"$lead_query\"</p>
        </div>
        
        <div style='background-color:#1e1e30; padding:15px; border-radius:4px;'>
            <p style='margin:0 0 10px 0; color:#e8e8f0; font-weight:bold;'>Contact Information:</p>
            <p style='margin:0 0 5px 0;'><strong>Name:</strong> Hidden (Unlock via Dashboard)</p>
            <p style='margin:0 0 5px 0;'><strong>Phone/WhatsApp:</strong> +91 XXXXX XXXXX</p>
            <p style='margin-top:10px; font-size:12px; color:#ff4455;'><em>Protected by 3-Claim Max Lock.</em></p>
        </div>
        
        <p style='margin-top:20px'>Make sure to claim this lead quickly before 3 other members lock it!</p>
        <a href='https://biznexus.in/dashboard/leads.php' class='btn'>Claim Lead & Reveal Details</a>
    ";

    $html = emailTemplate("New Lead on BizNexus", $content);
    return sendEmail($member_email, $subject, $html);
}
?>
