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

// 3. Revenue Generated (Sum of estimated value of accepted/closed leads)
$stmt = $pdo->prepare("SELECT SUM(estimated_value) FROM leads WHERE (to_user_id = ? OR from_user_id = ?) AND status IN ('accepted', 'closed')");
$stmt->execute([$uid, $uid]);
$revenue = $stmt->fetchColumn() ?: 0;

// 4. Upcoming Meetings
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND meeting_date >= CURDATE() AND status = 'scheduled'");
$stmt->execute([$uid, $uid]);
$upcoming_meetings = $stmt->fetchColumn() ?: 0;

// Meeting Reminders (Check for meetings in next 24h and notify once)
if ($upcoming_meetings > 0) {
    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE (created_by = ? OR attendee_id = ?) AND meeting_date = CURDATE() AND status = 'scheduled'");
    $stmt->execute([$uid, $uid]);
    $today_meetings = $stmt->fetchAll();
    foreach($today_meetings as $tm) {
        $notif_title = "Meeting Reminder: " . ($tm['title'] ?: 'B2B Meeting');
        // Check if already notified today
        $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $check->execute([$uid, $notif_title]);
        if (!$check->fetch()) {
            sendNotification($pdo, $uid, $notif_title, "You have a meeting scheduled for Today at " . date('h:i A', strtotime($tm['meeting_time'])), 'meeting');
        }
    }
}

// 5. Group Identification & Badges
$stmt = $pdo->prepare("SELECT g.*, gr.role as group_role FROM groups g LEFT JOIN group_roles gr ON g.id = gr.group_id AND gr.user_id = ? WHERE g.id = (SELECT group_id FROM users WHERE id = ?)");
$stmt->execute([$uid, $uid]);
$my_group = $stmt->fetch();

// Fetch Badges
$stmt = $pdo->prepare("SELECT * FROM member_badges WHERE user_id = ? ORDER BY awarded_at DESC");
$stmt->execute([$uid]);
$badges = $stmt->fetchAll();

// 6. Recent Market Activity (Newsletters)
$stmt = $pdo->prepare("SELECT m.title, m.created_at, u.business_name FROM marketplace m JOIN users u ON m.user_id = u.id WHERE m.status = 'active' ORDER BY m.created_at DESC LIMIT 5");
$stmt->execute();
$recent_activity = $stmt->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <div class="page-subtitle">Personal growth and network metrics.</div>
    </div>
    <?php if($my_group): ?>
    <div class="d-flex flex-column align-items-end">
        <div class="badge bg-gold text-dark p-2 px-3 rounded-pill shadow-sm mb-1">
            <i class="fas fa-users me-1"></i> Member of: <?= htmlspecialchars($my_group['name']) ?>
        </div>
        <?php if($my_group['group_role']): ?>
            <div class="badge bg-primary p-1 px-2 rounded-pill small"><?= strtoupper($my_group['group_role']) ?></div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="badge bg-secondary p-2 px-3 rounded-pill">Global Network Member</div>
    <?php endif; ?>
</div>

