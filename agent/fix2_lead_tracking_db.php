<?php
// /agent/fix2_lead_tracking_db.php
// Run this file once by visiting agent.biznexus.in/fix2_lead_tracking_db.php?key=BizCron2024 to create lead tracking tables

if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized access.");
}

require_once '../includes/db.php';

try {
    global $pdo;
    if (!$pdo) {
        die("Failed to connect to the database.");
    }

    echo "<h3>BizNexus - Fix #2: Migrating Lead Tracking Tables</h3>";

    // 1. Create lead_dispatches table
    $sql1 = "
    CREATE TABLE IF NOT EXISTS lead_dispatches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        member_id INT NOT NULL,
        member_name VARCHAR(200) NOT NULL,
        business_name VARCHAR(200) NOT NULL,
        category VARCHAR(100) NOT NULL,
        city VARCHAR(100) NOT NULL,
        whatsapp VARCHAR(20) NOT NULL,
        dispatch_rank INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'dispatched',
        notified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(lead_id),
        INDEX(member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql1);
    echo "<p>✅ Executed: Created lead_dispatches table</p>";

    // 2. Add new columns to public_leads if they don't exist
    $queries = [
        "ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'new'",
        "ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS total_members_notified INT DEFAULT 0",
        "ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS claimed_by_member_id INT DEFAULT NULL"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "<p>✅ Executed: $sql</p>";
    }

    echo "<h4>Lead Tracking DB update completed successfully!</h4>";
} catch (Exception $e) {
    echo "<h4>Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
