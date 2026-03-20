<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Loading includes_functions.php...<br>";
include 'includes_functions.php';
echo "Loaded!<br>";

if (function_exists('getUnreadCount')) {
    echo "getUnreadCount exists.<br>";
} else {
    echo "getUnreadCount MISSING.<br>";
}
?>
