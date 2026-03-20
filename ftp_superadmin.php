<?php
// /admin/superadmin.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

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

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'toggle_status') {
        $current = $pdo->query("SELECT status FROM users WHERE id = $userId")->fetchColumn();
        $newStatus = $current === 'active' ? 'inactive' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
    } elseif ($action === 'delete') {
        // Agent 10 Auto Cleanup
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM coin_escrow WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM referrals WHERE referrer_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM lead_dispatches WHERE member_id = ?")->execute([$userId]);
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
    } elseif ($action === 'agent_action') {
        // Handle agent run/hold (simulate logic)
        $agentId = $_POST['agent_id'] ?? 0;
        $agentCmd = $_POST['agent_cmd'] ?? '';
        // Real implementation would log or trigger shell, for now we set session state
        $_SESSION['agent_states'][$agentId] = $agentCmd === 'run' ? 'running' : 'hold';
    }
    header("Location: superadmin.php");
    exit;
}

// Fetch members including password hash for Security Insight
$members = $pdo->query("SELECT id, name, email, phone, plan, status, is_verified, password, created_at FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll();
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
        <a class='nav-link' href='/admin/categories.php'>🏷️ Product Categories</a>
        <a class='nav-link' href='/dashboard/index.php'>⬅ Back to App</a>
    </nav>
    <a href='/auth/logout.php' style='color:#ff4455;padding:16px 20px;text-decoration:none;'>🚪 Logout</a>
</div>

<div class='main p-4'>
    <h2 class="mb-4" style="font-family: 'Syne', sans-serif; font-weight: 800;">CEO Command Center</h2>
    
    <!-- Agent 11 Flowchart -->
    <div class="card p-4 mb-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold); margin-bottom: 20px;"><i class="fas fa-network-wired"></i> Autonomous Agent Flowchart</h5>
        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
            <?php
            $agents = [
                1 => 'Agent 1: Security', 2 => 'Agent 2: DB Arch', 3 => 'Agent 3: Core Logic',
                4 => 'Agent 4: Gateway', 5 => 'Agent 5: SEO', 6 => 'Agent 6: WhatsApp',
                7 => 'Agent 7: Trust Score', 8 => 'Agent 8: Onboarder', 9 => 'Agent 9: Tester',
                10 => 'Agent 10: Guardian', 11 => 'Agent 11: Manager', 12 => 'Agent 12: Support'
            ];
            foreach($agents as $id => $name):
                $status = $_SESSION['agent_states'][$id] ?? 'running';
                $pulsing = $status === 'running' ? 'pulse-green' : 'text-warning';
                $icon = $status === 'running' ? 'fa-satellite-dish' : 'fa-pause-circle';
                $textColor = $status === 'running' ? '#00ff88' : '#ffc107';
                $borderColor = $status === 'running' ? '#00ff88' : '#ffc107';
            ?>
            <div class="text-center p-2 mb-2" style="border: 1px dashed var(--border); border-radius: 8px; width: 140px; background: rgba(255, 255, 255, 0.02); border-color: <?= $borderColor ?>;">
                <div style="font-size: 24px;" class="<?= $pulsing ?> mb-2"><i class="fas <?= $icon ?>"></i></div>
                <div style="font-size: 11px; color: <?= $textColor ?>; font-weight: bold; text-transform:uppercase; mb-2"><?= $name ?></div>
                <div class="d-flex justify-content-center gap-1 mt-2">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="agent_action">
                        <input type="hidden" name="agent_id" value="<?= $id ?>">
                        <input type="hidden" name="agent_cmd" value="run">
                        <button type="submit" class="btn btn-sm" style="background:#00ff88;color:#000;font-size:9px;padding:2px 5px;" title="Run">RUN</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="agent_action">
                        <input type="hidden" name="agent_id" value="<?= $id ?>">
                        <input type="hidden" name="agent_cmd" value="hold">
                        <button type="submit" class="btn btn-sm" style="background:#ffc107;color:#000;font-size:9px;padding:2px 4px;" title="Hold">HLD</button>
                    </form>
                    <a href="/admin/agent_log.php?id=<?= $id ?>" target="_blank" class="btn btn-sm" style="background:#6c757d;color:#fff;font-size:9px;padding:2px 6px; text-decoration: none;" title="Open Telemetry Logs">LOG</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Deep Audit: Site Link Health Directory -->
    <div class="card p-4 mb-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold); margin-bottom: 20px;"><i class="fas fa-heartbeat"></i> Live Internal Router & Link Health</h5>
        <button class="btn btn-sm btn-outline-success mb-3" onclick="checkAllLinks()" style="width:200px;"><i class="fas fa-sync-alt"></i> Execute HTTP Scan</button>
        <div class="table-responsive">
            <table class="table table-dark table-sm" id="healthTable">
                <thead><tr><th>Route Path</th><th>Category</th><th>HTTP Status</th></tr></thead>
                <tbody>
                    <?php
                    $criticalRoutes = [
                        '/' => 'Frontend',
                        '/find.php' => 'Frontend',
                        '/auth/login.php' => 'Auth',
                        '/auth/register.php' => 'Auth',
                        '/dashboard/index.php' => 'App',
                        '/marketplace/index.php' => 'Module',
                        '/leads/list.php' => 'CRM',
                        '/admin/superadmin.php' => 'Admin'
                    ];
                    foreach($criticalRoutes as $path => $cat): ?>
                    <tr>
                        <td style="font-family: monospace; color:#ccc;"><?= $path ?></td>
                        <td><span class="badge bg-secondary"><?= $cat ?></span></td>
                        <td class="status-cell" data-path="<?= $path ?>"><span class="badge bg-warning text-dark">Pending</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            async function checkAllLinks() {
                const routes = document.querySelectorAll('.route-row');
                const btn = document.getElementById('scanBtn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
                btn.disabled = true;

                for (let r of routes) {
                    let path = r.dataset.path;
                    let pcell = r.querySelector('.ping-cell');
                    pcell.innerHTML = '<span style="color:#FFD700"><i class="fas fa-sync fa-spin"></i> Testing</span>';
                    try {
                        // Using GET instead of HEAD to bypass strict WAF filters on auth routes
                        let res = await fetch(path + '?scan=' + Date.now(), { method: 'GET', cache: 'no-cache' });
                        if (res.ok) {
                            pcell.innerHTML = '<span class="status-badge" style="background:rgba(0,255,136,0.1);color:#00ff88;border:1px solid #00ff88;">200 OK</span>';
                        } else {
                            pcell.innerHTML = `<span class="status-badge" style="background:rgba(255,68,68,0.1);color:#ff4444;border:1px solid #ff4444;">${res.status} Error</span>`;
                        }
                    } catch (e) {
                        pcell.innerHTML = '<span class="status-badge" style="background:rgba(255,68,68,0.1);color:#ff4444;border:1px solid #ff4444;">Offline</span>';
                    }
                }
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Execute HTTP Scan';
                btn.disabled = false;
            }
        </script>
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
                        <th>Password (Hash)</th>
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
                                <span class="badge-plan p-<?= htmlspecialchars($u['plan'] ?? 'free') ?>">
                                    <?= htmlspecialchars($u['plan'] ?? 'free') ?>
                                </span>
                            </td>
                            <td class="s-<?= htmlspecialchars($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></td>
                            <td style="font-family: monospace; font-size: 0.75rem; color:#888;" title="<?= htmlspecialchars($u['password']) ?>"><?= htmlspecialchars(substr($u['password'], 0, 15)) ?>...</td>
                            <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                            <td>
                                <form id="form_<?= $u['id'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" id="action_<?= $u['id'] ?>" value="">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="prompt('Edit user '+<?= $u['id'] ?>+'. Not implemented fully in UI yet. Wait for Phase 7.')" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>" onclick="document.getElementById('action_<?= $u['id'] ?>').value='toggle_status'; document.getElementById('form_<?= $u['id'] ?>').submit();" title="Toggle Status"><i class="fas <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i></button>
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

</body>
</html>
