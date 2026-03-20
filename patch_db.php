<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN sender_id INT");
    echo "Added sender_id\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN receiver_id INT");
    echo "Added receiver_id\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN referred_name VARCHAR(255)");
    echo "Added referred_name\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN phone VARCHAR(50)");
    echo "Added phone\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN email VARCHAR(255)");
    echo "Added email\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN referred_business_type VARCHAR(100)");
    echo "Added referred_business_type\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN notes TEXT");
    echo "Added notes\n";
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN estimated_value DECIMAL(10,2)");
    echo "Added estimated_value\n";
} catch(Exception $e) {}

echo "Referrals table patched.\n";

// Let's also check if leads table exists, if not create it
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT,
        to_user_id INT,
        title VARCHAR(255),
        description TEXT,
        contact_name VARCHAR(255),
        contact_phone VARCHAR(50),
        contact_email VARCHAR(255),
        estimated_value DECIMAL(10,2),
        category VARCHAR(100),
        city VARCHAR(100),
        status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Leads table verified.\n";
} catch(Exception $e) {
    echo "Leads error: " . $e->getMessage() . "\n";
}

?>
