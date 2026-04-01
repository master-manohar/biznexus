<?php
$secrets = require_once __DIR__ . '/../includes/secrets.php';
$api_key = $secrets['anthropic_api_key'];

$system = "You are a helpful assistant. Reply with 'AI ACTIVE' if you hear me.";
$messages = [['role'=>'user','content'=>'Hello!']];
$payload = ['model'=>'claude-3-haiku-20240307','max_tokens'=>100,'system'=>$system,'messages'=>$messages];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>json_encode($payload),
    CURLOPT_HTTPHEADER=>[
        "Content-Type: application/json",
        "x-api-key: {$api_key}",
        "anthropic-version: 2023-06-01"
    ],
    CURLOPT_TIMEOUT=>20
]);
$resp = curl_exec($ch); 
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
$err = curl_error($ch);
curl_close($ch);

header('Content-Type: text/plain');
echo "HTTP CODE: $code\n";
echo "CURL ERR: $err\n";
echo "RESPONSE: $resp\n";
?>
