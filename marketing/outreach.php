<?php
/**
 * marketing/outreach.php
 * Final Outreach Marketing Agent
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Two levels up: db.php is in the domain root, not public_html
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../includes/email_config.php';
require_once __DIR__ . '/../ai_helper_v3.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    die("Unauthorized.");
}

$bcc_email = "manohar.nch@gmail.com";

try {
    $stmt = $pdo->prepare("SELECT * FROM marketing_prospects WHERE status = 'pending' LIMIT 20");
    $stmt->execute();
    $prospects = $stmt->fetchAll();

    if (empty($prospects)) {
        echo "NO_PENDING_PROSPECTS";
        exit;
    }

    foreach ($prospects as $p) {
        $email = $p['email'];
        $name = $p['name'] ?: 'Partner';
        $bizName = $p['business_name'] ?: 'Your Business';
        $category = $p['category'] ?: 'Small Business';
        $city = $p['city'] ?: 'India';

        $prompt = "Write a short, professional, and viral-style email opening for a business named '$bizName' in the '$category' sector in '$city'. Mention how BizNexus can help them scale using AI. Max 2 sentences.";
        $hook = runBizAIString($prompt);

        $subject = "Growth Opportunity for $bizName in $city";
        $body = "
            <div style='font-family:sans-serif; line-height:1.6; color:#333; max-width:600px; margin:auto; border:1px solid #eee; padding:20px; border-radius:10px;'>
                <h2 style='color:#4f46e5;'>BizNexus Growth Opportunity</h2>
                <p>Hello $name,</p>
                <p>$hook</p>
                <p>At **BizNexus**, we are dedicated to helping SMEs like yours thrive. We offer free AI-powered tools, automated networking, and high-converting landing pages to boost your presence.</p>
                <p>Join 5,000+ businesses today: <a href='https://biznexus.in/auth/register.php' style='color:#4f46e5; text-decoration:none; font-weight:bold;'>Claim Your Free AI Website →</a></p>
                <br>
                <hr style='border:none; border-top:1px solid #eee;'>
                <p style='font-size:0.8rem; color:#888;'>Best regards,<br>The BizNexus Growth Team<br>https://biznexus.in</p>
            </div>
        ";

        $sent = sendEmail($email, $subject, $body, null, $bcc_email);

        if ($sent) {
            $pdo->prepare("UPDATE marketing_prospects SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$p['id']]);
            echo "SENT: $email (BCC: $bcc_email)\n";
        } else {
            echo "FAIL: $email\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
