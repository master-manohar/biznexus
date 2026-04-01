<?php
// api/public_bot_chat.php
session_start();

$msg = trim($_POST['message'] ?? $_POST['msg'] ?? '');
$init = $_POST['init'] ?? null;
$context = $_POST['context'] ?? '';

$history = $_SESSION['public_bot_history'] ?? [];

if ($init) {
    if (empty($history)) {
        if ($context === 'find') {
            $reply = "Hi there! 👋 I'm the BizNexus Matchmaker. What kind of business or service are you looking for, and in which city?";
        } else {
            $reply = "Welcome to BizNexus! 👋 How can I help you today?";
        }
        $history[] = ['role' => 'assistant', 'content' => $reply];
        $_SESSION['public_bot_history'] = $history;
        echo json_encode(['reply' => $reply]);
    } else {
        $last = end($history);
        echo json_encode(['reply' => $last['content'] ?? 'Hello again!']);
    }
    exit;
}

if (empty($msg)) {
    echo json_encode(['reply' => 'Please type a message.']);
    exit;
}

require_once __DIR__ . '/../includes/ai_helper_v3.php';

$system = <<<PROMPT
You are the BizNexus Matchmaker Assistant, an AI helping public visitors find businesses on biznexus.in.
Your goal is to collect 4 specific pieces of information from the user:
1. What they are looking for (e.g., Paper Cups, Web Design, IT Services)
2. Their City/Location
3. Their Name
4. Their Mobile Number

Keep responses incredibly short (strictly 1-2 sentences). Ask for missing information one by one so it feels like a natural conversation. Be extremely polite and friendly.

CRITICAL INSTRUCTION:
Once you have successfully collected ALL 4 pieces of information (Requirement, City, Name, Phone), you MUST append this exact token to the very end of your message:
[LEAD_DATA:TheirName:TheirPhone:TheirRequirement:TheirCity]
Make sure to replace the placeholders with the actual collected data. Do not use colons inside the data itself. Say something like "Thank you, I am matching you now." right before the token.
PROMPT;

$history[] = ['role' => 'user', 'content' => $msg];

$result = runBizAI($history, $system);

$bot_reply = "I'm having a little trouble connecting right now. Please test again later.";
if (isset($result['text'])) {
    $raw_reply = $result['text'];
        
        if (preg_match('/\[LEAD_DATA:(.*?):(.*?):(.*?):(.*?)\]/', $raw_reply, $matches)) {
            $leadName = trim($matches[1]);
            $leadPhone = trim($matches[2]);
            $leadQuery = trim($matches[3]);
            $leadCity = trim($matches[4]);
            
            require_once __DIR__ . '/../includes/db.php';
            require_once __DIR__ . '/../includes/lead_dispatch_engine.php';
            // Category mapped as query since AI doesn't know exact db enum
            dispatchPublicLead($pdo, $leadName, $leadPhone, '', $leadQuery, 'Other', $leadCity);
            
            $bot_reply = trim(str_replace($matches[0], '', $raw_reply));
            $bot_reply .= "\n\n✅ **Success!** Your requirement has been sent to verified businesses in " . htmlspecialchars($leadCity) . ". They will contact you shortly on " . htmlspecialchars($leadPhone) . "!";
        } else {
            $bot_reply = $raw_reply;
        }

        $history[] = ['role' => 'assistant', 'content' => $bot_reply];
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        $_SESSION['public_bot_history'] = $history;
    } else {
        $err = $result['error'] ?? 'Unknown AI Connection Error';
        $v = defined('BIZNEXUS_AI_VERSION') ? BIZNEXUS_AI_VERSION : 'LEGACY';
        $bot_reply = "System Note: $err. (AI Version: $v)";
    }

echo json_encode(['reply' => $bot_reply]);
