<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$ite = new RecursiveIteratorIterator($dir);
echo "Searching all files for Vanasthalipuram...\n";
foreach($ite as $file) {
    if($file->isFile()) {
        $content = file_get_contents($file->getPathname());
        if(stripos($content, 'Vanasthalipuram') !== false) {
            echo "FOUND IN: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.";
?>
