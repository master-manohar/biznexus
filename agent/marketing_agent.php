<?php
// /agent/marketing_agent.php
// Expected to be run via Hostinger Daily Cron Job
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/email_config.php';

$logFile = __DIR__ . '/agent_logs/marketing_log.txt';
if (!is_dir(__DIR__ . '/agent_logs')) mkdir(__DIR__ . '/agent_logs', 0777, true);

function writeLog($msg) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [Marketing Agent] $msg\n", FILE_APPEND);
    echo "[$date] $msg<br>\n";
}

writeLog("Starting daily Marketing & Re-engagement Campaign.");

try {
    // 1. Find 5 members who haven't logged in recently (simulated by finding random old accounts)
    // In a real scenario, we would check a `last_login` column, but since we just seeded DB, we'll pick 3 random accounts.
    $stmt = $pdo->query("SELECT id, name, email, business_name, category, city FROM users ORDER BY RAND() LIMIT 3");
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($targets) === 0) {
        writeLog("No active target members found. Exiting.");
        exit;
    }

    foreach ($targets as $user) {
        // Find 2 random leads in their category
        $leadStmt = $pdo->prepare("SELECT query, city FROM public_leads WHERE category = ? ORDER BY id DESC LIMIT 2");
        $leadStmt->execute([$user['category']]);
        $leads = $leadStmt->fetchAll();

        $content = "
            <h2>Hello {$user['name']}, you have unattended leads!</h2>
            <p>Your business profile <strong>{$user['business_name']}</strong> is verified on BizNexus, but we noticed you haven't checked your CRM pipeline today.</p>
            <p>Here are recent live searches in your industry:</p>
            <ul>
        ";

        if (count($leads) > 0) {
            foreach($leads as $l) {
                $loc = $l['city'] ?: 'Any India';
                $content .= "<li><strong>Requirement:</strong> {$l['query']} (<span style='color:#FFD700;'>$loc</span>)</li>";
            }
        } else {
            $content .= "<li><strong>General Network Matching:</strong> We have 4 new buyers in the {$user['category']} sector active today!</li>";
        }

        $content .= "
            </ul>
            <p>Don't miss out on securing these deals. Click below to spend your VooCoins and access these verified buyers instantly.</p>
            <a href='https://biznexus.in/auth/login.php' class='btn'>Unlock Leads Now</a>
            <p style='margin-top:20px; font-size:11px;'>This is an automated AI re-engagement ping from BizNexus Agent 03.</p>
        ";

        $html = emailTemplate("Unattended Leads Wait For You 💸", $content);
        
        $success = sendEmail($user['email'], "Unattended {$user['category']} Leads Wait For You 💸", $html);
        
        if ($success) {
            writeLog("Successfully dispatched Re-engagement Newsletter to {$user['email']}");
        } else {
            writeLog("Failed to send to {$user['email']}");
        }
        
        // Prevent spamming Hostinger SMTP limits
        sleep(2);
    }

    writeLog("Marketing Campaign complete.");

} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}
?>
