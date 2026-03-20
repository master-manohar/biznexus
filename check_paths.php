<?php
echo "Current File: " . __FILE__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "db.php exists here: " . (file_exists('db.php') ? 'YES' : 'NO') . "\n";
echo "db.php exists in parent: " . (file_exists('../db.php') ? 'YES' : 'NO') . "\n";
echo "db.php exists in includes: " . (file_exists('includes/db.php') ? 'YES' : 'NO') . "\n";
?>
