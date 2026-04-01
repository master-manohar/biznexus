<?php
/**
 * agent/biznexus_agent.php
 * The Master Agent orchestrator for BizNexus.
 * Monitors all users and triggers personalized Sub-Agent logic.
 * Cron: Run every 6 hours (or more frequently for high engagement).
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600); // 10 mins for batch processing

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';
require_once __DIR__ . '/../includes/agent_templates.php';
require_once __DIR__ . '/../includes/email_config.php';
require_once __DIR__ . '/../includes/email_config.php';

if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized.");
}

echo "=== BizNexus Master Agent v1.0 ===\n";

$batch_size = 50;
$test_uid = isset($_GET['test_uid']) ? (int)$_GET['test_uid'] : 0;

if ($test_uid) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.category, u.created_at, u.onboarding_complete, u.plan,
               uas.current_stage, uas.last_interaction_at, uas.next_followup_at
        FROM users u
        LEFT JOIN user_agent_state uas ON u.id = uas.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$test_uid]);
} else {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.category, u.created_at, u.onboarding_complete, u.plan,
               uas.current_stage, uas.last_interaction_at, uas.next_followup_at
        FROM users u
        LEFT JOIN user_agent_state uas ON u.id = uas.user_id
        WHERE u.status = 'active'
        ORDER BY u.id ASC
        LIMIT ?
    ");
    $stmt->execute([$batch_size]);
}
$users = $stmt->fetchAll();

echo "Processing " . count($users) . " active users...\n";

foreach ($users as $user) {
    $uid = $user['id'];
    echo "Processing User $uid (" . htmlspecialchars($user['name']) . ")...\n";

    // Initialize state if missing
    if (!$user['current_stage']) {
        $pdo->prepare("INSERT INTO user_agent_state (user_id, current_stage, next_followup_at) VALUES (?, 'onboarding_start', NOW())")
            ->execute([$uid]);
        $user['current_stage'] = 'onboarding_start';
        $user['next_followup_at'] = date('Y-m-d H:i:s');
    }

    // Check if it's time for follow-up
    if (strtotime($user['next_followup_at']) > time()) {
        echo "   ⏭️ Skipping. Next follow-up scheduled for: " . $user['next_followup_at'] . "\n";
        continue;
    }

    // ───────────────
    // Daily Motivation (If active today)
    // ───────────────
    $today = date('Y-m-d');
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM agent_interactions WHERE user_id = ? AND interaction_type = 'daily_quote' AND DATE(sent_at) = ?");
    $stmt_check->execute([$uid, $today]);
    if ($stmt_check->fetchColumn() == 0) {
        $quote = getDailyQuote($user['category'] ?? 'Business');
        
        // --- UPGRADE PITCH LOGIC ---
        $tier = $user['plan'] ?? 'free';
        $upgrade_pitch = "";
        if ($tier === 'free' || $tier === 'silver') {
            // Once every 3 days suggest an upgrade
            if (date('j') % 3 == 0) {
                $upgrade_pitch = getUpgradeAdvice($user['category'] ?? 'Business', $tier);
            }
        }
        
        echo "   ✨ Sending Daily Motivation...\n";
        // Log interaction
        $pdo->prepare("INSERT INTO agent_interactions (user_id, interaction_type, content) VALUES (?, 'daily_quote', ?)")
            ->execute([$uid, $quote . ($upgrade_pitch ? "\n\nPS: $upgrade_pitch" : "")]);
        
        $html_content = "
            <p style='color:#ffffff !important; font-size:1.1rem;'>Hi <strong>" . $user['name'] . "</strong>,</p>
            <p style='color:#ffffff !important;'>Here is your personalized business tip for today:</p>
            <div style='background:rgba(255,215,0,0.1); border-radius:12px; padding:20px; border-left:4px solid #FFD700; color:#FFD700 !important; font-style:italic;'>
                \"$quote\"
            </div>
        ";
        
        if ($upgrade_pitch) {
            $html_content .= "
                <div style='background:rgba(0,212,255,0.05); border:1px solid rgba(0,212,255,0.2); border-radius:10px; padding:15px; margin-top:20px; color:#00d4ff !important; font-size:.88rem;'>
                    <strong>🚀 Grow your " . ($user['category'] ?? 'Business') . " faster:</strong><br>
                    $upgrade_pitch
                </div>
            ";
        }

        $html_content .= "<p style='color:#666; font-size:.85rem; margin-top:20px;'>Your BizNexus Sub-Agent is working in the background to grow your " . ($user['category'] ?? 'Business') . " brand.</p>";

        $html = emailTemplate("Daily Business Motivation 🚀", $html_content, "View My Growth Board", "https://biznexus.in/dashboard.php");

        sendEmail($user['email'], "Daily BizQuote: Boost your " . ($user['category'] ?? 'Business') . "! 🚀", $html);
    }

    // Determine the next step based on profile completeness
    processUserJourney($pdo, $user);
}

echo "\nMaster Agent loop finished.\n";

/**
 * Personalized User Journey Logic
 */
