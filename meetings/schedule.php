<?php
/**
 * meetings/schedule.php
 * Form for Presidents and Admins to schedule meetings.
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
    die("Unauthorized: Only Admins or Presidents can schedule meetings.");
}

// Handle Form Submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $link = $_POST['link'];
    $time = $_POST['scheduled_at'];
    $type = $_POST['type'];
    $target_group = $isAdmin ? ($_POST['group_id'] ?: null) : $me['group_id'];

    $ins = $pdo->prepare("INSERT INTO networking_meetings (host_id, group_id, meeting_type, title, description, meeting_link, scheduled_at, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $ins->execute([$user_id, $target_group, $type, $title, $_POST['description'] ?? '', $link, $time]);
    $message = "✅ Meeting scheduled successfully!";
}

// Fetch Groups for Admin
$groups = [];
if ($isAdmin) {
    $groups = $pdo->query("SELECT id, name FROM groups")->fetchAll(PDO::FETCH_ASSOC);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schedule Meeting - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#0a0a0f;color:#e0e0f0;font-family:"Inter",sans-serif}
.card{background:#13131a;border:1px solid #2a2a3a;border-radius:16px;padding:30px}
.form-control, .form-select{background:#1c1c27;border:1px solid #2a2a3a;color:#fff}
.form-control:focus{background:#1c1c27;color:#fff;border-color:#FFD700;box-shadow:none}
</style>
</head>
<body class="p-4">
<div class="container" style="max-width:600px">
    <a href="/dashboard/index.php" class="text-decoration-none text-warning mb-4 d-inline-block">← Back to Dashboard</a>
    <div class="card">
        <h3 style="color:#FFD700;font-weight:900" class="mb-4">Schedule a Meeting</h3>
        <?php if($message): ?>
            <div class="alert alert-success"><?=$message?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Meeting Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Weekly Strategy Sync" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="group_team">Team Meeting</option>
                        <option value="one_to_one">One-to-One</option>
                        <?php if($isAdmin): ?><option value="weekly_master">Weekly Master</option><?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Scheduled Time</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Meeting Link (Google Meet / Zoom)</label>
                <input type="url" name="link" class="form-control" placeholder="https://meet.google.com/..." required>
            </div>
            <?php if($isAdmin): ?>
                <div class="mb-3">
                    <label class="form-label">Target Group</label>
                    <select name="group_id" class="form-select">
                        <option value="">All Groups (Global)</option>
                        <?php foreach($groups as $g): ?>
                            <option value="<?=$g['id']?>"><?=$g['name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Brief Description / AI Context</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold py-3">Schedule Meeting now</button>
        </form>
    </div>
</div>
</body>
</html>
