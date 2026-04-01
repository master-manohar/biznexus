<?php
/**
 * agent/test_gemini_diag.php
 * Diagnostic tool to verify Google Gemini integration.
 */
require_once __DIR__ . '/../includes/ai_helper_v3.php';

header('Content-Type: text/plain');
echo "--- BizNexus Gemini Diagnostic ---\n";

$test_messages = [['role' => 'user', 'content' => 'Hello Gemini! Are you active? Reply with: GEMINI ONLINE']];
$system = "You are a diagnostic assistant. Be brief.";

echo "Testing Gemini Flash Latest...\n";
$res = runGeminiChat($test_messages, $system, 'gemini-flash-latest');

if (isset($res['text'])) {
    echo "SUCCESS: " . $res['text'] . "\n";
} else {
    echo "FAILED: " . ($res['error'] ?? 'Unknown error') . "\n";
}

echo "\nTesting Gemini Pro Latest (for Website Architect)...\n";
$resPro = runGeminiChat($test_messages, $system, 'gemini-pro-latest');

if (isset($resPro['text'])) {
    echo "SUCCESS: " . $resPro['text'] . "\n";
} else {
    echo "FAILED: " . ($resPro['error'] ?? 'Unknown error') . "\n";
}

echo "\nTesting Gemini 1.0 Pro (Standard)...\n";
$res1 = runGeminiChat($test_messages, $system, 'gemini-1.0-pro');

if (isset($res1['text'])) {
    echo "SUCCESS: " . $res1['text'] . "\n";
} else {
    echo "FAILED: " . ($res1['error'] ?? 'Unknown error') . "\n";
}

echo "\n--- Diagnostic Finished ---\n";
