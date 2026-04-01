<?php
$page_title = "Dashboard - BizNexus";
require_once __DIR__ . '/../includes/layout_start.php';

$uid = $_SESSION['user_id'];

// 1. Leads Received
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE to_user_id = ?");
$stmt->execute([$uid]);
$leads_received = $stmt->fetchColumn() ?: 0;

// 2. Leads Given
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE from_user_id = ?");
$stmt->execute([$uid]);
$leads_given = $stmt->fetchColumn() ?: 0;

// 3. Revenue Generated
$stmt = $pdo->prepare("SELECT SUM(estimated_value) FROM leads WHERE (to_user_id = ? OR from_user_id = ?) AND status IN ('accepted', 'closed')");
$stmt->execute([$uid, $uid]);
$revenue = $stmt->fetchColumn() ?: 0;

// 4. Upcoming Meetings (Personal)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND meeting_date >= CURDATE() AND status = 'scheduled'");
$stmt->execute([$uid, $uid]);
$upcoming_meetings = $stmt->fetchColumn() ?: 0;

// 4b. Upcoming GLOBAL Networking Sessions
$stmt = $pdo->prepare("SELECT * FROM networking_meetings WHERE status = 'pending' AND scheduled_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY scheduled_at ASC LIMIT 1");
$stmt->execute();
$active_networking = $stmt->fetch();

// 5. Group Identification
$stmt = $pdo->prepare("SELECT g.*, gr.role as group_role FROM groups g LEFT JOIN group_roles gr ON g.id = gr.group_id AND gr.user_id = ? WHERE g.id = (SELECT group_id FROM users WHERE id = ?)");
$stmt->execute([$uid, $uid]);
$my_group = $stmt->fetch();

// Fetch Badges
$stmt = $pdo->prepare("SELECT * FROM member_badges WHERE user_id = ? ORDER BY awarded_at DESC");
$stmt->execute([$uid]);
$badges = $stmt->fetchAll();

// 6. Recent Market Activity
$stmt = $pdo->prepare("SELECT m.title, m.created_at, u.business_name FROM marketplace m JOIN users u ON m.user_id = u.id WHERE m.status = 'active' ORDER BY m.created_at DESC LIMIT 5");
$stmt->execute();
$recent_activity = $stmt->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <div class="page-subtitle">Growth and network metrics.</div>
    </div>
    <?php if($my_group): ?>
    <div class="d-flex flex-column align-items-end">
        <div class="badge bg-gold text-dark p-2 px-3 rounded-pill shadow-sm mb-1">
            <i class="fas fa-users me-1"></i> <?= htmlspecialchars($my_group['name']) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Networking Masterclass Widget -->
<?php if($active_networking): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="stat-card" style="background: linear-gradient(135deg, #13131a, #1a1a24); border: 2px solid #FFD70044; position: relative;">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-gold text-dark">LIVE NETWORKING</span>
                        <span class="text-white-50 small"><?= date('d M, h:i A', strtotime($active_networking['scheduled_at'])) ?></span>
                    </div>
                    <h4 style="color:#FFD700; font-weight:900;"><?= htmlspecialchars($active_networking['title']) ?></h4>
                    <?php if($active_networking['ai_business_tip']): ?>
                        <div class="p-3 rounded-3 mt-3" style="background: rgba(255,215,0,0.05); border-left: 3px solid #FFD700;">
                            <small class="text-gold fw-bold d-block mb-1">💡 AI Tip of the Week:</small>
                            <div class="text-white-50" style="font-size: 0.88rem; font-style: italic;">
                                "<?= htmlspecialchars($active_networking['ai_business_tip']) ?>"
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3 text-end pt-3 pt-md-0">
                    <a href="/meetings/join.php?id=<?= $active_networking['id'] ?>" target="_blank" class="btn btn-gold btn-lg w-100 fw-bold shadow">
                        <i class="fas fa-video me-2"></i> Join Session
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/security_badge.php'; ?>

<!-- Primary Stats Grid -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-top">
                <div class="stat-icon gold-bg">📥</div>
            </div>
            <div class="stat-value"><?= number_format($leads_received) ?></div>
            <div class="stat-label">Leads Received</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card blue-accent">
            <div class="stat-card-top">
                <div class="stat-icon blue-bg">📤</div>
            </div>
            <div class="stat-value"><?= number_format($leads_given) ?></div>
            <div class="stat-label">Leads Given</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card green-accent">
            <div class="stat-card-top">
                <div class="stat-icon green-bg">💰</div>
            </div>
            <div class="stat-value">₹<?= number_format($revenue) ?></div>
            <div class="stat-label">Value Won</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card purple-accent">
            <div class="stat-card-top">
                <div class="stat-icon purple-bg">📅</div>
            </div>
            <div class="stat-value"><?= number_format($upcoming_meetings) ?></div>
            <div class="stat-label">Meetings</div>
        </div>
    </div>
</div>

<?php $ai_tips = getConsultantTips($pdo, $uid); ?>
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="stat-card" style="background: linear-gradient(135deg, #13131a, #0d0d16); border: 1px solid #00e87a22;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="color: #00e87a; font-weight: 800; margin: 0;"><i class="fas fa-robot me-2"></i> AI Advisor</h5>
            </div>
            <div class="row g-3">
                <?php foreach($ai_tips as $tip): ?>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 d-flex align-items-start gap-3" style="background: rgba(255,255,255,0.03); height: 100%;">
                        <div style="font-size: 1.5rem;"><?= $tip['icon'] ?></div>
                        <div style="flex: 1;">
                            <div style="font-size: 0.8rem; font-weight: 600; color: #e8e8f0;"><?= htmlspecialchars($tip['text']) ?></div>
                            <a href="<?= $tip['link'] ?>" class="btn btn-sm p-0 text-warning mt-2 small">Fix Now →</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Refer & Earn -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="stat-card" style="background: #13131a; border: 1px solid #FFD70044;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 style="color: #FFD700; font-weight: 800;"><i class="fas fa-gift me-2"></i> Refer & Earn</h5>
                    <div class="input-group mt-3">
                        <input type="text" class="form-control bg-dark text-white border-secondary" value="https://biznexus.in/register_business.php?ref=<?= $user['refer_code'] ?? 'BIZNEXUS' ?>" id="referLink" readonly>
                        <button class="btn btn-gold" onclick="copyReferLink()">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferLink() {
    var copyText = document.getElementById("referLink");
    copyText.select();
    navigator.clipboard.writeText(copyText.value).then(() => { alert("Copied! 🚀"); });
}
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>