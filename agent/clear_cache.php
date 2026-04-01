<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache Reset Successful.\n";
} else {
    echo "OpCache Not Available.\n";
}
echo "Time: " . date('Y-m-d H:i:s') . "\n";
