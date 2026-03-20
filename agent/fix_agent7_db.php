<?php
require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Add WhatsApp Queue Columns
    $pdo->exec("ALTER TABLE lead_whatsapp_queue ADD COLUMN IF NOT EXISTS member_phone VARCHAR(20) DEFAULT NULL");
    $pdo->exec("ALTER TABLE lead_whatsapp_queue ADD COLUMN IF NOT EXISTS message_text TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lead_whatsapp_queue ADD COLUMN IF NOT EXISTS wa_link TEXT DEFAULT NULL");

    // Add Trust Score Columns to users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS trust_score INT DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS trust_badge ENUM('diamond', 'gold', 'blue') DEFAULT NULL");
    
    // Add KYC Status to users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_status ENUM('none', 'pending', 'verified', 'rejected') DEFAULT 'none'");

    echo "Agent 6 & 7 Database schema updated successfully.";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
