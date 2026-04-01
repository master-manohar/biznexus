<?php
$dir = __DIR__ . '/../sites/test_slug';
if (!is_dir($dir)) {
    if (!mkdir($dir, 0755, true)) {
        die("Failed to create dir: " . print_r(error_get_last(), true));
    }
}

if (!file_put_contents("$dir/index.php", "test")) {
    die("Failed to write file: " . print_r(error_get_last(), true));
}

echo "Success! Write test passed.";
