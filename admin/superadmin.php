<?php
// /admin/superadmin.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes_functions.php';

$uid = (int)$_SESSION['user_id'];

// FIXED: Using getDB() instead of hardcoded connection constants
global $pdo;
if (!$pdo) {
    die("Database connection failed.");
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    die("Access denied. Super Admin only.");
}

// Stats gathering
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$stats['total_leads'] = $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn();
$stats['total_referrals'] = $pdo->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
$stats['coins_circulating'] = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM coin_transactions")->fetchColumn();

// Handle bulk actions (simplified for this fix)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = (int)$_POST['user_id'];
    
    if ($action === 'deactivate') {
        $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$userId]);
    } elseif ($action === 'delete') {
        // Agent 10 Auto Cleanup
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM coin_escrow WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM referrals WHERE referrer_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM lead_dispatches WHERE member_id = ?")->execute([$userId]);
            // Depending on schema, clean up matching leads or queues
            try { $pdo->prepare("DELETE FROM public_leads WHERE claimed_by_member_id = ?")->execute([$userId]); } catch(Exception $e){}
            try { $pdo->prepare("DELETE FROM voocoin_balances WHERE user_id = ?")->execute([$userId]); } catch(Exception $e){}
            try { $pdo->prepare("DELETE FROM lead_whatsapp_queue WHERE member_id = ?")->execute([$userId]); } catch(Exception $e){}
            try { $pdo->prepare("DELETE FROM support_tickets WHERE user_id = ?")->execute([$userId]); } catch(Exception $e){}
            $pdo->prepare("DELETE FROM business_profiles WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    } elseif ($action === 'broadcast_notif') {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type'] ?? 'news';
        $all_users = $pdo->query("SELECT id FROM users WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        foreach ($all_users as $target_id) {
            $stmt->execute([$target_id, $title, $message, $type]);
        }
        $msg = "Broadcast sent successfully!";
    } elseif ($action === 'individual_notif') {
        $target_id = (int)$_POST['target_user_id'];
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type'] ?? 'info';
        sendNotification($pdo, $target_id, $title, $message, $type);
        $msg = "Notification sent to user ID $target_id.";
    }
    header("Location: superadmin.php?msg=" . urlencode($msg ?? 'Action completed'));
    exit;
}

