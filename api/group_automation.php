<?php
/**
 * BizNexus Group Automation Engine
 * 
 * PURPOSE: Automates group membership management:
 *   1. Auto-assign members to groups based on active/inactive status
 *   2. Rotate group presidents every 90 days
 *   3. Send notifications to outgoing/incoming presidents
 * 
 * USAGE: Run via cron job (weekly/monthly): php api/group_automation.php
 * OR trigger manually from superadmin.php
 */

// CLI or HTTP: set correct path
$root = dirname(__DIR__);

if (file_exists($root . '/includes/db.php')) {
    require_once $root . '/includes/db.php';
} else {
    require_once $root . '/db.php';
}

if (!isset($pdo)) die("DB connection failed.\n");

$log = [];
$log[] = "[" . date('Y-m-d H:i:s') . "] Group Automation Started";

// ============================================================
// STEP 1: Move inactive members to inactive group(s)
// ============================================================
try {
    // Find or create an "Inactive Pool" group
    $inactiveGroup = $pdo->query("SELECT id FROM groups WHERE is_active_group = 0 LIMIT 1")->fetch();
    if (!$inactiveGroup) {
        $pdo->exec("INSERT INTO groups (name, tier, is_active_group) VALUES ('Inactive Pool', 'inactive', 0)");
        $inactiveGroupId = $pdo->lastInsertId();
    } else {
        $inactiveGroupId = $inactiveGroup['id'];
    }

    // Find active groups for active member assignment
    $activeGroups = $pdo->query("SELECT id FROM groups WHERE is_active_group = 1")->fetchAll(PDO::FETCH_COLUMN);

    // Move inactive users to inactive group
    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE status = 'inactive'");
    $stmt->execute([$inactiveGroupId]);
    $log[] = "Moved " . $stmt->rowCount() . " inactive members to Inactive Pool";

    // Assign active members without a group to first active group
    if (!empty($activeGroups)) {
        $firstGroup = $activeGroups[0];
        $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE status = 'active' AND (group_id IS NULL OR group_id = ?)");
        $stmt->execute([$firstGroup, $inactiveGroupId]);
        $log[] = "Moved " . $stmt->rowCount() . " newly active members to Group #{$firstGroup}";
    }
} catch (Exception $e) {
    $log[] = "ERROR in member assignment: " . $e->getMessage();
}

// ============================================================
// STEP 2: Rotate group presidents (every 90 days)
// ============================================================
try {
    // Check groups where president term has expired (> 90 days)
    $expiredGroups = $pdo->query("
        SELECT g.id AS group_id, g.name AS group_name, g.president_user_id, g.term_started_at
        FROM groups g
        WHERE g.is_active_group = 1
          AND g.president_user_id IS NOT NULL
          AND g.term_started_at IS NOT NULL
          AND DATEDIFF(NOW(), g.term_started_at) >= 90
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expiredGroups as $grp) {
        $gid  = $grp['group_id'];
        $oldPresident = $grp['president_user_id'];

        // Find next eligible member in this group (oldest active member who hasn't been president recently)
        $nextPres = $pdo->prepare("
            SELECT u.id FROM users u
            WHERE u.group_id = ?
              AND u.status = 'active'
              AND u.id != ?
            ORDER BY u.created_at ASC
            LIMIT 1
        ");
        $nextPres->execute([$gid, $oldPresident]);
        $newPresidentId = $nextPres->fetchColumn();

        if (!$newPresidentId) {
            $log[] = "Group #{$gid} ({$grp['group_name']}): No eligible next president found, skipping.";
            continue;
        }

        // Update group with new president
        $pdo->prepare("UPDATE groups SET president_user_id = ?, term_started_at = NOW() WHERE id = ?")
            ->execute([$newPresidentId, $gid]);

        // Remove old role from outgoing president
        $pdo->prepare("UPDATE users SET group_role = 'member' WHERE id = ? AND group_id = ?")
            ->execute([$oldPresident, $gid]);

        // Assign president role to new president
        $pdo->prepare("UPDATE users SET group_role = 'president' WHERE id = ?")
            ->execute([$newPresidentId]);

        // Notify outgoing president
        $notifTitle = "Your Presidency Has Ended";
        $notifMsg   = "Thank you for serving as President of {$grp['group_name']}! Your 90-day term has concluded.";
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?,?,?,'group',NOW())")
            ->execute([$oldPresident, $notifTitle, $notifMsg]);

        // Notify incoming president
        $newPres = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $newPres->execute([$newPresidentId]);
        $newPresName = $newPres->fetchColumn();

        $notifTitleNew = "Congratulations! You Are Now President";
        $notifMsgNew   = "You have been elected as the new President of '{$grp['group_name']}'. Your term lasts 90 days. Lead well! 🏆";
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?,?,?,'group',NOW())")
            ->execute([$newPresidentId, $notifTitleNew, $notifMsgNew]);

        $log[] = "Group #{$gid} ({$grp['group_name']}): Rotated president from user#{$oldPresident} to user#{$newPresidentId} ({$newPresName})";
    }

    if (empty($expiredGroups)) {
        $log[] = "No groups with expired president terms found.";
    }
} catch (Exception $e) {
    $log[] = "ERROR in president rotation: " . $e->getMessage();
}

