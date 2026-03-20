<?php
// /admin_setup.php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("SELECT email, role FROM users WHERE role='admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Hash password
    $hash = password_hash('BizNexus@2026', PASSWORD_DEFAULT);
    
    if($admin) {
        // Force password reset to BizNexus@2026 for CEO access
        $pdo->query("UPDATE users SET password='$hash' WHERE email='{$admin['email']}'");
        try { $pdo->query("UPDATE users SET is_verified=1 WHERE email='{$admin['email']}'"); } catch(Exception $e) {}
        echo "<h1>Super Admin Access Details</h1>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p><strong>Password:</strong> BizNexus@2026</p>";
        echo "<p><a href='/auth/login.php'>Click here to login</a></p>";
    } else {
        // Create an admin if none exists
        $pdo->query("INSERT INTO users (name, business_name, email, phone, password, role, status) 
                     VALUES ('CEO', 'BizNexus HQ', 'ceo@biznexus.in', '9999999999', '$hash', 'admin', 'active')");
        // Apply verification safely after insert
        try { $pdo->query("UPDATE users SET is_verified=1 WHERE email='ceo@biznexus.in'"); } catch(Exception $e) {}
                     
        echo "<h1>Super Admin Created Successfully</h1>";
        echo "<p><strong>Email:</strong> ceo@biznexus.in</p>";
        echo "<p><strong>Password:</strong> BizNexus@2026</p>";
        echo "<p><a href='/auth/login.php'>Click here to login</a></p>";
    }
} catch(Exception $e){
    echo "<h2 style='color:red;'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
