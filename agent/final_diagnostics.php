<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Testing includes_functions.php syntax...\n";
include 'includes_functions.php';
echo "DEBUG: includes_functions.php loaded successfully.\n";

echo "DEBUG: Testing awardCoins redeclaration...\n";
// Manually define a dummy function to see if my check works
if (!function_exists('testFunc')) {
    function testFunc() { echo "Original\n"; }
}
if (!function_exists('testFunc')) {
    function testFunc() { echo "Duplicate\n"; }
}
echo "DEBUG: function_exists checks out.\n";

echo "SIMULATING find.php FINAL SUBMISSION...\n";

$_GET['q'] = 'Healthcare';
$_GET['city'] = '';
$_POST['submit_lead'] = '1';
$_POST['name'] = 'User Test';
$_POST['phone'] = '9999988888';
$_POST['matched_category'] = 'Healthcare';
$_POST['matched_city'] = 'Hyderabad';
$_POST['original_query'] = 'Healthcare';

try {
    include 'find.php';
    echo "\n\nSUCCESS: find.php submission simulated.";
} catch (Throwable $e) {
    echo "\n\nCAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
