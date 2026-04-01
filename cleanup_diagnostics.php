<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Flexible DB connection
if (file_exists(__DIR__ . '/includes/db.php')) {
    require_once __DIR__ . '/includes/db.php';
} elseif (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} else {
    die("DB connection file not found.");
}

if (($_GET['key'] ?? '') !== 'BizCron2024') {
    die("Access Denied.");
}

echo "--- BizNexus Cleanup & Growth Report ---\n";
echo "DB Connected: " . (isset($pdo) ? 'YES' : 'NO') . "\n\n";

// 1. Delete Demo Accounts
try {
    // Search for demo accounts
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE name LIKE '%demo%' OR email LIKE '%demo%'");
    $stmt->execute();
    $demos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deletedCount = 0;
    foreach ($demos as $d) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$d['id']]);
        $deletedCount++;
    }
    echo "Total demo accounts deleted: $deletedCount\n";
} catch (Exception $e) {
    echo "Cleanup Error: " . $e->getMessage() . "\n";
}

// 2. Growth Report (Past 24 Hours)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $newCount = $stmt->fetchColumn();
    echo "New accounts created in the past 24 hours: $newCount\n";
} catch (Exception $e) {
    echo "Growth Report Error: " . $e->getMessage() . "\n";
}
echo "\nDone.";
