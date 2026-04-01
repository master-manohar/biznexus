<?php
require_once __DIR__ . '/includes/db.php';
$pdo->beginTransaction();
try {
    // 1. Find all "Elite" or "Sreelakshmi" related groups
    $groups = $pdo->query("SELECT id, name FROM groups WHERE name LIKE '%Elite%' OR name LIKE '%Sreelakshmi%' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($groups) > 0) {
        // Keep the lowest ID one found as the primary
        $primary = $groups[0];
        $primaryId = $primary['id'];
        
        echo "Primary Group: " . $primary['name'] . " ($primaryId)\n";
        
        // Rename it
        $pdo->prepare("UPDATE groups SET name = 'BizNexus Elite' WHERE id = ?")->execute([$primaryId]);
        
        // Move all users from the other groups to this one
        foreach ($groups as $idx => $g) {
            if ($idx === 0) continue;
            echo "Consolidating group " . $g['id'] . " (" . $g['name'] . ") into $primaryId\n";
            $pdo->prepare("UPDATE users SET group_id = ? WHERE group_id = ?")->execute([$primaryId, $g['id']]);
            $pdo->prepare("DELETE FROM groups WHERE id = ?")->execute([$g['id']]);
        }
    }
    
    $pdo->commit();
    echo "SUCCESS: Groups consolidated and renamed to 'BizNexus Elite'.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}
