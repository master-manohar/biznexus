<?php
/**
 * agent/system_pulse.php
 * This script is the "heartbeat" of the BizNexus AI ecosystem.
 * It manages both Social Media and SEO automation.
 */
require_once __DIR__ . '/../includes/db.php';

// Security Key
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized Pulse");
}

header('Content-Type: text/plain');
echo "==== BIZNEXUS SYSTEM PULSE ====\n";

function triggerAgent($script, $params = "") {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $url = $protocol . "://" . $host . "/agent/" . $script . "?key=BizCron2024" . $params;
    
    echo "Triggering: $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
    $output = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) echo "Error: $err\n";
    else echo "Response preview: " . substr($output, 0, 200) . "...\n";
}

// 1. Social Pulse (Every 2 Hours)
echo "\n[SOCIAL CHECK]\n";
$stmt = $pdo->query("SELECT created_at FROM social_posts ORDER BY id DESC LIMIT 1");
$last_social = $stmt->fetchColumn();
if (!$last_social || (time() - strtotime($last_social)) / 3600 >= 2) {
    echo "Action: Triggering Social Media Agent.\n";
    triggerAgent('social_media_agent.php');
} else {
    echo "Action: Social matches schedule (Last run < 2h ago).\n";
}

// 2. SEO Pulse (Every 24 Hours - generating 25 pages per run)
echo "\n[SEO CHECK]\n";
$stmt = $pdo->query("SELECT MAX(last_generated) FROM seo_pages");
$last_seo = $stmt->fetchColumn();
if (!$last_seo || (time() - strtotime($last_seo)) / 3600 >= 24) {
    echo "Action: Triggering SEO Power Agent.\n";
    triggerAgent('seo_power_agent.php');
} else {
    echo "Action: SEO matches schedule (Last run < 24h ago).\n";
}

echo "\nPulse Cycle Complete.\n";
