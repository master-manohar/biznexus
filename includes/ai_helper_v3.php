<?php
/**
 * includes/ai_helper_v3.php
 * Final stable version using Gemini 3 as verified.
 * VERSION: 3.5 (Dual-AI-Fallback)
 */
define('BIZNEXUS_AI_VERSION', '3.5');
function runAnthropicChat($messages, $system = '', $model = 'claude-3-haiku-20240307') {
    $secrets = require __DIR__ . '/secrets.php';
    $api_key = $secrets['anthropic_api_key'] ?? '';
    if (empty($api_key)) return ['error' => 'Anthropic API Key missing'];

    $payload = [
        'model' => $model,
        'max_tokens' => 1024,
        'messages' => $messages,
        'system' => $system
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "Anthropic Error (HTTP $httpCode): " . ($curl_err ?: $response)];
    }

    $result = json_decode($response, true);
    return ['text' => $result['content'][0]['text'] ?? ''];
}
function runGeminiChat($messages, $system_instruction = '', $model = 'gemini-2.0-flash', $config = []) {
    $secrets = require __DIR__ . '/secrets.php';
    $api_key = $secrets['gemini_api_key'] ?? '';

    if (empty($api_key)) {
         return ['error' => 'Gemini API Key missing in secrets.php'];
    }

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
            'maxOutputTokens' => 4096,
        ], $config)
    ];

    if (!empty($system_instruction)) {
        $payload['system_instruction'] = [
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
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "Gemini API Error (HTTP $httpCode): " . ($curl_err ?: $response)];
    }

    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text && isset($result['error'])) {
        return ['error' => "Gemini Logic Error: " . ($result['error']['message'] ?? 'Unknown')];
    }

    return $text ? ['text' => trim($text)] : ['error' => 'Empty response from Gemini 1.5 Flash'];
}

function runBizAI($messages, $system = '', $model = 'claude-3-haiku-20240307', $config = []) {
    // Primary: Anthropic Claude
    $res = runAnthropicChat($messages, $system, $model);
    if (isset($res['text'])) return $res;

    error_log('[BizAI] Anthropic failed: ' . ($res['error'] ?? 'unknown'));

    // Fallback cascade — each Gemini model has its own separate quota pool
    $geminiModels = [
        'gemini-2.0-flash',       // Primary fallback (1500 RPD free)
        'gemini-2.0-flash-lite',  // Separate lighter quota pool
        'gemini-1.5-flash',       // Older model — separate quota
        'gemini-1.0-pro',         // Last resort — oldest, most permissive
    ];

    foreach ($geminiModels as $gModel) {
        $res = runGeminiChat($messages, $system, $gModel, $config);
        if (isset($res['text'])) return $res;

        $errBody = $res['error'] ?? '';
        $is429   = strpos($errBody, '429') !== false || strpos($errBody, 'RESOURCE_EXHAUSTED') !== false;

        error_log("[BizAI] $gModel failed" . ($is429 ? ' (quota exhausted)' : '') . ': ' . substr($errBody, 0, 120));

        if (!$is429) break; // Non-quota error (e.g. auth) — no point trying other models
        // 429 → try next model immediately, no sleep needed (quota is per-model)
    }

    return $res; // Return last error for caller to log
}


/**
 * Simplified string-in string-out AI helper
 */
function runBizAIString($prompt, $system = '', $model = 'claude-3-haiku-20240307', $config = []) {
    $messages = [['role' => 'user', 'content' => $prompt]];
    $res = runBizAI($messages, $system, $model, $config);
    return $res['text'] ?? '';
}
