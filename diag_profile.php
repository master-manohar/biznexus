<?php
// Diagnostic for profile/edit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();

echo "<h2>Profile Edit Diagnostic</h2>";

// Check if session has user
if (empty($_SESSION['user_id'])) {
    echo "<p style='color:orange'>⚠️ No session user_id — user not logged in</p>";
} else {
    echo "<p style='color:green'>✅ Session user_id = " . (int)$_SESSION['user_id'] . "</p>";
}

// Check includes
$files = [
    'includes/db.php',
    'includes/auth_check.php',
    'includes_functions.php',
    'includes/layout_start.php',
    'profile/edit.php',   // check if it even exists
];
foreach ($files as $f) {
    $exists = file_exists(__DIR__ . '/' . $f);
    $color = $exists ? 'green' : 'red';
    echo "<p style='color:$color'>" . ($exists ? '✅' : '❌') . " $f</p>";
}

// Check DB and users table
try {
    require_once 'includes/db.php';
    echo "<p style='color:green'>✅ DB connected</p>";
    
    if (!empty($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, name, email, phone, business_name, category, city, profile_complete FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "<p style='color:green'>✅ User found: " . htmlspecialchars($user['name']) . "</p>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } else {
            echo "<p style='color:red'>❌ No user found for id=$uid</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
