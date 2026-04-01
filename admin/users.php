<?php
// /admin/users.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$uid = (int)$_SESSION['user_id'];
global $pdo;

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
if ($stmt->fetchColumn() !== 'admin') {
    die("Access denied. Super Admin only.");
}

// Handle Verification Toggles & Password Resets
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_verify'])) {
        $target_id = (int)$_POST['user_id'];
        $current = (int)$_POST['current_verified'];
        $new_status = $current ? 0 : 1;
        $vStmt = $pdo->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
        $vStmt->execute([$new_status, $target_id]);
        $message = "User ID $target_id verification status updated.";
    } elseif (isset($_POST['reset_password'])) {
        $target_id = (int)$_POST['user_id'];
        $new_pass = 'BizNexus@2026';
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $pStmt->execute([$hash, $target_id]);
        $message = "Password for User ID $target_id has been reset to: <strong>BizNexus@2026</strong>";
    } elseif (isset($_POST['update_plan'])) {
        $target_id = (int)$_POST['user_id'];
        $new_plan = $_POST['new_plan'];
        $pdo->prepare("UPDATE users SET plan = ? WHERE id = ?")->execute([$new_plan, $target_id]);
        $message = "User ID $target_id plan updated to $new_plan.";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 100;
$offset = ($page - 1) * $limit;

// Search Filter
$search = $_GET['q'] ?? '';
$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (name LIKE ? OR business_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

// Fetch all members
$query = "SELECT id, name, email, phone, business_name, category, city, plan, status, is_verified, email_verified, created_at FROM users $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmtUsers = $pdo->prepare($query);
$stmtUsers->execute($params);
$users = $stmtUsers->fetchAll();

?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Customer Management - Super Admin</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&display=swap' rel='stylesheet'>
    <link href='/assets/css/biznexus.css' rel='stylesheet'>
    <style>
        .badge-trust { background: linear-gradient(135deg, #FFD700, #ff8c00); color: #000; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
        .action-btn { font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; border: 1px solid #555; background: #222; color: #fff; cursor: pointer; }
        .action-btn:hover { background: #444; }
        .btn-reset { border-color: #ff4455; color: #ff4455; }
        .btn-reset:hover { background: #ff4455; color: #000; }
        .btn-verify { border-color: #00ff88; color: #00ff88; }
        .btn-verify:hover { background: #00ff88; color: #000; }
    </style>
</head>
<body>

<div class='sidebar'>
    <div class='sidebar-logo'>⚡ Super Admin</div>
    <nav class='nav flex-column' style='flex:1'>
        <a class='nav-link' href='/admin/superadmin.php'>🏠 Dashboard Analytics</a>
        <a class='nav-link active' href='/admin/users.php'>👥 Customer Data</a>
        <a class='nav-link' href='/admin/leads.php'>🔥 Lead Tracker</a>
        <a class='nav-link' href='/dashboard/index.php'>⬅ Back to App</a>
    </nav>
    <a href='/auth/logout.php' style='color:#ff4455;padding:16px 20px;text-decoration:none;'>🚪 Logout</a>
</div>

<div class='main p-4'>
    <h2 class="mb-4" style="font-family: 'Syne', sans-serif; font-weight: 800;">Customer Database</h2>

    <?php if ($message): ?>
        <div class="alert alert-success" style="background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card p-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <form method="GET" class="mb-4">
            <div class="input-group" style="max-width: 400px;">
                <input type="text" name="q" class="form-control" style="background: var(--bg); color: #fff; border-color: var(--border);" placeholder="Search Name, Email, Phone..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-warning" type="submit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead style="border-bottom: 2px solid var(--gold);">
                    <tr>
                        <th>ID</th>
                        <th>Business / Name</th>
                        <th>Contact</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.9rem;">
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['business_name'] ?: $u['name']) ?></strong><br>
                                <small style="color: var(--text2);"><?= htmlspecialchars($u['name']) ?></small>
                                <?php if ($u['is_verified']): ?>
                                    <br><span class="badge-trust">✓ Verified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['email']) ?>
                                <?php if($u['email_verified']??0): ?>
                                    <span style="color:#00ff88;font-size:0.75rem;">(Verified)</span>
                                <?php else: ?>
                                    <span style="color:#ffcc00;font-size:0.75rem;">(Unverified)</span>
                                <?php endif; ?>
                                <br>
                                <?= htmlspecialchars($u['phone']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['category']) ?><br>
                                <small style="color: var(--gold);"><?= htmlspecialchars($u['city']) ?></small>
                            </td>
                            <td>
                                <span style="color: <?= $u['status']=='active' ? '#00ff88' : '#ff4455' ?>"><?= ucfirst($u['status']) ?></span><br>
                                <select onchange="updatePlan(<?= $u['id'] ?>, this.value)" class="form-select form-select-sm bg-dark text-white border-0 mt-1" style="width:100px; font-size:0.7rem;">
                                    <option value="free" <?= ($u['plan']=='free')?'selected':'' ?>>FREE</option>
                                    <option value="silver" <?= ($u['plan']=='silver')?'selected':'' ?>>SILVER</option>
                                    <option value="gold" <?= ($u['plan']=='gold')?'selected':'' ?>>GOLD</option>
                                    <option value="platinum" <?= ($u['plan']=='platinum')?'selected':'' ?>>PLATINUM</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="current_verified" value="<?= $u['is_verified'] ?>">
                                        <button type="submit" name="toggle_verify" class="action-btn btn-verify">
                                            <?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset password for this user?');">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="reset_password" class="action-btn btn-reset">Reset Pass</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between mt-3">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-light">← Previous Page</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-light">Next Page →</a>
        </div>
    </div>
</div>

<script>
function updatePlan(userId, plan) {
    if(!confirm("Change plan for Member ID " + userId + " to " + plan + "?")) return;
    let f = document.createElement("form");
    f.method = "POST";
    f.innerHTML = `<input type="hidden" name="update_plan" value="1"><input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="new_plan" value="${plan}">`;
    document.body.appendChild(f);
    f.submit();
}
</script>
</body>
</html>
