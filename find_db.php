<?php
$files = ['db.php', 'includes/db.php', '../includes/db.php', 'config/db.php'];
foreach($files as $f) {
    if (file_exists($f)) {
        echo "FOUND: $f\n";
    }
}
echo "DIR: " . __DIR__;
