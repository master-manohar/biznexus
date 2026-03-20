<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';

// Check leads table schema
try {
    $cols = $pdo->query("DESCRIBE public_leads")->fetchAll(PDO::FETCH_ASSOC);
    echo "public_leads columns:\n<pre>";
    foreach($cols as $c) echo " - {$c['Field']} ({$c['Type']})\n";
    echo "</pre>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn();
    echo "Total leads in table: $count<br>\n";
    
    // Check if leads/list.php has any obvious include errors
    echo "includes_functions.php exists: " . (file_exists('includes_functions.php') ? 'YES' : 'NO') . "<br>\n";
    echo "includes/db.php exists: " . (file_exists('includes/db.php') ? 'YES' : 'NO') . "<br>\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
