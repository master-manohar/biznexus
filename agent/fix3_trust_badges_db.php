<?php
// /agent/fix3_trust_badges_db.php
// Run this file once by visiting agent.biznexus.in/fix3_trust_badges_db.php?key=BizCron2024 to add the Trust Badge column

if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    die("Unauthorized.");
}

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        echo "✅ Added `is_verified` column to `users` table.<br>";
    } else {
        echo "ℹ️ `is_verified` column already exists.<br>";
    }

    echo "<h3>Trust Badge DB Migration Complete!</h3>";
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
