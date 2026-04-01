<?php
// /includes/emails/welcome_meet.php
function sendWelcomeMeetEmail($email, $name, $phone) {
    global $pdo; // Assume included
    
    // Check if sendEmail is available
    if (!function_exists('sendEmail')) {
        require_once __DIR__ . '/../email_config.php';
    }

    $content = "
        <h2>Welcome to BizNexus, $name! 🎉</h2>
        <p>Your registration for the <strong>Free Business Meet</strong> is confirmed. We are excited to connect with you!</p>
        
        <p>As a special bonus, we have instantly credited your account with the <strong>Silver Package</strong> and <strong>100 VooCoins</strong> completely FREE!</p>
        
        <div style='background:rgba(255,215,0,0.1); border-left:4px solid #FFD700; padding:15px; margin:20px 0;'>
            <h4 style='margin-top:0; color:#b8860b;'>Your BizNexus Login Details</h4>
            <ul style='list-style:none; padding-left:0; margin-bottom:0;'>
                <li><strong>Email:</strong> $email</li>
                <li><strong>Password:</strong> $phone</li>
            </ul>
        </div>
        
        <p style='color:#555; font-size:0.9em;'><em>Note: We used your mobile number as your temporary password. We highly recommend changing it once you log in.</em></p>
        
        <div style='background:#f9f9f9; border:1px solid #ddd; border-radius:8px; padding:20px; margin:25px 0; text-align:center;'>
            <h3 style='color:#333; margin-top:0;'>Want Maximum Exposure at the Event? 🚀</h3>
            <p style='color:#555; font-size:0.95em;'>We are expecting hundreds of high-value business owners. Position your brand front and center:</p>
            <table width='100%' cellpadding='10' cellspacing='0' style='margin-top:15px; border-collapse:collapse;'>
                <tr>
                    <td width='50%' style='background:#fff; border:1px solid #eee; border-radius:6px; text-align:center;'>
                        <h4 style='margin:0 0 5px 0; color:#4488ff;'>🏬 Book a Stall</h4>
                        <p style='margin:0; font-size:0.85em; color:#666;'>Showcase your products/services directly to ready buyers.</p>
                    </td>
                    <td width='50%' style='background:#fff; border:1px solid #FFD700; border-radius:6px; text-align:center;'>
                        <h4 style='margin:0 0 5px 0; color:#b8860b;'>👑 Gold Sponsor</h4>
                        <p style='margin:0; font-size:0.85em; color:#666;'>Massive branding, on-stage mention, and premium placement.</p>
                    </td>
                </tr>
            </table>
            <p style='margin-top:15px; font-weight:bold; font-size:0.9em;'>Reply directly to this email or ask in the WhatsApp group for pricing details!</p>
        </div>
        
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Please ensure you've joined the <a href='https://chat.whatsapp.com/JalVsmbMNNxALFP3FLKBk6' style='color:#25D366; font-weight:bold;'>WhatsApp Group</a> for event location and dates.</li>
            <li>Log into your dashboard to complete your business profile.</li>
        </ul>
        
        <a href='https://biznexus.in/auth/login.php' class='btn' style='background:#FFD700; color:#000; padding:12px 24px; text-decoration:none; font-weight:bold; border-radius:6px; display:inline-block; margin-top:15px;'>Log In to BizNexus</a>
    ";

    $html = emailTemplate("Meeting Registration Confirmed", $content);
    return sendEmail($email, "Your BizNexus Account + Event Confirmation ⚡", $html);
}
?>
