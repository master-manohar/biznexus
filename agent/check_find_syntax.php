<?php
$output = [];
$return_var = 0;
exec('php -l ' . escapeshellarg(__DIR__ . '/find.php'), $output, $return_var);
echo implode("\n", $output);
if ($return_var !== 0) echo "\nSYNTAX ERROR";
else echo "\nNo syntax errors.";
