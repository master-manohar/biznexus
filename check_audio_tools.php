<?php
// check_audio_tools.php
$tools = ['ffmpeg','avconv','mencoder','sox','mp4box','HandBrakeCLI'];
foreach ($tools as $t) {
    $r = shell_exec("which $t 2>&1");
    echo "$t: " . (str_contains($r,'no ') || empty(trim($r)) ? '❌' : '✅ ' . trim($r)) . "\n";
}
echo "\nPHP extensions: " . implode(', ', get_loaded_extensions()) . "\n";
echo "Temp dir: " . sys_get_temp_dir() . "\n";
echo "Disk free: " . round(disk_free_space('/') / 1024 / 1024, 1) . " MB\n";
