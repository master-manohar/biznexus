<?php
/**
 * agent/outreach_marketing_agent.php
 * Final Outreach Marketing Agent (Clean & Verified & Logging)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';

// Handle Task ID from Runner
$taskId = $_GET['task_id'] ?? $argv[1] ?? null;

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    die("Unauthorized.");
}

$agent_name = "Outbound Marketing";
$bcc_email = "manohar.nch@gmail.com";

function logAgent($pdo, $taskId, $name, $action, $detail) {
    $stmt = $pdo->prepare("INSERT INTO agent_logs (task_id, agent_name, action, detail) VALUES (?, ?, ?, ?)");
    $stmt->execute([$taskId, $name, $action, $detail]);
}

try {
    if ($taskId) {
        $pdo->prepare("UPDATE agent_tasks SET status = 'running', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
        logAgent($pdo, $taskId, $agent_name, "start", "Agent started processing batch.");
    }

    $stmt = $pdo->prepare("SELECT * FROM marketing_prospects WHERE status = 'pending' LIMIT 20");
    $stmt->execute();
    $prospects = $stmt->fetchAll();

    if (empty($prospects)) {
        echo "NO_PENDING_PROSPECTS";
        if ($taskId) {
            $pdo->prepare("UPDATE agent_tasks SET status = 'completed', result = 'No prospects found', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
            logAgent($pdo, $taskId, $agent_name, "idle", "No pending prospects found.");
        }
        exit;
    }

    $count = 0;
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
                <p>Hello $name from <strong>$bizName</strong>,</p>
                <p>$hook</p>
                <p>At **BizNexus**, we are dedicated to helping SMEs like yours thrive. We offer free AI-powered tools, automated networking, and high-converting landing pages to boost your presence.</p>
                <p>Join 5,000+ businesses today: <a href='https://biznexus.in/auth/register.php' style='color:#4f46e5; text-decoration:none; font-weight:bold;'>Claim Your Free AI Website →</a></p>
                <br>
                <hr style='border:none; border-top:1px solid #eee;'>
                <p style='font-size:0.8rem; color:#888;'>Best regards,<br>The BizNexus Growth Team<br>https://biznexus.in</p>
                <div style='font-size:0.7rem; color:#bbb; border-top:1px solid #f0f0f0; padding-top:10px; margin-top:20px;'>
                    <em>Sent to: $email ($bizName) | BizNexus SME Outreach</em>
                </div>
            </div>
        ";

        $sent = sendEmail($email, $subject, $body, $bcc_email);

        if ($sent) {
            $pdo->prepare("UPDATE marketing_prospects SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$p['id']]);
            logAgent($pdo, $taskId, $agent_name, "outreach", "Sent personalized email to $email ($bizName)");
            echo "SENT: $email\n";
            $count++;
        } else {
            logAgent($pdo, $taskId, $agent_name, "error", "Failed to send email to $email");
            echo "FAIL: $email\n";
        }
    }

    if ($taskId) {
        $pdo->prepare("UPDATE agent_tasks SET status = 'completed', result = 'Sent $count emails', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
        logAgent($pdo, $taskId, $agent_name, "finish", "Batch complete. Total emails sent: $count");
    }
    echo "SUCCESS: Processed $count prospects.";

} catch (Exception $e) {
    if ($taskId) {
        $pdo->prepare("UPDATE agent_tasks SET status = 'failed', result = ?, updated_at = NOW() WHERE id = ?")->execute([$e->getMessage(), $taskId]);
        logAgent($pdo, $taskId, $agent_name, "fatal", $e->getMessage());
    }
    echo "ERROR: " . $e->getMessage();
}
?>
