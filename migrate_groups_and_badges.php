<?php
require_once 'includes/db.php';
global $pdo;

$migrations = [
    // Update groups table
    "ALTER TABLE groups ADD COLUMN IF NOT EXISTS is_active_group TINYINT(1) DEFAULT 1",
    "ALTER TABLE groups ADD COLUMN IF NOT EXISTS term_started_at DATETIME DEFAULT NULL",
    "ALTER TABLE groups ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL",

    // Update users table for activity tracking
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_active_at DATETIME DEFAULT CURRENT_TIMESTAMP",

    // Create member_badges table
    "CREATE TABLE IF NOT EXISTS member_badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        badge_type VARCHAR(50) NOT NULL,
        label VARCHAR(100) NOT NULL,
        icon VARCHAR(255) DEFAULT '',
        awarded_by INT DEFAULT NULL,
        awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )",

    // Create group_roles table
    "CREATE TABLE IF NOT EXISTS group_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('president', 'vice_president', 'member') DEFAULT 'member',
        expires_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (group_id),
        INDEX (user_id),
        UNIQUE (group_id, role, expires_at)
    )"
];

echo "--- Running Migrations ---\n";
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
