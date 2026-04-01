<?php
require_once __DIR__ . '/includes/db.php';

try {
    // 1. Update public_leads table
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS ai_strategy TEXT");
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS lat DECIMAL(10,8)");
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS lng DECIMAL(11,8)");
    
    // 2. Update referrals table
    $pdo->exec("ALTER TABLE referrals ADD COLUMN IF NOT EXISTS ai_strategy TEXT");
    
    // 3. Update users table
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lat DECIMAL(10,8)");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS lng DECIMAL(11,8)");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_lead_at DATETIME");
    
    echo "Database schema updated successfully for Lead Mover AI Agent.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
