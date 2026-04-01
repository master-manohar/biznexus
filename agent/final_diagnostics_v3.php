<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
echo "DEBUG: Root is $root\n";

echo "DEBUG: Testing includes_functions.php syntax...\n";
require_once $root . '/includes_functions.php';
echo "DEBUG: includes_functions.php loaded successfully.\n";

echo "SIMULATING find.php FINAL SUBMISSION (REAL POST)...\n";

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['q'] = 'Healthcare';
$_GET['city'] = '';
$_POST['submit_lead'] = '1';
$_POST['name'] = 'User Test';
$_POST['phone'] = '9999988888';
$_POST['matched_category'] = 'Healthcare';
$_POST['matched_city'] = 'Hyderabad';
$_POST['original_query'] = 'Healthcare';

try {
    chdir($root);
    include 'find.php';
    if (isset($leadSubmitted) && $leadSubmitted) {
        echo "\n\nSUCCESS: Lead submitted correctly in simulation.";
    } else {
        echo "\n\nFAILED: Lead submission block reached but success flag not set. Error: " . ($errorMessage ?? 'None');
    }
} catch (Throwable $e) {
    echo "\n\nCAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    echo "\nStack Trace:\n" . $e->getTraceAsString();
}
