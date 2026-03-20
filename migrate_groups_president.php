<?php
// Migration: Add president columns to groups table
require_once 'includes/db.php';

$sqls = [
    "ALTER TABLE groups ADD COLUMN IF NOT EXISTS president_user_id INT NULL DEFAULT NULL",
    "ALTER TABLE groups ADD COLUMN IF NOT EXISTS term_started_at DATETIME NULL DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS group_role VARCHAR(50) NULL DEFAULT 'member'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS group_id INT NULL DEFAULT NULL",
];

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } catch (Exception $e) {
        echo "SKIP/ERR: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. groups schema:\n";
$cols = $pdo->query("DESCRIBE groups")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\nusers group columns:\n";
$ucols = $pdo->query("SHOW COLUMNS FROM users LIKE 'group%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ucols as $c) echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
?>
