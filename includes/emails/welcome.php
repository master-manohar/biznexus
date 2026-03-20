<?php
// /includes/emails/welcome.php
function sendWelcomeEmail($email, $name) {
    global $pdo; // Assume included
    
    // We already require email_config if this function is called, but check just in case
    if (!function_exists('sendEmail')) {
        require_once __DIR__ . '/../email_config.php';
    }

    $content = "
        <h2>Welcome to BizNexus, $name! ⚡</h2>
        <p>You have successfully joined India's premier AI-powered B2B business network.</p>
        <p>As a welcome gift, we have credited your account with <strong>100 VooCoins</strong>.</p>
        
        <p style='margin-top:20px'><strong>Next Steps for Success:</strong></p>
        <ul>
            <li>Complete your Business Profile to earn an extra 50 VooCoins.</li>
            <li>Fill out your services and precise location so our AI matching engine can send leads directly to your inbox.</li>
            <li>Explore our marketplace or schedule priority meetings with other members!</li>
        </ul>
        
        <a href='https://biznexus.in/auth/login.php' class='btn'>Go to Dashboard</a>
    ";

    $html = emailTemplate("Welcome to BizNexus", $content);
    return sendEmail($email, "Welcome to BizNexus ⚡", $html);
}
?>
