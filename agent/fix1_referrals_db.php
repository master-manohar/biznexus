<?php
// /agent/fix1_referrals_db.php
// Run this file once by visiting agent.biznexus.in/fix1_referrals_db.php?key=BizCron2024 to add missing columns to the referrals table

if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized access.");
}

require_once '../includes/db.php';

try {
    global $pdo;
    if (!$pdo) {
        die("Failed to connect to the database.");
    }

    echo "<h3>BizNexus - Fix #1: Migrating Referrals Table</h3>";

    $queries = [
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS referred_name VARCHAR(200) DEFAULT ''",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS referred_phone VARCHAR(20) DEFAULT ''",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS referred_email VARCHAR(200) DEFAULT ''",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS referred_business_type VARCHAR(100) DEFAULT ''",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS notes TEXT",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS estimated_value INT DEFAULT 0",
        "ALTER TABLE referrals ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'sent'"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "<p>✅ Executed: $sql</p>";
    }

    echo "<h4>All referral columns added successfully!</h4>";
} catch (Exception $e) {
    echo "<h4>Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
