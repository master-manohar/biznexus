<?php
require_once __DIR__ . '/db.php';
global $pdo;

/**
 * Advanced Group Automation for BizNexus
 * - Auto-generates groups per category.
 * - Separates active/inactive members.
 * - Rotates Presidents every 90 days.
 */

function runGroupAutomation($pdo) {
    echo "Starting Group Automation...\n";

    // 1. Get all unique categories from users
    $stmt = $pdo->query("SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($categories as $cat) {
        // Ensure Active and Inactive groups exist for this category
        $active_group_id = getOrCreateGroup($pdo, $cat, true);
        $inactive_group_id = getOrCreateGroup($pdo, $cat, false);

        // 2. Refresh membership based on activity (Active = logged in last 30 days)
        // Move to Active Group
        $pdo->prepare("UPDATE users SET group_id = ? WHERE category = ? AND (last_active_at > DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_active_at IS NULL)")
            ->execute([$active_group_id, $cat]);
        
        // Move to Inactive Group
        $pdo->prepare("UPDATE users SET group_id = ? WHERE category = ? AND last_active_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)")
            ->execute([$inactive_group_id, $cat]);
    }

    // 3. President Rotation (90 days)
    rotatePresidents($pdo);

    echo "Automation complete.\n";
}

function getOrCreateGroup($pdo, $category, $isActiveGroup) {
    $suffix = $isActiveGroup ? "Active" : "Inactive";
    $name = "$category - $suffix";
    
    $stmt = $pdo->prepare("SELECT id FROM groups WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    
    if (!$id) {
        $stmt = $pdo->prepare("INSERT INTO groups (name, category, is_active_group, tier, term_started_at) VALUES (?, ?, ?, 'Nexus', NOW())");
        $stmt->execute([$name, $category, $isActiveGroup ? 1 : 0]);
        $id = $pdo->lastInsertId();
    }
    return $id;
}

function rotatePresidents($pdo) {
    // Find groups needing a new president (term > 90 days or no president)
    $stmt = $pdo->query("SELECT id FROM groups WHERE is_active_group = 1 AND (term_started_at < DATE_SUB(NOW(), INTERVAL 90 DAY) OR term_started_at IS NULL)");
    $groupIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($groupIds as $gid) {
        // Find potential active presidents in this group
        $stmt = $pdo->prepare("SELECT id FROM users WHERE group_id = ? AND status = 'active' ORDER BY RAND() LIMIT 1");
        $stmt->execute([$gid]);
        $newPresId = $stmt->fetchColumn();

        if ($newPresId) {
            // Remove old roles
            $pdo->prepare("DELETE FROM group_roles WHERE group_id = ? AND role = 'president'")->execute([$gid]);
            
            // Assign new president
            $expires = date('Y-m-d H:i:s', strtotime('+90 days'));
            $pdo->prepare("INSERT INTO group_roles (group_id, user_id, role, expires_at) VALUES (?, ?, 'president', ?)")
                ->execute([$gid, $newPresId, $expires]);
            
            // Update group term
            $pdo->prepare("UPDATE groups SET term_started_at = NOW() WHERE id = ?")->execute([$gid]);
            
            // Send notification
            $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'groups', ?)")
                ->execute([$newPresId, "Congratulations! You've been elected as the President of your group for the next 90 days."]);
        }
    }
}

// Check if run from CLI or check_automation flag
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    runGroupAutomation($pdo);
}
?>