function processUserJourney($pdo, $user) {
    $uid = $user['id'];
    $stage = $user['current_stage'];
    
    // Fetch profile details
    $stmt = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ?");
    $stmt->execute([$uid]);
    $bp = $stmt->fetch();
    
    // Check missing fields
    $missing = [];
    if (empty($bp['business_name'])) $missing[] = 'Business Name';
    if (empty($bp['category']))      $missing[] = 'Category';
    if (empty($bp['description']))   $missing[] = 'About/Description';
    if (empty($bp['phone']))         $missing[] = 'Contact Number';
    if (empty($bp['address']))       $missing[] = 'Location/Address';

    // Check Marketplace
    $stmt_m = $pdo->prepare("SELECT COUNT(*) FROM marketplace WHERE user_id = ?");
    $stmt_m->execute([$uid]);
    if ($stmt_m->fetchColumn() == 0) {
        $missing[] = 'Marketplace Products/Services';
    }

    // Journey Logic
    if (!empty($missing)) {
        // Send follow-up based on missing info (staged)
        $advice = getAgentAdvice($user['category'] ?? 'Business', count($missing) > 3 ? 'onboarding_start' : 'missing_marketplace', $user['name']);
        sendStagedFollowup($pdo, $user, $missing, $advice);
    } else {
        // Profile 100% complete!
        handleOrchestration($pdo, $user);
    }
}

function sendStagedFollowup($pdo, $user, $missing, $advice) {
    $uid = $user['id'];
    $next = date('Y-m-d H:i:s', strtotime('+2 days'));
    
    $subject = "Help needed with your " . ($user['category'] ?? 'Business') . " profile! 🚀";
    $html = emailTemplate("Unlock Your Business Potential! 🚀", "
        <p style='color:#ffffff !important; font-size:1.1rem;'>Hi <strong>" . $user['name'] . "</strong>,</p>
        <p style='color:#ffffff !important;'>$advice</p>
        <p style='color:#ffffff !important;'>To help you grow, we still need to complete your profile:</p>
        <ul style='color:#FFD700 !important;'>
            <li>" . implode("</li><li>", $missing) . "</li>
        </ul>
        <p style='color:#888; font-size:.9rem;'>Once complete, I'll automatically trigger our Web Developer and SEO agents to build your business presence.</p>
    ", "Complete Profile Now →", "https://biznexus.in/profile/edit.php");

    echo "   📧 Sending follow-up for missing [" . implode(', ', $missing) . "]...\n";
    
    // Log interaction
    $pdo->prepare("INSERT INTO agent_interactions (user_id, interaction_type, content) VALUES (?, 'onboarding_followup', ?)")
        ->execute([$uid, $html]);
    
    sendEmail($user['email'], $subject, $html);
    
    // Update state
    $pdo->prepare("UPDATE user_agent_state SET current_stage = ?, last_interaction_at = NOW(), next_followup_at = ? WHERE user_id = ?")
        ->execute(['followup_sent', $next, $uid]);
}

function handleOrchestration($pdo, $user) {
    $uid = $user['id'];
    echo "   ✅ Profile 100% Complete! Triggering downstream agents...\n";
    
    // 1. Mark onboarding complete in users table if not already
    $pdo->prepare("UPDATE users SET onboarding_complete = 1 WHERE id = ?")->execute([$uid]);
    
    // 2. Trigger Specialized Agents (Hypothetical logic for now, could be cron tasks)
    echo "   🛠️ Triggering Web Dev Agent...\n";
    // exec("php agent/web_dev_agent.php --user_id=$uid > /dev/null &");
    
    echo "   🔍 Triggering SEO Agent...\n";
    // exec("php agent/bulk_seo_agent.php --user_id=$uid > /dev/null &");
    
    echo "   📸 Triggering Instagram Agent...\n";
    // exec("php agent/social_media_agent.php --user_id=$uid > /dev/null &");

    // Clear next follow-up so we don't spam
    $pdo->prepare("UPDATE user_agent_state SET current_stage = 'fully_launched', next_followup_at = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE user_id = ?")
        ->execute([$uid]);
}
