<?php
/**
 * meetings/attendance.php
 * Attendance reports for Presidents and Admins.
 */
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth_check.php";

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role, group_role, group_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch();

$isAdmin = ($me['role'] === 'admin');
$isPresident = ($me['group_role'] === 'president');

if (!$isAdmin && !$isPresident) {
    die("Unauthorized access.");
}

// Fetch Meetings
if ($isAdmin) {
    $stmt = $pdo->query("SELECT * FROM networking_meetings ORDER BY scheduled_at DESC LIMIT 20");
} else {
    $stmt = $pdo->prepare("SELECT * FROM networking_meetings WHERE host_id = ? OR group_id = ? ORDER BY scheduled_at DESC LIMIT 20");
    $stmt->execute([$user_id, $me['group_id']]);
}
$meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If a specific meeting is selected, fetch participants
$selected_meeting = null;
$participants = [];
if (isset($_GET['meeting_id'])) {
    $mid = $_GET['meeting_id'];
    $st = $pdo->prepare("SELECT * FROM networking_meetings WHERE id = ?");
    $st->execute([$mid]);
    $selected_meeting = $st->fetch();
    
    // Security: President can only see their meetings
    if (!$isAdmin && $selected_meeting['host_id'] != $user_id && $selected_meeting['group_id'] != $me['group_id']) {
        die("Unauthorized meeting view.");
    }
    
    $pst = $pdo->prepare("SELECT u.name, u.email, na.status, na.joined_at 
                         FROM networking_attendance na 
                         JOIN users u ON na.user_id = u.id 
                         WHERE na.meeting_id = ? 
                         ORDER BY na.status DESC, u.name ASC");
    $pst->execute([$mid]);
    $participants = $pst->fetchAll(PDO::FETCH_ASSOC);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Attendance Reports - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#0a0a0f;color:#e0e0f0;font-family:"Inter",sans-serif}
.card{background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:20px;margin-bottom:20px}
.table{color:#e0e0f0;border-color:#2a2a3a}
.table thead th{background:#1c1c27;color:#FFD700;border-bottom:2px solid #2a2a3a}
</style>
</head>
<body class="p-4">
<div class="container">
    <a href="/dashboard/index.php" class="text-decoration-none text-warning mb-4 d-inline-block">← Back to Dashboard</a>
    <h2 style="color:#FFD700;font-weight:900" class="mb-4">Networking Attendance Reports</h2>
    
    <div class="row">
        <div class="col-md-4">
            <h5 class="mb-3">Recent Meetings</h5>
            <?php foreach($meetings as $m): ?>
                <a href="?meeting_id=<?=$m['id']?>" class="text-decoration-none">
                    <div class="card <?=(($selected_meeting && $selected_meeting['id']==$m['id'])?'border-warning':'')?>">
                        <h6 class="mb-1 fw-bold"><?=htmlspecialchars($m['title'])?></h6>
                        <small class="text-muted"><?=date('d M Y', strtotime($m['scheduled_at']))?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="col-md-8">
            <?php if ($selected_meeting): ?>
                <div class="card">
                    <h4 class="mb-1" style="color:#FFD700"><?=htmlspecialchars($selected_meeting['title'])?></h4>
                    <p class="text-muted mb-4"><?=date('F j, Y @ h:i A', strtotime($selected_meeting['scheduled_at']))?></p>
                    
                    <h6 class="mb-3">Participation Log</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Status</th>
                                    <th>Joined At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($participants)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No attendance logs found for this meeting.</td></tr>
                                <?php else: ?>
                                    <?php foreach($participants as $p): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($p['name'])?><br><small class="text-muted"><?=$p['email']?></small></td>
                                            <td>
                                                <span class="badge <?=($p['status']=='present'?'bg-success':'bg-secondary')?>">
                                                    <?=ucfirst($p['status'])?>
                                                </span>
                                            </td>
                                            <td><?=$p['joined_at'] ? date('h:i A', strtotime($p['joined_at'])) : '-'?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card text-center py-5">
                    <h5 class="text-muted">Select a meeting from the left to view attendance details.</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
