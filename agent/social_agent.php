<?php
// /agent/social_agent.php
// Expected to be run via Hostinger Daily Cron Job
require_once dirname(__DIR__) . '/includes/db.php';

$logFile = __DIR__ . '/agent_logs/social_log.txt';
if (!is_dir(__DIR__ . '/agent_logs')) mkdir(__DIR__ . '/agent_logs', 0777, true);

function writeLog($msg) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [Social Agent] $msg\n", FILE_APPEND);
    echo "[$date] $msg<br>\n";
}

$secrets = require_once dirname(__DIR__) . '/includes/secrets.php';
$claudeApiKey = $secrets['anthropic_api_key'];

writeLog("Starting daily Social Media Content Generation.");

try {
    // 1. Fetch the 2 newest members to feature on Social Media today
    $stmt = $pdo->query("SELECT business_name, category, city FROM users ORDER BY id DESC LIMIT 2");
    $newMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($newMembers) === 0) {
        writeLog("No members found. Exiting.");
        exit;
    }

    $socialFeedPath = dirname(__DIR__) . '/agent/social_feed.html';
    $htmlOutput = "<h2>Today's Generated Social Posts (" . date('M d, Y') . ")</h2><hr>";

    foreach ($newMembers as $member) {
        $bName = $member['business_name'];
        $cat = $member['category'];
        $city = $member['city'];

        writeLog("Engaging Gemini AI for $bName ($cat in $city)...");
        
        require_once dirname(__DIR__) . '/includes/ai_helper_v3.php';
        
        $prompt = "Write an engaging, exciting Instagram/LinkedIn post celebrating a new business joining the BizNexus AI B2B network. Business Name: '$bName'. Industry: '$cat'. Location: '$city'. Include 5 relevant hashtags at the end. Keep it under 60 words.";
        $sys = "You are an expert Social Media Manager for a B2B network. Output only the post text and hashtags.";
        
        $result = runBizAI([['role' => 'user', 'content' => $prompt]], $sys);
        
        if (isset($result['text'])) {
            $postContent = nl2br(htmlspecialchars(trim($result['text'])));
            $htmlOutput .= "<h3>Feature: $bName</h3><div style='background:#f4f4f4;padding:15px;border-radius:8px;border:1px solid #ccc;color:#000;font-family:sans-serif;'>$postContent</div><br>";
            writeLog("Successfully generated post for $bName");
        } else {
            writeLog("Failed to generate post for $bName. Check API.");
        }
    }

    // Save the feed for Admin to easily copy-paste
    file_put_contents($socialFeedPath, $htmlOutput);
    writeLog("Saved social feed generator to /agent/social_feed.html");

} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}
?>