// ============================================================
// STEP 3: Balance groups (100 members per group cap)
// ============================================================
try {
    $MEMBERS_PER_GROUP = 100;

    $overloadedGroups = $pdo->query("
        SELECT g.id, g.name, COUNT(u.id) AS member_count
        FROM groups g
        LEFT JOIN users u ON u.group_id = g.id AND u.status = 'active'
        WHERE g.is_active_group = 1
        GROUP BY g.id
        HAVING member_count > {$MEMBERS_PER_GROUP}
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($overloadedGroups as $og) {
        // Find or create a new active group
        $newGrp = $pdo->query("
            SELECT g.id FROM groups g
            LEFT JOIN users u ON u.group_id = g.id
            WHERE g.is_active_group = 1
            GROUP BY g.id
            HAVING COUNT(u.id) < {$MEMBERS_PER_GROUP}
            LIMIT 1
        ")->fetch();

        if (!$newGrp) {
            // Create a new group auto-named
            $newGroupName = "BizNexus Circle " . rand(100, 999);
            $pdo->prepare("INSERT INTO groups (name, tier, is_active_group, term_started_at) VALUES (?, 'active', 1, NOW())")
                ->execute([$newGroupName]);
            $newGrpId = $pdo->lastInsertId();
        } else {
            $newGrpId = $newGrp['id'];
        }

        // Move excess members to new group
        // Note: MariaDB doesn't like parameterized LIMIT in UPDATE, use subquery approach
        $excess = $og['member_count'] - $MEMBERS_PER_GROUP;
        // Fetch the IDs of members to move
        $fetchExcess = $pdo->prepare("
            SELECT id FROM users WHERE group_id = ? AND status = 'active' AND group_role = 'member'
            LIMIT " . (int)$excess
        );
        $fetchExcess->execute([$og['id']]);
        $excessIds = $fetchExcess->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($excessIds)) {
            $placeholders = implode(',', array_fill(0, count($excessIds), '?'));
            $args = array_merge([$newGrpId], $excessIds);
            $pdo->prepare("UPDATE users SET group_id = ? WHERE id IN ($placeholders)")->execute($args);
        }
        $log[] = "Balanced Group #{$og['id']} ({$og['name']}): moved " . count($excessIds) . " members to Group #{$newGrpId}";
    }

    if (empty($overloadedGroups)) {
        $log[] = "All groups are within the {$MEMBERS_PER_GROUP}-member limit.";
    }
} catch (Exception $e) {
    $log[] = "ERROR in group balancing: " . $e->getMessage();
}

$log[] = "[" . date('Y-m-d H:i:s') . "] Group Automation Completed";

// Output log
if (php_sapi_name() === 'cli') {
    echo implode("\n", $log) . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'log' => $log]);
}
?>
