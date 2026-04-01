<?php
require_once __DIR__ . '/../includes/db.php';

// Fetch users in Global Pool (no group_id)
$stmt = $pdo->query("SELECT u.id, bp.city FROM users u JOIN business_profiles bp ON u.id = bp.user_id WHERE u.group_id IS NULL AND u.status = 'active'");
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Processing " . count($pending_users) . " users for auto-join...\n";

foreach ($pending_users as $user) {
    $uid = $user['id'];
    $city = trim($user['city']);
    
    if (!$city) {
        echo "User $uid: No city in profile. Skipping.\n";
        continue;
    }

    // Try to find a Nexus group for this city
    $gStmt = $pdo->prepare("SELECT id FROM groups WHERE city LIKE ? AND is_active_group = 1 ORDER BY id ASC LIMIT 1");
    $gStmt->execute(["%$city%"]);
    $group_id = $gStmt->fetchColumn();

    if ($group_id) {
        // Assign to group
        $pdo->prepare("UPDATE users SET group_id = ?, group_role = 'member' WHERE id = ?")->execute([$group_id, $uid]);
        echo "User $uid: Joined group ID $group_id ($city).\n";
    } else {
        // Fallback: Assign to 'Global Nexus' (ID 1 is usually Hyderabad Nexus, maybe I should create a catch-all)
        // For now, if no city group, we'll keep them in pool until admin assigns.
        echo "User $uid: No existing group for '$city'. Pending admin creation.\n";
    }
}

echo "Auto-join complete.\n";
?>
