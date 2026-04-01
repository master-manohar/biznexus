<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "SIMULATING find.php REQUEST...\n";

// Mock GET/POST
$_GET['q'] = 'test query';
$_GET['city'] = 'Hyderabad';
$_POST['submit_lead'] = '1';
$_POST['name'] = 'Test User';
$_POST['phone'] = '1234567890';
$_POST['matched_category'] = 'Technology';
$_POST['matched_city'] = 'Hyderabad';
$_POST['original_query'] = 'test query';

try {
    include 'find.php';
    echo "\n\nSUCCESS: find.php executed without fatal error.";
} catch (Throwable $e) {
    echo "\n\nCAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
