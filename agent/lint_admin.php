<?php
$file = __DIR__ . '/../admin/index.php';
$output = shell_exec("php -l " . escapeshellarg($file));
echo "Syntax Check Result:\n$output\n";
