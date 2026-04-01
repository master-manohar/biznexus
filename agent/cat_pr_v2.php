<?php
echo "FILE_SIZE: " . filesize(__DIR__ . '/media_pr_agent.php') . "\n";
echo "LAST_MODIFIED: " . date("Y-m-d H:i:s", filemtime(__DIR__ . '/media_pr_agent.php')) . "\n";
$content = file_get_contents(__DIR__ . '/media_pr_agent.php');
echo "LINE_COUNT: " . substr_count($content, "\n") . "\n";
if (strpos($content, 'Shradha Sharma') !== false) echo "Shradha Sharma FOUND!\n";
else echo "Shradha Sharma NOT FOUND!\n";
