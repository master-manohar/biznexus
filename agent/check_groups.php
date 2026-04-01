<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- GROUPS ---\n";
$groups = $pdo->query("SELECT id, name, city, tier, badge_icon FROM groups LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
print_r($groups);

echo "\n--- USERS IN GLOBAL POOL (No Group) ---\n";
$users = $pdo->query("SELECT u.id, u.name, bp.city, bp.category FROM users u LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.group_id IS NULL AND u.status='active' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>
