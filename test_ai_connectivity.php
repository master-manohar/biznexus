<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/ai_helper_v3.php';
header('Content-Type: text/plain');

echo "=== AI Connectivity Test ===\n";

echo "\n1. Testing Anthropic (Claude 3 Haiku)...\n";
$anthropic_res = runAnthropicChat([['role'=>'user', 'content'=>'Hello']], 'Test system instruction');
if (isset($anthropic_res['error'])) {
    echo "FAILED: " . $anthropic_res['error'] . "\n";
} else {
    echo "SUCCESS: " . substr($anthropic_res['text'], 0, 50) . "...\n";
}

echo "\n2. Testing Google Gemini (1.5 Flash)...\n";
$gemini_res = runGeminiChat([['role'=>'user', 'content'=>'Hello']], 'Test system instruction');
if (is_array($gemini_res) && isset($gemini_res['error'])) {
    echo "FAILED: " . $gemini_res['error'] . "\n";
} else {
    $text = is_string($gemini_res) ? $gemini_res : ($gemini_res['text'] ?? 'N/A');
    echo "SUCCESS: " . substr($text, 0, 50) . "...\n";
}

echo "\n3. Testing runBizAI (Dual-AI-Fallback)...\n";
$dual_res = runBizAI([['role'=>'user', 'content'=>'Hello']], 'Test system instruction');
if (isset($dual_res['error'])) {
    echo "FAILED: " . $dual_res['error'] . "\n";
} else {
    echo "SUCCESS: " . substr($dual_res['text'], 0, 50) . "...\n";
}
?>
