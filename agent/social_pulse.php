<?php
/**
 * agent/social_pulse.php
 * This script ensures the social media agent runs every 2 hours.
 * It can be triggered via Cron OR via a hidden fetch on the Admin dashboard.
 */
require_once __DIR__ . '/../includes/db.php';

// Security Key
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized Pulse");
}

header('Content-Type: text/plain');
echo "Checking Social Pulse...\n";

// 1. Check the last attempt from social_posts table
$stmt = $pdo->query("SELECT created_at FROM social_posts ORDER BY id DESC LIMIT 1");
$last_attempt = $stmt->fetchColumn();

$should_run = false;
if (!$last_attempt) {
    $should_run = true;
    echo "No previous posts found. Initializing first run.\n";
} else {
    $last_time = strtotime($last_attempt);
    $diff_hours = (time() - $last_time) / 3600;
    
    if ($diff_hours >= 2) {
        $should_run = true;
        echo "Last attempt was " . round($diff_hours, 1) . " hours ago. Triggering agent.\n";
    } else {
        echo "Last attempt was only " . round($diff_hours, 1) . " hours ago. Sleeping.\n";
    }
}

// 2. Trigger the agent if needed
if ($should_run) {
    // We execute it via an HTTP sub-request to ensure it runs in the proper web context
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $url = $protocol . "://" . $host . "/agent/social_media_agent.php?key=BizCron2024";
    
    echo "Triggering: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Allow time for AI generation & Reels processing
    $output = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        echo "Trigger Error: $err\n";
    } else {
        echo "Agent Response:\n$output\n";
    }
}
