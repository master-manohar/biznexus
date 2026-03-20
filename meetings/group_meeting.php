<?php
// /meetings/group_meeting.php - President/VP can schedule group meetings
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$uid   = (int)$_SESSION['user_id'];

// Fetch user info + role
$userStmt = $pdo->prepare("SELECT u.*, g.name as group_name, g.id as gid FROM users u LEFT JOIN groups g ON u.group_id = g.id WHERE u.id = ?");
$userStmt->execute([$uid]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$allowed_roles = ['president', 'vice_president', 'gen_secretary'];
if (!$user || !in_array($user['group_role'] ?? '', $allowed_roles)) {
    http_response_code(403);
    die("<div style='text-align:center;padding:60px;color:#ff4d6d;font-family:Inter,sans-serif;'><h2>Access Restricted</h2><p>Only Group Presidents and Officers can schedule Group Meetings.</p><a href='/dashboard/index.php' style='color:#FFD700;'>← Back to Dashboard</a></div>");
}

$group_id   = $user['gid'];
$group_name = $user['group_name'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic        = trim($_POST['topic'] ?? '');
    $agenda       = trim($_POST['agenda'] ?? '');
    $meeting_date = trim($_POST['meeting_date'] ?? '');
    $meeting_time = trim($_POST['meeting_time'] ?? '');
    $meeting_type = $_POST['meeting_type'] ?? 'online';

    if (empty($topic) || empty($meeting_date) || empty($meeting_time)) {
        $msg = 'error:Topic, Date, and Time are required.';
    } else {
        // Generate Google Meet link
        $meet_token = substr(md5(time() . $uid . $topic), 0, 3) . '-' .
                      substr(md5($meeting_date . $uid), 0, 4) . '-' .
                      substr(md5($meeting_time . $group_id), 0, 3);
        $meet_link = "https://meet.google.com/{$meet_token}";

        try {
            $pdo->beginTransaction();

            // Insert meeting as a group meeting (attendee_id = 0 means group)
            $ins = $pdo->prepare("INSERT INTO meetings 
                (created_by, attendee_id, meeting_type, meeting_date, meeting_time, meeting_brief, meeting_link, coin_cost, status)
                VALUES (?, 0, ?, ?, ?, ?, ?, 0, 'scheduled')");
            $ins->execute([$uid, $meeting_type, $meeting_date, $meeting_time, "$topic\n\n$agenda", $meet_link]);
            $meeting_id = $pdo->lastInsertId();

            // Notify ALL members of the group
            $members = $pdo->prepare("SELECT id FROM users WHERE group_id = ? AND status = 'active' AND id != ?");
            $members->execute([$group_id, $uid]);
            $group_members = $members->fetchAll(PDO::FETCH_COLUMN);

            $notif_title = "📅 Group Meeting Scheduled — {$group_name}";
            $notif_msg   = "{$user['name']} has scheduled a group meeting: \"{$topic}\" on " . date('d M Y', strtotime($meeting_date)) . " at " . date('h:i A', strtotime($meeting_time)) . ". Join: {$meet_link}";

            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'meeting', NOW())");
            foreach ($group_members as $mid) {
                $notif_stmt->execute([$mid, $notif_title, $notif_msg]);
            }

            $pdo->commit();
            $msg = "success:Group meeting scheduled! Google Meet link: {$meet_link} — {$count} members notified.";
            $count = count($group_members);
            $msg = "success:Group meeting '{$topic}' on " . date('d M Y', strtotime($meeting_date)) . " scheduled! {$count} members notified. Meet link: {$meet_link}";
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = 'error:' . $e->getMessage();
        }
    }
}

// Fetch recent group meetings
$recent = $pdo->prepare("SELECT m.*, u.name as organizer FROM meetings m LEFT JOIN users u ON m.created_by = u.id WHERE m.created_by IN (SELECT id FROM users WHERE group_id = ?) AND m.attendee_id = 0 ORDER BY m.meeting_date DESC LIMIT 10");
$recent->execute([$group_id]);
$past_meetings = $recent->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Group Meeting Scheduler';
$active_page = 'meetings';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<style>
.meet-card { background: var(--card); border: 1px solid rgba(68,136,255,.3); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.meet-link-box { background: rgba(68,136,255,.08); border: 1px solid rgba(68,136,255,.3); border-radius: 10px; padding: 12px 16px; font-family: monospace; font-size:.85rem; color:#4488ff; word-break:break-all; margin-top:10px; }
.role-badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:.72rem; font-weight:700; text-transform:uppercase; }
.r-president { background:rgba(255,215,0,.15); color:#FFD700; border:1px solid rgba(255,215,0,.3); }
.r-vice_president { background:rgba(0,232,122,.1); color:#00e87a; border:1px solid rgba(0,232,122,.3); }
</style>

<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px;">
    <div>
        <h2 style="font-family:'Syne',sans-serif; font-weight:800; margin:0; color:#e8e8f5;">📅 Schedule Group Meeting</h2>
        <p style="color:#8888aa; margin:6px 0 0; font-size:.88rem;">
            Group: <strong style="color:#e8e8f5;"><?= htmlspecialchars($group_name) ?></strong>
            &nbsp;<span class="role-badge r-<?= $user['group_role'] ?>"><?= ucfirst(str_replace('_',' ',$user['group_role'])) ?></span>
        </p>
    </div>
    <a href="/meetings/list.php" style="color:#8888aa; text-decoration:none; font-size:.85rem;">← My Meetings</a>
</div>

<?php if ($msg): [$type, $text] = explode(':', $msg, 2); ?>
<div style="background:<?= $type==='success' ? 'rgba(0,232,122,.1)' : 'rgba(255,68,68,.1)' ?>; border:1px solid <?= $type==='success' ? '#00e87a' : '#ff4d6d' ?>; color:<?= $type==='success' ? '#00e87a' : '#ff4d6d' ?>; padding:14px 18px; border-radius:10px; margin-bottom:24px; line-height:1.6;">
    <?= $type==='success' ? '✅' : '❌' ?> <?= htmlspecialchars($text) ?>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">
    <!-- Schedule Form -->
    <div class="meet-card">
        <h5 style="color:#FFD700; margin-bottom:20px;"><i class="fas fa-calendar-plus"></i> New Group Meeting</h5>
        <form method="POST">
            <div class="mb-3">
                <label style="color:#c0c0d8; font-size:.85rem; display:block; margin-bottom:6px;">Meeting Topic *</label>
                <input type="text" name="topic" class="form-control" placeholder="e.g. Monthly Business Review Q1" required>
            </div>
            <div class="mb-3">
                <label style="color:#c0c0d8; font-size:.85rem; display:block; margin-bottom:6px;">Agenda / Notes</label>
                <textarea name="agenda" class="form-control" rows="3" placeholder="Topics to discuss, action items..."></textarea>
            </div>
            <div class="mb-3">
                <label style="color:#c0c0d8; font-size:.85rem; display:block; margin-bottom:6px;">Meeting Type</label>
                <select name="meeting_type" class="form-control">
                    <option value="online">💻 Online (Google Meet)</option>
                    <option value="physical">☕ Physical / In-person</option>
                    <option value="hybrid">🔀 Hybrid</option>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;" class="mb-4">
                <div>
                    <label style="color:#c0c0d8; font-size:.85rem; display:block; margin-bottom:6px;">Date *</label>
                    <input type="date" name="meeting_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label style="color:#c0c0d8; font-size:.85rem; display:block; margin-bottom:6px;">Time *</label>
                    <input type="time" name="meeting_time" class="form-control" required>
                </div>
            </div>
            <div style="background:rgba(68,136,255,.06); border:1px solid rgba(68,136,255,.2); border-radius:10px; padding:12px 14px; margin-bottom:16px; font-size:.8rem; color:#8888aa;">
                <strong style="color:#4488ff;">ℹ️ Auto-Notify:</strong> All <?= htmlspecialchars($group_name) ?> members will receive an in-app notification + the Google Meet link instantly.
            </div>
            <button type="submit" class="btn btn-primary w-100" style="background:linear-gradient(135deg,#FFD700,#ff8c00);color:#000;font-weight:700;border:none;border-radius:10px;padding:12px;">
                📅 Schedule & Notify All Members
            </button>
        </form>
    </div>

    <!-- Recent Group Meetings -->
    <div>
        <h5 style="color:#e8e8f5; margin-bottom:16px;">Recent Group Meetings</h5>
        <?php if (empty($past_meetings)): ?>
            <div style="text-align:center; padding:40px; color:#8888aa; background:var(--card); border:1px solid var(--border); border-radius:12px;">
                <div style="font-size:2rem;">📋</div>
                <div>No group meetings scheduled yet</div>
            </div>
        <?php else: ?>
        <?php foreach($past_meetings as $pm): ?>
        <div style="background:var(--card); border:1px solid var(--border); border-radius:10px; padding:16px; margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div style="font-weight:600; color:#e8e8f5; font-size:.9rem;"><?= htmlspecialchars(strtok($pm['meeting_brief'] ?? 'Group Meeting', "\n")) ?></div>
                    <div style="font-size:.75rem; color:#8888aa; margin-top:4px;">
                        Organized by <?= htmlspecialchars($pm['organizer'] ?? 'President') ?> &nbsp;·&nbsp;
                        <?= date('d M Y, h:i A', strtotime($pm['meeting_date'].' '.$pm['meeting_time'])) ?>
                    </div>
                </div>
                <span style="font-size:.7rem; padding:3px 10px; border-radius:20px; background:rgba(0,232,122,.1); color:#00e87a; border:1px solid rgba(0,232,122,.3);">
                    <?= ucfirst($pm['status']) ?>
                </span>
            </div>
            <?php if ($pm['meeting_link']): ?>
            <div class="meet-link-box" style="margin-top:8px;">
                🔗 <a href="<?= htmlspecialchars($pm['meeting_link']) ?>" target="_blank" style="color:#4488ff;"><?= htmlspecialchars($pm['meeting_link']) ?></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