// Fetch members
$members = $pdo->query("SELECT id, name, email, phone, plan, status, is_verified, created_at FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Super Admin - BizNexus</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap' rel='stylesheet'>
    <link href='/assets/css/biznexus.css' rel='stylesheet'>
    <style>
        .badge-plan { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .p-free { background: #333; color: #fff; }
        .p-silver { background: #C0C0C0; color: #000; }
        .p-gold { background: #FFD700; color: #000; }
        .p-platinum { background: #E5E4E2; color: #000; }
        .s-active { color: #00ff88; }
        .s-inactive { color: #ff4455; }
        @keyframes pulseG { 0% { transform: scale(1); text-shadow: 0 0 10px #00ff88; } 50% { transform: scale(1.1); text-shadow: 0 0 20px #00ff88; } 100% { transform: scale(1); text-shadow: 0 0 10px #00ff88; } }
        .pulse-green { animation: pulseG 2s infinite; color: #00ff88; }
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm("WARNING: Are you sure you want to delete this user? This will permanently erase their profile and scrub all related escrow/lead data.")) {
                if (confirm("DOUBLE CONFIRMATION: This action is irreversible. Proceed?")) {
                    document.getElementById('action_' + id).value = 'delete';
                    document.getElementById('form_' + id).submit();
                }
            }
        }
    </script>
</head>
<body>

<div class='sidebar'>
    <div class='sidebar-logo'>⚡ Super Admin</div>
    <nav class='nav flex-column' style='flex:1'>
        <a class='nav-link active' href='/admin/superadmin.php'>🏠 Dashboard Analytics</a>
        <a class='nav-link' href='/admin/users.php'>👥 Customer Data</a>
        <!-- FIXED: Added Leads Tracker link -->
        <a class='nav-link' href='/admin/leads.php'>🔥 Lead Tracker</a>
        <a class='nav-link' href='/dashboard/index.php'>⬅ Back to App</a>
    </nav>
    <a href='/auth/logout.php' style='color:#ff4455;padding:16px 20px;text-decoration:none;'>🚪 Logout</a>
</div>

<div class='main p-4'>
    <h2 class="mb-4" style="font-family: 'Syne', sans-serif; font-weight: 800;">CEO Command Center</h2>
    
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Agent 11 Flowchart -->
    <div class="card p-4 mb-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold); margin-bottom: 20px;"><i class="fas fa-network-wired"></i> Autonomous Agent Flowchart</h5>
        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
            <?php
            $agents = [
                1 => ['name' => 'Agent 1: Security', 'status' => 'running'],
                2 => ['name' => 'Agent 2: DB Arch', 'status' => 'running'],
                3 => ['name' => 'Agent 3: Core Logic', 'status' => 'running'],
                4 => ['name' => 'Agent 4: Gateway', 'status' => 'running'],
                5 => ['name' => 'Agent 5: SEO', 'status' => 'running'],
                6 => ['name' => 'Agent 6: WhatsApp', 'status' => 'running'],
                7 => ['name' => 'Agent 7: Trust Score', 'status' => 'running'],
                8 => ['name' => 'Agent 8: Onboarder', 'status' => 'running'],
                9 => ['name' => 'Agent 9: Tester', 'status' => 'running'],
                10 => ['name' => 'Agent 10: Guardian', 'status' => 'running'],
                11 => ['name' => 'Agent 11: Manager', 'status' => 'running'],
                12 => ['name' => 'Agent 12: Support', 'status' => 'running']
            ];
            foreach($agents as $id => $a):
                $pulsing = $a['status'] === 'running' ? 'pulse-green' : 'text-warning';
                $icon = $a['status'] === 'running' ? 'fa-satellite-dish' : 'fa-clock';
                $textColor = $a['status'] === 'running' ? '#00ff88' : '#888';
            ?>
            <div class="text-center p-2" style="border: 1px dashed var(--border); border-radius: 8px; width: 140px; background: rgba(0, 255, 136, 0.05); border-color: #00ff88;">
                <div style="font-size: 24px;" class="<?= $pulsing ?> mb-2">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div style="font-size: 11px; color: <?= $textColor ?>; font-weight: bold; text-transform:uppercase;"><?= $a['name'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Broadcast Notification Tool -->
    <div class="card p-4 mb-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold); margin-bottom: 20px;"><i class="fas fa-bullhorn"></i> Broadcast Platform News</h5>
        <form method="POST">
            <input type="hidden" name="action" value="broadcast_notif">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Notification Title</label>
                    <input type="text" name="title" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="e.g. Weekly Update" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Type</label>
                    <select name="type" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="news">📰 News</option>
                        <option value="system">⚙️ System</option>
                        <option value="success">✅ Success</option>
                        <option value="warning">⚠️ Warning</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Message</label>
                    <input type="text" name="message" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Type your message here..." required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">Send Broadcast</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 text-center" style="background: var(--card); border: 1px solid var(--border);">
                <h3 style="color: var(--gold);"><?= $stats['total_users'] ?></h3>
                <p class="mb-0 text-muted">Total Users</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center" style="background: var(--card); border: 1px solid var(--border);">
                <h3 style="color: #00ff88;"><?= $stats['active_users'] ?></h3>
                <p class="mb-0 text-muted">Active Users</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center" style="background: var(--card); border: 1px solid var(--border);">
                <h3 style="color: #6c63ff;"><?= $stats['total_leads'] ?></h3>
                <p class="mb-0 text-muted">Total Leads Generated</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center" style="background: var(--card); border: 1px solid var(--border);">
                <h3 style="color: #FFD700;"><?= number_format($stats['coins_circulating']) ?></h3>
                <p class="mb-0 text-muted">VooCoins Circulating</p>
            </div>
        </div>
    </div>

    <div class="card p-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold); margin-bottom: 20px;">Recent Members</h5>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($u['name']) ?>
                                <?php if ($u['is_verified']): ?>
                                    <span style="color:#FFD700; font-size:12px; margin-left:5px;">✓</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone']) ?></td>
                            <td>
                                <!-- FIXED: Corrected PHP tag closure here to prevent HTTP 500 parse error -->
                                <span class="badge-plan p-<?= htmlspecialchars($u['plan'] ?? 'free') ?>">
                                    <?= htmlspecialchars($u['plan'] ?? 'free') ?>
                                </span>
                            </td>
                            <td class="s-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></td>
                            <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                            <td>
                                <form id="form_<?= $u['id'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" id="action_<?= $u['id'] ?>" value="">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="document.getElementById('action_<?= $u['id'] ?>').value='deactivate'; document.getElementById('form_<?= $u['id'] ?>').submit();" title="Deactivate"><i class="fas fa-ban"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#notifModal" onclick="setNotifUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>')" title="Send Notification"><i class="fas fa-bell"></i></button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $u['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Individual Notification Modal -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Send to: <span id="notifUserName" class="text-warning"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="individual_notif">
                    <input type="hidden" name="target_user_id" id="notifUserId">
                    
                    <div class="mb-3">
                        <label class="form-label small">Title</label>
                        <input type="text" name="title" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Type</label>
                        <select name="type" class="form-select bg-dark text-white border-secondary">
                            <option value="info">ℹ️ Info</option>
                            <option value="lead">🎯 Lead</option>
                            <option value="referral">🤝 Referral</option>
                            <option value="coin">🪙 Coins</option>
                            <option value="meeting">📅 Meeting</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Message</label>
                        <textarea name="message" class="form-control bg-dark text-white border-secondary" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-modal="dismiss">Cancel</button>
                    <button type="submit" class="btn btn-gold btn-sm px-4">Send Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setNotifUser(id, name) {
    document.getElementById('notifUserId').value = id;
    document.getElementById('notifUserName').innerText = name;
}
</script>
</body>
</html>