<!-- Badge Shelf -->
<?php if($badges): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach($badges as $b): ?>
            <div class="badge-item px-3 py-2 rounded-3" style="background: rgba(255,215,0,0.1); border: 1px solid #FFD70044; color: #FFD700;" title="Awarded on <?= date('d M Y', strtotime($b['awarded_at'])) ?>">
                <i class="fas fa-medal me-2"></i> <?= htmlspecialchars($b['label']) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Primary Stats Grid -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card gold-accent">
            <div class="stat-card-top">
                <div class="stat-icon gold-bg">📥</div>
                <div class="stat-trend trend-neutral">Incoming</div>
            </div>
            <div class="stat-value"><?= number_format($leads_received) ?></div>
            <div class="stat-label">Leads Received</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card blue-accent">
            <div class="stat-card-top">
                <div class="stat-icon blue-bg">📤</div>
                <div class="stat-trend trend-up">Outgoing</div>
            </div>
            <div class="stat-value"><?= number_format($leads_given) ?></div>
            <div class="stat-label">Leads Given</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card green-accent">
            <div class="stat-card-top">
                <div class="stat-icon green-bg">💰</div>
                <div class="stat-trend trend-up">Revenue</div>
            </div>
            <div class="stat-value">₹<?= number_format($revenue) ?></div>
            <div class="stat-label">Estimated Value Won</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card purple-accent">
            <div class="stat-card-top">
                <div class="stat-icon purple-bg">📅</div>
                <div class="stat-trend trend-neutral">Syncs</div>
            </div>
            <div class="stat-value"><?= number_format($upcoming_meetings) ?></div>
            <div class="stat-label">Upcoming Meetings</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Network Activity (Newsletters) -->
    <div class="col-lg-8">
        <div class="stat-card" style="height: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 style="color: #e8e8f0; font-weight: 800; margin: 0;">🌐 Network Updates</h5>
                <a href="/marketplace/list.php" class="text-warning text-decoration-none small">Explore All</a>
            </div>
            <div class="activity-list">
                <?php foreach($recent_activity as $act): ?>
                <div class="d-flex align-items-center p-3 mb-2 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                    <div style="width: 40px; height: 40px; background: #FFD70033; color: #FFD700; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.9rem; font-weight: 600; color: #e8e8f0;"><?= htmlspecialchars($act['title']) ?></div>
                        <div style="font-size: 0.75rem; color: #9898bc;">Listed by <?= htmlspecialchars($act['business_name']) ?></div>
                    </div>
                    <div class="text-end" style="font-size: 0.7rem; color: #8888aa;">
                        <?= date('M j', strtotime($act['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Group Information -->
    <div class="col-lg-4">
        <div class="stat-card" style="height: 100%; background: linear-gradient(145deg, #13131a, #0a0a0f); border: 1px solid #FFD70022;">
            <h5 class="mb-4" style="color: #FFD700; font-weight: 800;">🏢 My Group</h5>
            <?php if($my_group): ?>
                <div class="text-center py-4">
                    <div style="font-size: 3rem; margin-bottom: 15px;">💎</div>
                    <h4 style="font-weight: 800; color: #fff;"><?= htmlspecialchars($my_group['name']) ?></h4>
                    <p style="color: #888; font-size: 0.85rem;"><?= htmlspecialchars($my_group['description'] ?: 'Collaborate with local business owners in your category.') ?></p>
                    <div class="mt-4 p-3 rounded-4" style="background: #1a1a24;">
                        <div class="row text-center">
                            <div class="col-6" style="border-right: 1px solid #2a2a3a;">
                                <div style="color: #FFD700; font-weight: 800;">Tier</div>
                                <div style="font-size: 0.8rem; color: #aaa;"><?= $my_group['tier'] ?></div>
                            </div>
                            <div class="col-6">
                                <div style="color: #FFD700; font-weight: 800;">Members</div>
                                <div style="font-size: 0.8rem; color: #aaa;"><?= $my_group['max_members'] ?> Max</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($my_group['group_role'] === 'president'): ?>
                        <div class="president-portal mt-4 text-start">
                            <hr style="border-color: #2a2a3a;">
                            <h6 class="text-gold mb-3"><i class="fas fa-bullhorn me-2"></i> President Portal</h6>
                            <form action="/includes/handle_broadcast.php" method="POST">
                                <input type="hidden" name="group_id" value="<?= $my_group['id'] ?>">
                                <textarea name="message" class="form-control form-control-sm bg-black text-white border-secondary mb-2" rows="2" placeholder="Broadast to your group..."></textarea>
                                <button type="submit" class="btn btn-gold btn-sm w-100 fw-bold">Send Announcement</button>
                            </form>
                            <p class="text-muted mt-2 small" style="font-size: 0.7rem;">Term ends in <?= date('d', strtotime($my_group['term_started_at'] . ' + 90 days') - time()) ?> days.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;">🏢</div>
                    <p style="color: #555;">You are currently in the Global Pool. Complete your profile to be assigned to a city-specific group.</p>
                    <a href="/profile/edit.php" class="btn btn-outline-warning btn-sm px-4 rounded-pill">Complete Profile</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>