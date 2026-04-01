<?php
/**
 * agent/networking_agent.php
 * Handles automated weekly meetings, AI business tips, and meeting notifications.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';
require_once __DIR__ . '/../includes_functions.php';

// Security Key (Required for Pulse Trigger)
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized Agent Run");
}

echo "--- BIZNEXUS NETWORKING AGENT Pulse ---\n";

/**
 * 1. AUTOMATED WEEKLY MEETING GENERATION
 */
$dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
$isDemo = isset($_GET['demo']);

if ($dayOfWeek == 1 || $isDemo) { // Monday or Demo Mode
    $currentWeek = date('W');
    $currentYear = date('Y');
    
    // In demo mode, use a unique identifier to avoid duplicate skipping
    $checkKey = $isDemo ? 'DEMO_WEEK_13' : ($currentYear . $currentWeek);
    
    // Check if it exists (for demo, we can just check for specific title)
    $check = $pdo->prepare("SELECT id FROM networking_meetings WHERE title LIKE ? AND scheduled_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $searchTerm = $isDemo ? '%DEMO%' : "BizNexus Weekly Networking Masterclass - Week $currentWeek%";
    $check->execute([$searchTerm]);
    
    if (!$check->fetch()) {
        echo "Generating networking meeting...\n";
        
        // Generate AI Business Tip
        $prompt = "Generate a concise, powerful business tip for entrepreneurs in India for " . date('d M Y') . ". Max 3 sentences.";
        $tip = runBizAIString($prompt, "You are a senior business consultant for BizNexus.");
        
        // Schedule for tomorrow if Demo, else 8:30 AM today/tomorrow
        $scheduled_time = $isDemo ? date('Y-m-d H:i:s', strtotime('+1 day')) : (date('Y-m-d') . " 08:30:00");
        $title = $isDemo ? "🔥 Demo Networking Masterclass" : "BizNexus Weekly Networking Masterclass - Week $currentWeek";
        
        $ins = $pdo->prepare("INSERT INTO networking_meetings (host_id, meeting_type, title, description, meeting_link, ai_business_tip, scheduled_at, status) 
                             VALUES (?, 'weekly_master', ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([
            1, 
            $title,
            "Join our networking session to connect and share insights.",
            "https://meet.google.com/lookup/biznexus-weekly",
            $tip,
            $scheduled_time
        ]);
        
        echo "✅ Meeting created for $scheduled_time.\n";
    } else {
        echo "Meeting already exists.\n";
    }
}

/**
 * 2. NOTIFICATION ENGINE
 */
$now = date('Y-m-d H:i:s');
$oneHourLater = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $pdo->prepare("SELECT * FROM networking_meetings WHERE status = 'pending' AND scheduled_at BETWEEN ? AND ?");
$stmt->execute([$now, $oneHourLater]);
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($upcoming as $meeting) {
    echo "Processing notifications for meeting: " . $meeting['title'] . "\n";
}

/**
 * 3. ATTENDANCE & STATUS RECONCILIATION
 */
$pastMeetings = $pdo->query("SELECT id FROM networking_meetings WHERE status IN ('pending', 'ongoing') AND scheduled_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)")->fetchAll(PDO::FETCH_ASSOC);

foreach ($pastMeetings as $pm) {
    echo "Reconciling attendance for meeting ID: " . $pm['id'] . "\n";
    $pdo->prepare("UPDATE networking_meetings SET status = 'completed' WHERE id = ?")->execute([$pm['id']]);
}

echo "--- BIZNEXUS NETWORKING AGENT Pulse Finished ---\n";
