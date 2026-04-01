<?php
header('Content-Type: text/plain');
echo "Script Path: " . __FILE__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";

$test_file = __DIR__ . '/../includes/ai_helper_v3.php';
if (file_exists($test_file)) {
    echo "AI HELPER V3 FOUND AT: $test_file\n";
    echo "Last Modified: " . date("Y-m-d H:i:s", filemtime($test_file)) . "\n";
    echo "--- CONTENT PREVIEW ---\n";
    echo substr(file_get_contents($test_file), 0, 500);
} else {
    echo "AI HELPER V3 NOT FOUND AT: $test_file\n";
    // Search for it
    echo "Searching for ai_helper_v3.php...\n";
    exec("find /home/u175452495 -name ai_helper_v3.php", $output);
    print_r($output);
}
