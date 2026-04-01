<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$paths = [
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
    __DIR__ . '/../../db.php',
    __DIR__ . '/../../includes/db.php'
];

echo "<h3>Include Check</h3>";
foreach ($paths as $p) {
    echo "Path: $p - " . (file_exists($p) ? '✅ EXISTS' : '❌ MISSING') . "<br>";
}
助
