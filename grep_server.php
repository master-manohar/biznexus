<?php
// Recursive search for a string
$dir = new RecursiveDirectoryIterator(__DIR__);
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.php$/', RegexIterator::GET_MATCH);
echo "Searching for Vanasthalipuram...\n";
foreach($files as $file) {
    $content = file_get_contents($file[0]);
    if(stripos($content, 'Vanasthalipuram') !== false) {
        echo "FOUND IN: " . $file[0] . "\n";
    }
}
echo "Done.";
?>
