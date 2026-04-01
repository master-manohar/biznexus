<?php
// agent/diag_ai_v2.php
require_once __DIR__ . '/../includes/ai_helper_v3.php';
header('Content-Type: text/plain');
echo "BIZNEXUS AI DIAGNOSTIC V2\n";
echo "AI Version: " . BIZNEXUS_AI_VERSION . "\n";

$messages = [['role' => 'user', 'content' => 'Hello, are you active?']];
$system = "Return 'ACTIVE' as JSON: {status: 'ACTIVE'}";

echo "\n--- Testing runAnthropicChat Directly ---\n";
$res_ant = runAnthropicChat($messages, $system);
print_r($res_ant);

echo "\n--- Testing runBizAI (Default: Claude) ---\n";
$res = runBizAI($messages, $system);
print_r($res);

echo "\n--- Testing runGeminiChat (Fallback Check) ---\n";
$res2 = runGeminiChat($messages, $system);
print_r($res2);
