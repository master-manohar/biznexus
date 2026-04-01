<?php
/**
 * agent/test_claude_only.php
 * Verify that the system now defaults to Claude.
 */
require_once __DIR__ . '/../includes/ai_helper_v3.php';

header('Content-Type: text/plain');
echo "--- BizNexus Claude-Only Verification ---\n";

$messages = [['role' => 'user', 'content' => 'Who are you? Reply with: I AM CLAUDE']];
$res = runBizAI($messages);

if (isset($res['text'])) {
    echo "SUCCESS: " . $res['text'] . "\n";
} else {
    echo "FAILED: " . ($res['error'] ?? 'Unknown error') . "\n";
}

echo "\n--- Verification Finished ---\n";
