<?php
$output = [];
$return_var = 0;
exec('php -l ' . escapeshellarg(__DIR__ . '/lead_mover_ai_agent.php'), $output, $return_var);
echo implode("\n", $output);
if ($return_var !== 0) echo "\nSYNTAX ERROR DETECTED";
else echo "\nNo syntax errors detected.";
