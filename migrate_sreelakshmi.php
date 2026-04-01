<?php
require_once __DIR__ . '/includes/db.php';
$pdo->beginTransaction();
try {
    // 1. Create New Group
    $stmt = $pdo->prepare("INSERT INTO groups (name, tier, max_members, is_active, is_active_group, created_at) VALUES (?, 'Elite', 50, 1, 1, NOW())");
    $stmt->execute(['BizNexus Elite - Sreelakshmi']);
    $groupId = $pdo->lastInsertId();

    // 2. Promote Sreelakshmi (ID 2913)
    $stmt = $pdo->prepare("UPDATE users SET role = 'moderator', group_id = ?, group_role = 'president' WHERE id = 2913");
    $stmt->execute([$groupId]);

    $stmt = $pdo->prepare("UPDATE groups SET president_user_id = ?, term_started_at = NOW() WHERE id = ?");
    $stmt->execute([2913, $groupId]);

    // 3. Move Recent Members (IDs 7399 to 7420) to her group
    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE id >= 7399 AND id <= 7420 AND category IS NOT NULL");
    $stmt->execute([$groupId]);

    $pdo->commit();
    echo "SUCCESS: Group created ($groupId), Sreelakshmi promoted, and members migrated.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}
