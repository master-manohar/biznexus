<?php
require_once __DIR__ . '/../includes/db.php';

$migrations = [
    // 1. Expand group_role in users table
    "ALTER TABLE users MODIFY COLUMN group_role VARCHAR(50) DEFAULT 'member'",
    
    // 2. Create user_agent_state table for Master-Sub architecture
    "CREATE TABLE IF NOT EXISTS user_agent_state (
        user_id INT PRIMARY KEY,
        current_stage VARCHAR(50) DEFAULT 'onboarding_start',
        last_interaction_at DATETIME DEFAULT NULL,
        next_followup_at DATETIME DEFAULT NULL,
        agent_personality TEXT,
        agent_notes TEXT,
        is_pro_active TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // 3. Create agent_interactions table for logging
    "CREATE TABLE IF NOT EXISTS agent_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        interaction_type VARCHAR(50), -- 'email', 'whatsapp', 'notif'
        content TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )",

    // 4. Ensure business_profiles has needed fields for agent personality
    "ALTER TABLE business_profiles ADD COLUMN IF NOT EXISTS about_details TEXT",
    "ALTER TABLE business_profiles ADD COLUMN IF NOT EXISTS target_audience TEXT"
];

echo "--- Running Phase 3 Migrations ---\n";
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . substr($sql, 0, 60) . "...\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Migrations finished.\n";
?>
