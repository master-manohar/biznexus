<?php
require_once __DIR__ . '/../includes/db.php';

$migrations = [
    // Update group_members role ENUM
    "ALTER TABLE group_members MODIFY COLUMN role ENUM('admin', 'member', 'pending', 'president', 'vice_president', 'treasurer', 'secretary') DEFAULT 'pending'"
];

echo "--- Running Group Role Migrations ---\n";
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: $sql\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
?>
