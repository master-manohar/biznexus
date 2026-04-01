<?php
$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
echo "SCANNING RECIPIENTS OF functions.php...\n";

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $f = $file->getPathname();
    if (pathinfo($f, PATHINFO_EXTENSION) !== 'php') continue;
    
    $content = @file_get_contents($f);
    if ($content && stripos($content, 'functions.php') !== false) {
        echo "FILE: $f matches 'functions.php'\n";
    }
}
echo "DONE.\n";
