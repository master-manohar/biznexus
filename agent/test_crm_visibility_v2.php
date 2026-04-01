<?php
require_once __DIR__ . '/../includes/db.php';

function testFilter($pdo, $uid, $is_admin = false) {
    echo "--- Testing User ID: $uid ---\n";
    
    // 1. Fetch user category
    $stmt = $pdo->prepare("SELECT bp.category, u.role FROM users u JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_cat = $user['category'] ?? '';
    echo "User Industry: $user_cat\n";

    // 2. Simulated Filter Logic from leads/list.php
    $where = ["1=1"]; $params = [];
    if (!$is_admin && $user_cat) {
        $where[] = "category = ?";
        $params[] = $user_cat;
    }
    
    $wStr = implode(' AND ', $where);
    $sql = "SELECT id, category, status FROM public_leads WHERE (claimed_by_member_id IS NULL OR status IN ('new','open')) AND $wStr LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Visible Leads:\n";
    if (empty($leads)) echo "NONE (Correct if no leads in $user_cat)\n";
    foreach($leads as $l) {
        echo "ID:{$l['id']} Cat:{$l['category']}\n";
    }
    echo "\n";
}

// User 7 (Healthcare)
testFilter($pdo, 7);

// User 1 (Dance) - I'll check what his category is first
$stmt = $pdo->prepare("SELECT category FROM business_profiles WHERE user_id = 1");
$stmt->execute();
$cat1 = $stmt->fetchColumn();
testFilter($pdo, 1);

// Admin bypass test (User 7 but as admin)
testFilter($pdo, 7, true);
?>
