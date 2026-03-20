<?php
// /agent/fix4_phase3_db.php
// Run this file via browser to execute Phase 3 Database expansions
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized.");
}

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Starting Phase 3 Migration...<br>";

    // 1. ALTER referrals
    try {
        $pdo->exec("ALTER TABLE referrals 
                    ADD COLUMN referred_name VARCHAR(100) DEFAULT NULL,
                    ADD COLUMN phone VARCHAR(20) DEFAULT NULL,
                    ADD COLUMN email VARCHAR(100) DEFAULT NULL");
        echo "✅ ALTER TABLE referrals successful.<br>";
    } catch (Exception $e) {
        echo "ℹ️ referrals ALTER skipped (already exists or error: " . $e->getMessage() . ")<br>";
    }
    
    // 2. CREATE coin_escrow
    $pdo->exec("CREATE TABLE IF NOT EXISTS coin_escrow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount INT NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('pending', 'released', 'cancelled') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ CREATE TABLE coin_escrow successful.<br>";

    // 3. CREATE rate_limits
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        action VARCHAR(50) NOT NULL,
        request_count INT DEFAULT 1,
        reset_time DATETIME NOT NULL,
        UNIQUE KEY ip_action (ip_address, action)
    )");
    echo "✅ CREATE TABLE rate_limits successful.<br>";

    // 4. CREATE lead_whatsapp_queue
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_whatsapp_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        member_id INT NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ CREATE TABLE lead_whatsapp_queue successful.<br>";

    echo "<h3>Phase 3 Database Migration Complete!</h3>";
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
