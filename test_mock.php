<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock session
session_start();
$_SESSION['user_id'] = 1; // Assume demo user

echo "Mocking session for user 1...<br>";

// We can't easily include layout_start.php if it has its own auth_check
// Let's check the code of layout_start.php again and try to run it line by line or include parts.

echo "Testing includes/db.php...<br>";
include 'includes/db.php';
echo "DB Check OK!<br>";

echo "Testing includes_functions.php...<br>";
include 'includes_functions.php';
echo "Functions OK!<br>";

global $pdo;
$user_id = 1;

echo "Testing pdo->prepare for users...<br>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User fetch OK! Name: " . ($user['name'] ?? 'N/A') . "<br>";

echo "Testing getUnreadCount...<br>";
$unread_notifs = getUnreadCount($pdo, $user_id);
echo "Unread count: $unread_notifs<br>";

echo "Testing layout rendering start...<br>";
?>
<div style="background:red; color:white; padding:10px;">If you see this, basic PHP finished.</div>
