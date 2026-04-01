<?php
header('Content-Type: text/plain');
$file = __DIR__ . '/../includes/ai_helper.php';
if (file_exists($file)) {
    echo "FILE EXISTS: $file\n";
    echo "--- START ---\n";
    echo file_get_contents($file);
    echo "\n--- END ---\n";
} else {
    echo "FILE NOT FOUND at $file\n";
}
