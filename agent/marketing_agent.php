<?php
// /agent/marketing_agent.php
// Expected to be run via Hostinger Daily Cron Job
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/email_config.php';

$logFile = __DIR__ . '/agent_logs/marketing_log.txt';
if (!is_dir(__DIR__ . '/agent_logs')) mkdir(__DIR__ . '/agent_logs', 0777, true);

$BCC_ADMIN = 'manohar.nch@gmail.com';

function writeLog($msg) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [Marketing Agent] $msg\n", FILE_APPEND);
    echo "[$date] $msg<br>\n";
}

writeLog("Starting daily Marketing & Re-engagement Campaign.");

try {
    // 1. Find up to 5 active members (simulated selection)
    $stmt = $pdo->query("SELECT id, name, email, business_name, category, city FROM users WHERE status='active' AND email NOT LIKE '%@example.com' ORDER BY RAND() LIMIT 5");
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($targets) === 0) {
        writeLog("No active target members found. Exiting.");
        exit;
    }

    require_once dirname(__DIR__) . '/includes/ai_helper_v3.php';

    foreach ($targets as $user) {
        // Find 3 most recent leads in their category
        $leadStmt = $pdo->prepare("SELECT query, city FROM public_leads WHERE category = ? ORDER BY id DESC LIMIT 3");
        $leadStmt->execute([$user['category'] ?: 'General']);
        $leads = $leadStmt->fetchAll();

        // AI Personalized Subject & Opening
        $aiPrompt = "Generate a catchy email subject and a 1-sentence personalized opening for a business owner in the '{$user['category']}' industry. 
Business: {$user['business_name']}
Owner: {$user['name']}
Context: We have " . count($leads) . " new leads in their category today.";
        
        $aiRes = runBizAIString($aiPrompt, "You are a professional growth hacker for a B2B platform.");
        
        $subject = "New {$user['category']} Leads found for {$user['business_name']}! 🚀";
        if (preg_match('/Subject:(.*?)(?:Opening:|$)/is', (string)$aiRes, $m)) $subject = trim($m[1]);
        
        $opening = "Great news! We've identified new potential business matches for you on BizNexus.";
        if (preg_match('/Opening:(.*?)$/is', (string)$aiRes, $m)) $opening = trim($m[1]);

        $content = "
            <h2>{$opening}</h2>
            <p>Your business profile <strong>{$user['business_name']}</strong> is ranking high for <strong>{$user['category']}</strong> searches in <strong>{$user['city']}</strong>.</p>
            <p>Here are the latest requirements from our network:</p>
            <div style='background:#f9f9f9; padding:15px; border-radius:10px; border-left:4px solid #FFD700;'>
        ";

        if (count($leads) > 0) {
            $content .= "<ul style='padding-left:20px; color:#444;'>";
            foreach($leads as $l) {
                $loc = $l['city'] ?: 'India';
                $content .= "<li style='margin-bottom:8px;'><strong>Requirement:</strong> {$l['query']} (<span style='color:#777;'>Located in $loc</span>)</li>";
            }
            $content .= "</ul>";
        } else {
            $content .= "<p style='color:#666;'><em>We are currently seeing a surge in demand for {$user['category']} services. Update your profile to stay at the top of our matching engine!</em></p>";
        }

        $content .= "
            </div>
            <p style='margin-top:20px;'>Don't let these opportunities go to your competitors. Log in to your CRM pipeline to unlock these contacts instantly.</p>
            <div style='text-align:center; margin:30px 0;'>
                <a href='https://biznexus.in/auth/login.php' style='background:#FFD700; color:#000; padding:15px 30px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>Unlock Leads & Reply Now</a>
            </div>
            <p style='font-size:12px; color:#888; border-top:1px solid #eee; pt:15px;'>Powered by BizNexus AI Growth Engine. This is an automated marketing intelligence report.</p>
        ";

        $html = emailTemplate($subject, $content);
        $success = sendEmail($user['email'], $subject, $html, $BCC_ADMIN);
        
        if ($success) {
            writeLog("Sent category marketing Alert to {$user['email']} (Category: {$user['category']})");
        } else {
            writeLog("Failed to send to {$user['email']}");
        }
        
        sleep(2);
    }

    writeLog("Marketing Campaign complete.");

} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}
?>
