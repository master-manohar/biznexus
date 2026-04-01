<?php
/**
 * debug_auth.php
 * Diagnostic script to check current user status.
 * DELETE THIS FILE AFTER USE.
 */
session_start();
require_once __DIR__ . '/includes/db.php';

echo "<h1>BizNexus Auth Debug</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red'>Not logged in.</p>";
    exit;
}

$uid = $_SESSION['user_id'];
echo "<p>Logged in as User ID: <strong>$uid</strong></p>";

try {
    $stmt = $pdo->prepare("SELECT id, name, email, email_verified, is_verified, verify_token FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<h3>Database State:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        echo "<h3>Session State:</h3>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        
        echo "<h3>Verification Check:</h3>";
        if ($user['email_verified']) {
            echo "<p style='color:green'>✅ Email is VERIFIED in Database.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Email is NOT VERIFIED in Database.</p>";
            echo "<p>Token exists: " . ($user['verify_token'] ? 'Yes' : 'No') . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>User row not found in DB.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='/dashboard/index.php'>Back to Dashboard</a> | <a href='/profile/edit.php'>Back to Profile</a></p>";
