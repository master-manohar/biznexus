<?php
header('Content-Type: text/plain');
$file = __DIR__ . '/../includes/ai_helper_v3.php';
echo "FILE: $file\n";
echo "--- START ---\n";
echo file_get_contents($file);
echo "\n--- END ---\n";
