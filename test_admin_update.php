<?php
require_once __DIR__ . '/includes/db.php';
// Simulate an admin action to change user 7 to 'platinum'
$target_id = 7;
$new_plan = 'platinum';

// We'll just do it via PHP to verify DB access, but the UI logic in superadmin.php also does exactly this
$stmt = $pdo->prepare("UPDATE users SET plan = ? WHERE id = ?");
if ($stmt->execute([$new_plan, $target_id])) {
    echo "SUCCESS: User $target_id updated to $new_plan.\n";
} else {
    echo "ERROR: Failed to update user $target_id.\n";
}

$check = $pdo->prepare("SELECT plan FROM users WHERE id = ?");
$check->execute([$target_id]);
echo "Current Plan for User $target_id: " . $check->fetchColumn() . "\n";
?>
