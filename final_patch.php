<?php
require_once 'includes/db.php';
try {
    // 1. Ensure voocoin_balances exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS voocoin_balances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        balance INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 2. Ensure coin_transactions exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS coin_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount INT NOT NULL,
        balance_after INT DEFAULT 0,
        type ENUM('earn', 'spend') NOT NULL,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Ensure referrals table columns match my code (phone, email)
    // Check if referred_phone exists and rename it to phone if needed
    $res = $pdo->query("SHOW COLUMNS FROM referrals LIKE 'referred_phone'");
    if ($res->fetch()) {
        $pdo->exec("ALTER TABLE referrals CHANGE COLUMN referred_phone phone VARCHAR(20)");
    }
    $res = $pdo->query("SHOW COLUMNS FROM referrals LIKE 'referred_email'");
    if ($res->fetch()) {
        $pdo->exec("ALTER TABLE referrals CHANGE COLUMN referred_email email VARCHAR(100)");
    }
    
    // Add phone/email if they are completely missing
    $cols = $pdo->query("SHOW COLUMNS FROM referrals")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('phone', $cols)) $pdo->exec("ALTER TABLE referrals ADD COLUMN phone VARCHAR(20)");
    if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE referrals ADD COLUMN email VARCHAR(100)");

    // 4. Ensure users table has status column (used is_active in old files)
    if (!in_array('status', $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN))) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
    }

    echo "Final Database Standardization Complete.\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
