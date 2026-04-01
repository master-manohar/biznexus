<?php
require_once __DIR__ . '/includes/db.php';
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('member', 'admin', 'moderator') DEFAULT 'member'");
    echo "SUCCESS: Role enum updated.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
