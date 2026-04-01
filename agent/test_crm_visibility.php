<?php
require_once __DIR__ . '/../includes/db.php';

// Test Case: User 7 (Manohar) is likely 'Healthcare' or similar
$uid = 7;
$stmt = $pdo->prepare("SELECT category FROM business_profiles WHERE user_id = ?");
$stmt->execute([$uid]);
$user_cat = $stmt->fetchColumn();
echo "User Category: $user_cat\n";

// Fetch leads user 7 would see in 'New Opportunities'
$stmt = $pdo->prepare("SELECT id, name, category, status FROM public_leads WHERE (claimed_by_member_id IS NULL OR status IN ('new','open')) LIMIT 5");
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Leads visible in Opportunities:\n";
foreach($leads as $l) {
    echo "ID:{$l['id']} Cat:{$l['category']} Status:{$l['status']}\n";
}
