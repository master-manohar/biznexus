<?php
header('Content-Type: text/plain');
$file = __DIR__ . '/test_gemini_diag.php';
if (file_exists($file)) {
    echo "FILE: $file\n";
    echo "--- START ---\n";
    echo file_get_contents($file);
    echo "\n--- END ---\n";
} else {
    echo "NOT FOUND\n";
}
