<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Path Discovery</h3>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "DIR: " . __DIR__ . "<br>";

function findFile($dir, $filename) {
    $it = new RecursiveDirectoryIterator($dir);
    foreach(new RecursiveIteratorIterator($it) as $file) {
        if ($file->getFilename() === $filename) {
            echo "Found: " . $file->getPathname() . "<br>";
        }
    }
}

echo "Searching in " . realpath(__DIR__ . '/../../') . "...<br>";
try {
    findFile(realpath(__DIR__ . '/../../'), 'db.php');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
助
