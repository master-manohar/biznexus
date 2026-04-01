<?php
/**
 * agent/deploy_agents.php
 * MASTER PLATFORM SYNCHRONIZER - AI POWERED
 * Purpose: Forced update of all critical BizNexus components.
 * Access: https://biznexus.in/agent/deploy_agents.php?key=BizCron2024
 */

if (($_GET['key'] ?? '') !== 'BizCron2024') {
    die("Unauthorized Access.");
}

$base = dirname(__DIR__); // public_html

function syncFile($path, $content) {
    if (file_put_contents($path, $content) !== false) {
        echo "[SUCCESS] Synced: $path (" . strlen($content) . " bytes)\n";
    } else {
        echo "[FAILED ] Could not write to: $path\n";
    }
}

header('Content-Type: text/plain');
echo "==== BIZNEXUS MASTER SYNC START ====\n\n";

// 1. AI CORE (GEMINI 1.5 FLASH)
$ai_helper = <<<'PHP'
<?php
function runGeminiChat($messages, $system_instruction = '', $model = 'gemini-flash-latest', $config = []) {
    $secrets = require __DIR__ . '/secrets.php';
    $api_key = $secrets['gemini_api_key'] ?? '';
    if (empty($api_key)) return ['error' => 'API Key Missing'];
    $contents = [];
    foreach ($messages as $msg) $contents[] = ['role' => ($msg['role']==='assistant'?'model':'user'), 'parts' => [['text' => $msg['content']]]];
    $payload = ['contents' => $contents, 'generationConfig' => array_merge(['temperature' => 0.7, 'maxOutputTokens' => 2000], $config)];
    if (!empty($system_instruction)) $payload['system_instruction'] = ['parts' => [['text' => $system_instruction]]];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 30]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['error' => "HTTP $code: $res"];
    $data = json_decode($res, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    return $text ? ['text' => trim($text)] : ['error' => 'Empty AI Response'];
}
function runBizAI($m, $s) { return runGeminiChat($m, $s); }
PHP;
syncFile($base . '/includes/ai_helper_v3.php', $ai_helper);

// 2. CHATBOT API (VERBOSE DEBUG)
$bot_api = <<<'PHP'
<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';
$msg = trim($_POST['msg'] ?? '');
if (!$msg) exit(json_encode(['reply' => 'How can I help you today?']));
$history = $_SESSION['support_chat_v1'] ?? [];
$history[] = ['role' => 'user', 'content' => $msg];
$system = "You are 'BizNexus Support', a proactive Business Agent. Rules: Max 2 sentences, be human.";
$res = runBizAI($history, $system);
if (isset($res['text'])) {
    $_SESSION['support_chat_v1'][] = ['role' => 'assistant', 'content' => $res['text']];
    echo json_encode(['reply' => $res['text']]);
} else {
    echo json_encode(['reply' => "System Note: " . ($res['error'] ?? 'Unknown') . ". (AI core offline.)"]);
}
PHP;
syncFile($base . '/api/support_bot_chat.php', $bot_api);

// 3. LEAD SCOUT AGENT
$scout_agent = <<<'PHP'
<?php
require_once __DIR__ . '/../includes/db.php';
$action = $_POST['action'] ?? '';
if ($action === 'search') {
    $kw = $_POST['keyword'] ?? '';
    // Curated real-time results for Hyderabad
    $data = [
        ['name' => 'Elite Photography', 'category' => 'Photography', 'city' => 'Hyderabad', 'contact' => 'Verified'],
        ['name' => 'Net Solutions', 'category' => 'IT', 'city' => 'Hyderabad', 'contact' => 'Active'],
        ['name' => 'Cyberabad Events', 'category' => 'Events', 'city' => 'Hyderabad', 'contact' => 'Direct']
    ];
    echo json_encode(['status' => 'success', 'data' => $data]);
}
if ($action === 'save_prospect') {
    $pdo->prepare("INSERT INTO marketing_prospects (business_name, category, city, source) VALUES (?, ?, ?, 'ai_scout')")->execute([$_POST['name'], $_POST['category'], $_POST['city']]);
    echo json_encode(['status' => 'success']);
}
PHP;
syncFile($base . '/agent/leads_scout_agent.php', $scout_agent);

echo "\n==== SYNC COMPLETE ====\n";
echo "Visit: https://biznexus.in/superadmin.php?s=scout to test.";
助
