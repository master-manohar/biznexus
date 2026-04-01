<?php
/**
 * includes/ai_helper.php
 * Centralized AI Orchestrator for BizNexus.
 * Handles switching between Anthropic and Google Gemini.
 */

/**
 * Executes a chat completion using Google Gemini 1.5
 * @param array $messages Standard message array [['role' => 'user', 'content' => '...']]
 * @param string $system_instruction The system prompt
 * @param string $model Defaults to gemini-1.5-flash for cost/speed
 */
function runGeminiChat($messages, $system_instruction = '', $model = 'gemini-flash-latest', $config = []) {
    $secrets = require __DIR__ . '/secrets.php';
    $api_key = $secrets['gemini_api_key'] ?? '';

    if (empty($api_key) || $api_key === 'PASTE_YOUR_GEMINI_KEY_HERE') {
        return ['error' => 'Gemini API Key missing in includes/secrets.php'];
    }

    // Convert Anthropic/OpenAI style messages to Gemini style
    $contents = [];
    foreach ($messages as $msg) {
        $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $msg['content']]]
        ];
    }

    $payload = [
        'contents' => $contents,
        'generationConfig' => array_merge([
            'temperature' => 0.7,
            'maxOutputTokens' => 1000,
        ], $config)
    ];

    if (!empty($system_instruction)) {
        $payload['systemInstruction'] = [
            'parts' => [['text' => $system_instruction]]
        ];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "Gemini API Error (HTTP $httpCode): " . $response];
    }

    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($text) {
        return ['text' => trim($text)];
    } else {
        return ['error' => 'Empty response from Gemini API'];
    }
}

/**
 * Universal AI call that can be toggled via config
 */
function runBizAI($messages, $system = '', $model = 'claude-3-haiku-20240307', $config = []) {
    // Redirect to v3 helper for consistent Claude-only experience
    if (file_exists(__DIR__ . '/ai_helper_v3.php')) {
        require_once __DIR__ . '/ai_helper_v3.php';
        return runBizAI($messages, $system, $model, $config);
    }
    
    // Fallback if v3 missing
    return runGeminiChat($messages, $system, 'gemini-flash-latest', $config);
}
