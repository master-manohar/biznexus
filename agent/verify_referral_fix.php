<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

echo "VERIFYING REFERRAL SUBMISSION...\n";

// 1. Check if categories exist
$count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
echo "Categories in DB: $count\n";

// 2. Simulate insertion of an "Open Pool" referral (receiver_id = NULL)
try {
    $sender_id = 1; // Assuming admin or test user exists
    $stmt = $pdo->prepare("INSERT INTO referrals (sender_id, receiver_id, category, referred_name, phone, status, created_at) 
                           VALUES (?, NULL, 'IT Services', 'Test Prospect', '1234567890', 'sent', NOW())");
    $stmt->execute([$sender_id]);
    $rid = $pdo->lastInsertId();
    echo "SUCCESS: Inserted Test Referral ID $rid with NULL receiver_id.\n";
    
    // Cleanup
    $pdo->prepare("DELETE FROM referrals WHERE id = ?")->execute([$rid]);
    echo "Cleanup complete.\n";
} catch (Exception $e) {
    echo "ERROR during insertion: " . $e->getMessage() . "\n";
}
