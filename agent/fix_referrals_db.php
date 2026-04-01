<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

echo "STARTING REFERRALS DB FIX...\n";

try {
    // 1. Alter table to allow NULL for receiver_id
    $pdo->exec("ALTER TABLE referrals MODIFY receiver_id INT NULL");
    echo "ALTER TABLE SUCCESS: receiver_id is now nullable.\n";

    // 2. Set any existing 0 values to NULL to avoid constraint issues if we re-enable or similar
    // Actually, if we already have 0s and a FK, it shouldn't have been possible.
    // The previous error showed it WAS failing.
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
