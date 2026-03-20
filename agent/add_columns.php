<?php
require_once dirname(__DIR__) . '/includes/db.php';
try {
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS claimed_count INT DEFAULT 0");
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS locked_at DATETIME DEFAULT NULL");
    
    // Make sure lead_dispatches has slot_number and status
    $pdo->exec("ALTER TABLE lead_dispatches ADD COLUMN IF NOT EXISTS slot_number INT DEFAULT 0");
    // ld.status already exists? 
    // "ld.status ENUM('pending', 'claimed', 'failed', 'dispatched')"
    
    echo "Columns added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
