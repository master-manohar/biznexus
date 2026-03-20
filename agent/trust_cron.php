<?php
// /agent/trust_cron.php
require_once dirname(__DIR__) . '/includes/db.php';

$stmt = $pdo->query("SELECT u.id, u.is_verified, u.plan, bp.description, bp.logo, bp.website, bp.city FROM users u LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.status = 'active'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
foreach($users as $user) {
    $score = 0;
    
    // 1. Profile Completeness (max 20)
    if (!empty($user['description'])) $score += 10;
    if (!empty($user['city'])) $score += 5;
    if (!empty($user['website']) || !empty($user['logo'])) $score += 5;
    
    // 2. KYC / Verification (max 25)
    if ($user['is_verified']) $score += 25;
    
    // 3. Paid Plan (max 15)
    if (in_array($user['plan'], ['silver', 'gold', 'platinum'])) $score += 15;
    if ($user['plan'] === 'platinum') $score += 5; // Bonus
    
    // 4. Activity / Claims (up to 20)
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM lead_dispatches WHERE member_id = ? AND status IN ('claimed', 'contacted')");
    $stmtC->execute([$user['id']]);
    $claims = (int)$stmtC->fetchColumn();
    $score += min(20, $claims * 5); // 5 points per successful claim, max 20
    
    // 5. Ratings Baseline (20 pts)
    $score += 20; 
    
    // Cap at 100
    if ($score > 100) $score = 100;
    
    // Badge Logic
    $badge = null;
    if ($score >= 80) $badge = 'diamond';
    elseif ($score >= 60) $badge = 'gold';
    elseif ($score >= 40) $badge = 'blue';

    // Update User
    $uStmt = $pdo->prepare("UPDATE users SET trust_score = ?, trust_badge = ? WHERE id = ?");
    $uStmt->execute([$score, $badge, $user['id']]);
    $updated++;
}

echo "Trust Score Engine execution complete.\nEvaluated and updated $updated member Trust Profiles.";
?>
