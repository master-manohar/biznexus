<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Correct include path for database
require_once dirname(__DIR__) . '/includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// === PLATFORM-WIDE ANALYTICS ===
$total_platform_members = 0;
$new_members_week = 0;
$total_platform_leads = 0;
$top_categories = [];

try {
    $total_platform_members = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
    $new_members_week = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Check if public_leads table exists safely
    try {
        $total_platform_leads = $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn();
    } catch (Exception $e) { /* Table might not exist yet */ }

    $top_categories = $pdo->query("SELECT category, COUNT(*) as cnt FROM users WHERE category IS NOT NULL AND category != '' GROUP BY category ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Log error if needed */ }


// === USER-SPECIFIC ANALYTICS ===

// Profile Views (last 30 days)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM profile_views WHERE viewed_id = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$user_id]);
$profile_views_30 = $stmt->fetchColumn() ?: 0;

// Profile Views (last 7 days)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM profile_views WHERE viewed_id = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$user_id]);
$profile_views_7 = $stmt->fetchColumn() ?: 0;

// Total Connections
$stmt = $pdo->prepare("SELECT COUNT(*) FROM connections WHERE (user_id = ? OR connected_id = ?) AND status = 'accepted'");
$stmt->execute([$user_id, $user_id]);
$total_connections = $stmt->fetchColumn() ?: 0;

// New Connections this month
$stmt = $pdo->prepare("SELECT COUNT(*) FROM connections WHERE (user_id = ? OR connected_id = ?) AND status = 'accepted' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
$stmt->execute([$user_id, $user_id]);
$new_connections = $stmt->fetchColumn() ?: 0;

// Meetings scheduled
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND status != 'cancelled'");
$stmt->execute([$user_id, $user_id]);
$total_meetings = $stmt->fetchColumn() ?: 0;

// Meetings this month
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND status != 'cancelled' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
$stmt->execute([$user_id, $user_id]);
$meetings_month = $stmt->fetchColumn() ?: 0;

// Referrals sent
$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
$stmt->execute([$user_id]);
$referrals_sent = $stmt->fetchColumn() ?: 0;

// Referrals converted
$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'converted'");
$stmt->execute([$user_id]);
$referrals_converted = $stmt->fetchColumn() ?: 0;

// Coin balance
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM coin_transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $coin_balance = $stmt->fetchColumn() ?: 0;
} catch(Exception $e) { $coin_balance = 0; }

// CRM leads
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM crm_leads WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_leads = $stmt->fetchColumn() ?: 0;
} catch(Exception $e) { $total_leads = 0; }

// Engagement score calculation
$engagement_score = min(100, round(
    ($profile_views_30 * 0.3) +
    ($total_connections * 0.2) +
    ($total_meetings * 2) +
    ($referrals_sent * 1.5)
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --gold: #FFD700;
            --green: #00ff88;
            --bg: #0a0a0f;
            --card: #13131a;
            --border: rgba(255,215,0,0.15);
            --text-muted: #8888aa;
        }
        body { background: var(--bg); color: #fff; font-family: 'Segoe UI', sans-serif; }
        .main-content { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .stat-card { text-align: center; padding: 20px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); }
        .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--gold); }
        .stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-top: 5px; }
        .logo-text { font-size: 1.5rem; font-weight: 800; color: var(--gold); text-decoration: none; display: block; margin-bottom: 20px; }
        .logo-text span { color: var(--green); }
        .header-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; border-left: 4px solid var(--gold); padding-left: 15px; }
        .category-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .category-name { font-size: 0.9rem; }
        .category-cnt { font-weight: 700; color: var(--green); }
    </style>
</head>
<body>

<div class="main-content">
    <a href="/" class="logo-text">Biz<span>Nexus</span> Analytics</a>

    <!-- PLATFORM OVERVIEW -->
    <div class="header-title">Platform Performance</div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($total_platform_members) ?></div>
                <div class="stat-label">Total Members</div>
                <div style="font-size:0.7rem; color:var(--green); margin-top:5px;">+<?= $new_members_week ?> this week</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($total_platform_leads) ?></div>
                <div class="stat-label">Platform Leads</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" style="margin-bottom:0; padding:15px 24px;">
                <div style="font-size:0.8rem; font-weight:700; margin-bottom:10px; color:var(--text-muted);">TOP BUSINESS CATEGORIES</div>
                <div class="row">
                    <?php foreach($top_categories as $cat): ?>
                    <div class="col-6">
                        <div class="category-item">
                            <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                            <span class="category-cnt"><?= $cat['cnt'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- USER SPECIFIC -->
    <div class="header-title">Your Engagement Score: <?= $engagement_score ?>/100</div>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($profile_views_30) ?></div>
                <div class="stat-label">Profile Views (30d)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($total_connections) ?></div>
                <div class="stat-label">Connections</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($total_meetings) ?></div>
                <div class="stat-label">Meetings Scheduled</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($referrals_sent) ?></div>
                <div class="stat-label">Referrals Given</div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="/dashboard/index.php" class="btn btn-outline-warning btn-sm">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>