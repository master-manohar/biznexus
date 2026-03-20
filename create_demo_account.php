<?php
// One-time script to create a Razorpay demo account on BizNexus
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

$email    = 'demo@biznexus.in';
$password = 'Demo@1234';
$name     = 'BizNexus Demo';
$hash     = password_hash($password, PASSWORD_BCRYPT);

// Check if already exists
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
$existing = $check->fetchColumn();

if ($existing) {
    // Update password to make sure it's correct
    $pdo->prepare("UPDATE users SET password=?, name=?, status='active', email_verified=1, role='member', plan='premium' WHERE email=?")
        ->execute([$hash, $name, $email]);
    echo "✅ Updated existing demo account.\n";
    $uid = $existing;
} else {
    // Create fresh demo account
    $pdo->prepare("INSERT INTO users (name, email, password, status, email_verified, role, plan, coins, profile_complete, created_at)
                   VALUES (?, ?, ?, 'active', 1, 'member', 'premium', 100, 1, NOW())")
        ->execute([$name, $email, $hash]);
    $uid = $pdo->lastInsertId();
    echo "✅ Created new demo account (id=$uid).\n";
}

// Create a business profile for demo account
$bp = $pdo->prepare("SELECT id FROM business_profiles WHERE user_id=?");
$bp->execute([$uid]);
if (!$bp->fetchColumn()) {
    $pdo->prepare("INSERT INTO business_profiles (user_id, business_name, tagline, description, category, city, created_at)
                   VALUES (?, 'Demo Business', 'Quality products & services', 'This is a demo account for BizNexus platform review.', 'Technology', 'Hyderabad', NOW())")
        ->execute([$uid]);
    echo "✅ Business profile created.\n";
}

echo "\n";
echo "=================================================\n";
echo "  RAZORPAY TEST CREDENTIALS FOR BIZNEXUS\n";
echo "=================================================\n";
echo "Website : https://biznexus.in\n";
echo "Login URL: https://biznexus.in/auth/login.php\n";
echo "Email   : demo@biznexus.in\n";
echo "Password: Demo@1234\n";
echo "=================================================\n";
echo "Role    : Premium Member\n";
echo "Status  : Active + Email Verified\n";
echo "=================================================\n";
?>
