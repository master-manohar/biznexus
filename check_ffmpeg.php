<?php
// check_ffmpeg.php — Test if FFmpeg is available for audio mixing
echo shell_exec('which ffmpeg 2>&1') ?: "not found via which\n";
echo shell_exec('ffmpeg -version 2>&1') ?: "ffmpeg not available\n";
echo "\nPHP exec disabled: " . (function_exists('exec') ? 'NO' : 'YES') . "\n";
echo "Shell exec disabled: " . (function_exists('shell_exec') ? 'NO' : 'YES') . "\n";
