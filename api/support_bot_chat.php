<?php
/**
 * /api/support_bot_chat.php
 * AI Business Development Agent & Support Bot for BizNexus
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'Please login to chat with your Business Assistant.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = trim($_POST['msg'] ?? '');
$sess_key = 'support_chat_v1';

// 1. Fetch User Context
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$userData = $user->fetch(PDO::FETCH_ASSOC);

$business = $pdo->prepare("SELECT * FROM businesses WHERE user_id = ?");
$business->execute([$user_id]);
$bizData = $business->fetch(PDO::FETCH_ASSOC);

$profile = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ?");
$profile->execute([$user_id]);
$profData = $profile->fetch(PDO::FETCH_ASSOC);

$meetings = $pdo->prepare("SELECT * FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND status = 'scheduled' AND meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 3");
$meetings->execute([$user_id, $user_id]);
$meetingList = $meetings->fetchAll(PDO::FETCH_ASSOC);

// 2. Identify Missing Information
$missing_core = [];
if (empty($bizData['name'])) $missing_core[] = "Business Name";
if (empty($bizData['category'])) $missing_core[] = "Business Category";
if (empty($bizData['city'])) $missing_core[] = "City location";

$missing_verification = [];
if (empty($bizData['gst'])) $missing_verification[] = "GST Number";
if (empty($profData['pan_number'])) $missing_verification[] = "PAN Number";
if (empty($profData['aadhaar_number'])) $missing_verification[] = "Aadhaar Number";
if (empty($userData['email_verified'])) $missing_verification[] = "Verified Email";

$profile_core_complete = empty($missing_core);
$profile_complete = ($profile_core_complete && empty($missing_verification));

$created_at = new DateTime($userData['created_at'] ?? 'now');
$now = new DateTime();
$account_age_days = $now->diff($created_at)->days;
$eligible_for_website = ($profile_complete && $account_age_days >= 3 && empty($bizData['website_generated']));

// Format missing items for prompt
$missing_str = "";
if (!$profile_core_complete) {
    $missing_str = "Core Identity Missing: " . implode(', ', $missing_core) . " (Ask them to update their profile first)";
} else {
    $missing_str = "Verification Missing: " . implode(', ', $missing_verification) . " (Politely ask for these only after core profile is done)";
}

// 3. Define Website Generation Context
$web_instruct = "";
if ($eligible_for_website) {
    $web_instruct = "WEBSITE OPPORTUNITY: User is eligible for a free one-page site. If they seem interested in growth, offer to 'instantly build a professional website' for them.";
} elseif (!empty($bizData['website_generated'])) {
    $web_instruct = "WEBSITE STATUS: They already have a website. If they ask about it, tell them it's live at " . $bizData['slug'] . ".biznexus.in";
}

require_once __DIR__ . '/../includes/ai_helper_v3.php';

$system = "You are 'BizNexus Support', a proactive Business Agent.
CRITICAL RULES YOU MUST OBEY:
1. EXTREMELY SHORT REPLIES. Maximum 2 sentences per response. No exceptions.
2. NO BULLET POINTS. NEVER use lists.
3. Be conversational and human-like.
4. If they agree to generate a website, say 'I am building your website now...' and append [TRIGGER_WEBSITE_GEN] exactly. 

USER CONTEXT:
- Name: {$userData['name']}
- Business: {$bizData['name']}
- Missing Profile Items: {$missing_str}
- Web Generated: {$bizData['website_generated']}
- Account Age: {$account_age_days} days

CONVERSATIONAL FLOW (Ask ONE thing, then wait):
1. INITIAL GREETING: Say 'Namaskaram! I'm your Business Agent.' Then ask 'How is your business doing today?'. Wait for reply.
2. PROFILE CHECK: If they are missing Core Identity items, ask them to complete their basic profile. If Core is done but Verification is missing, politely ask them to provide PAN/GST.
3. ENGAGEMENT: Ask 'What are your next plans to grow your business?'
{$web_instruct}

TRIGGERING ACTIONS:
- If user says yes to a website, append exactly: [TRIGGER_WEBSITE_GEN].
- If user asks for a specific business/service (e.g. 'I need a builder in Hyderabad'), append exactly: [REDIRECT_FIND:builder:hyderabad].";


// 4. Handle History
$history = $_SESSION[$sess_key] ?? [];
if ($msg) {
    $history[] = ['role' => 'user', 'content' => $msg];
} else {
    // Initial greeting trigger
    $history[] = ['role' => 'user', 'content' => 'Trigger initial greeting.'];
}

if (count($history) > 15) $history = array_slice($history, -15);

// 5. Call Gemini
$result = runBizAI($history, $system);

if (isset($result['text'])) {
    $reply = $result['text'];
    $history[] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION[$sess_key] = $history;
    echo json_encode(['reply' => $reply]);
} else {
    $err = $result['error'] ?? 'Unknown AI Connection Error';
    $v = defined('BIZNEXUS_AI_VERSION') ? BIZNEXUS_AI_VERSION : 'LEGACY';
    echo json_encode(['reply' => "System Note: $err. (AI Version: $v)"]);
}
