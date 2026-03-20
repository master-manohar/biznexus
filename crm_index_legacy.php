<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch leads grouped by stage
$stages = ['New', 'Contacted', 'Meeting', 'Proposal', 'Won'];

$leads_by_stage = [];
foreach ($stages as $stage) {
    $stmt = $pdo->prepare("SELECT l.*, c.name as company_name FROM crm_leads l LEFT JOIN companies c ON l.company_id = c.id WHERE l.user_id = ? AND l.stage = ? ORDER BY l.follow_up_date ASC");
    $stmt->execute([$user_id, $stage]);
    $leads_by_stage[$stage] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(deal_value) as total_value FROM crm_leads WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) as won_count, SUM(deal_value) as won_value FROM crm_leads WHERE user_id = ? AND stage = 'Won'");
$stmt->execute([$user_id]);
$won = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM crm_leads WHERE user_id = ? AND follow_up_date < CURDATE() AND stage != 'Won'");
$stmt->execute([$user_id]);
$overdue = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Pipeline – BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --card: #13131a;
            --gold: #FFD700;
            --green: #00ff88;
            --border: #2a2a3a;
            --text: #e0e0e0;
            --muted: #888;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: var(--card); border-right: 1px solid var(--border);
            z-index: 100; display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 24px 20px; border-bottom: 1px solid var(--border);
            font-size: 1.3rem; font-weight: 700; color: var(--gold);
            letter-spacing: 1px;
        }
        .sidebar-brand span { color: var(--green); }
        .sidebar-nav { padding: 16px 0; flex: 1; overflow-y: auto; }
        .nav-section { padding: 8px 20px; font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 8px; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 20px; color: var(--muted); text-decoration: none;
            font-size: 0.9rem; transition: all 0.2s; border-left: 3px solid transparent;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--gold); background: rgba(255,215,0,0.05);
            border-left-color: var(--gold);
        }
        .nav-link i { width: 18px; text-align: center; }

        /* Main */
        .main { margin-left: 240px; padding: 0; min-height: 100vh; }
        .topbar {
            background: var(--card); border-bottom: 1px solid var(--border);
            padding: 16px 32px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 1.4rem; font-weight: 700; color: var(--gold); }
        .topbar-actions { display: flex; gap: 12px; align-items: center; }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold), #ffaa00);
            color: #000; border: none; padding: 9px 20px;
            border-radius: 8px; font-weight: 600; font-size: 0.875rem;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(255,215,0,0.3); color: #000; }

        .btn-outline {
            background: transparent; color: var(--text); border: 1px solid var(--border);
            padding: 9px 20px; border-radius: 8px; font-weight: 500; font-size: 0.875rem;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-outline:hover { border-color: var(--gold); color: var(--gold); }

        /* Stats */
        .stats-bar {
            padding: 20px 32px; display: flex; gap: 16px;
            background: var(--card); border-bottom: 1px solid var(--border);
        }
        .stat-pill {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            border-radius: 10px; padding: 12px 20px; display: flex;
            align-items: center; gap: 12px; flex: 1;
        }
        .stat-pill .icon {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem;
        }
        .stat-pill .val { font-size: 1.3rem; font-weight: 700; }
        .stat-pill .lbl { font-size: 0.75rem; color: var(--muted); }

        /* Kanban */
        .kanban-wrapper {
            padding: 24px 24px; overflow-x: auto;
        }
        .kanban-board {
            display: flex; gap: 16px; min-width: 1000px;
        }
        .kanban-col {
            flex: 1; min-width: 220px;
        }
        .col-header {
            padding: 12px 14px; border-radius: 10px 10px 0 0;
            display: flex; align-items: center; justify-content: space-between;
            font-weight: 600; font-size: 0.875rem;
        }
        .col-header .badge-count {
            font-size: 0.75rem; padding: 2px 8px; border-radius: 20px;
            background: rgba(0,0,0,0.25); font-weight: 600;
        }
        .col-body {
            background: rgba(255,255,255,0.02); border: 1px solid var(--border);
            border-top: none; border-radius: 0 0 10px 10px;
            padding: 10px; min-height: 400px;
            display: flex; flex-direction: column; gap: 10px;
        }

        /* Stage Colors */
        .col-new .col-header    { background: rgba(100,149,237,0.15); color: #6495ED; border: 1px solid rgba(100,149,237,0.3); }
        .col-contacted .col-header { background: rgba(255,165,0,0.12); color: #FFA500; border: 1px solid rgba(255,165,0,0.3); }
        .col-meeting .col-header  { background: rgba(147,112,219,0.15); color: #9370DB; border: 1px solid rgba(147,112,219,0.3); }
        .col-proposal .col-header { background: rgba(0,191,255,0.12); color: #00BFFF; border: 1px solid rgba(0,191,255,0.3); }
        .col-won .col-header     { background: rgba(0,255,136,0.12); color: var(--green); border: 1px solid rgba(0,255,136,0.3); }

        /* Lead Card */
        .lead-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 14px; cursor: pointer;
            transition: all 0.2s; text-decoration: none; color: var(--text);
            display: block; position: relative; overflow: hidden;
        }
        .lead-card:hover { border-color: var(--gold); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); color: var(--text); }
        .lead-card.overdue { border-left: 3px solid #ff4444; }
        .lead-card.overdue::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, #ff4444, transparent);
        }

        .lead-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 4px; }
        .lead-company { font-size: 0.78rem; color: var(--muted); margin-bottom: 8px; }
        .lead-value {
            font-size: 0.85rem; font-weight: 700; color: var(--gold);
            display: inline-flex; align-items: center; gap: 4px;
        }
        .lead-meta {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--border);
            font-size: 0.75rem;
        }
        .follow-up { display: flex; align-items: center; gap: 4px; }
        .follow-up.overdue-text { color: #ff4444; font-weight: 600; }
        .follow-up.ok-text { color: var(--muted); }
        .priority-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .p-high { background: #ff4444; }
        .p-medium { background: #FFA500; }
        .p-low { background: var(--green); }

        .lead-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #ff8800);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700; color: #000;
            flex-shrink: 0;
        }

        .empty-col {
            text-align: center; padding: 40px 10px;
            color: var(--muted); font-size: 0.8rem;
        }
        .empty-col i { font-size: 1.5rem; margin-bottom: 8px; display: block; opacity: 0.4; }

        /* Search Bar */
        .search-bar {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            border-radius: 8px; padding: 9px 14px; color: var(--text);
            font-size: 0.875rem; width: 220px; outline: none;
        }
        .search-bar:focus { border-color: var(--gold); }
        .search-bar::placeholder { color: var(--muted); }

        /* Filter Buttons */
        .filter-btn {
            padding: 7px 14px; border-radius: 8px; font-size: 0.8rem;
            border: 1px solid var(--border); background: transparent;
            color: var(--muted); cursor: pointer; transition: all 0.2s;
        }
        .filter-btn.active, .filter-btn:hover {
            border-color: var(--gold); color: var(--gold);
            background: rgba(255,215,0,0.07);
        }

        /* Tooltip */
        .overdue-badge {
            background: #ff444422; color: #ff4444; border: 1px solid #ff444440;
            border-radius: 4px; font-size: 0.68rem; padding: 1px 6px;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-bar { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">Biz<span>Nexus</span></div>
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="/dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="/crm/index.php" class="nav-link active"><i class="fas fa-funnel-dollar"></i> CRM Pipeline</a>
        <a href="/invoices/index.php" class="nav-link"><i class="fas fa-file-invoice"></i> Invoices</a>
        <a href="/inventory/index.php" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a>
        <div class="nav-section">Tools</div>
        <a href="/tasks/index.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a>
        <a href="/reports/index.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="/contacts/index.php" class="nav-link"><i class="fas fa-address-book"></i> Contacts</a>
        <div class="nav-section">Account</div>
        <a href="/profile.php" class="nav-link"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main -->
<div class="main">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h1><i class="fas fa-funnel-dollar me-2"></i>CRM Pipeline</h1>
            <div style="font-size:0.78rem;color:var(--muted);margin-top:2px;">Drag leads through your sales funnel</div>
        </div>
        <div class="topbar-actions">
            <input type="text" class="search-bar" placeholder="&#xf002; Search leads..." id="searchInput">
            <a href="add.php" class="btn-gold"><i class="fas fa-plus"></i> Add Lead</a>
            <a href="reports.php" class="btn-outline"><i class="fas fa-chart-line"></i> Reports</a>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-pill">
            <div class="icon" style="background:rgba(100,149,237,0.15);color:#6495ED;"><i class="fas fa-users"></i></div>
            <div>
                <div class="val"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="lbl">Total Leads</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="icon" style="background:rgba(255,215,0,0.12);color:var(--gold);"><i class="fas fa-rupee-sign"></i></div>
            <div>
                <div class="val">₹<?= number_format(($stats['total_value'] ?? 0), 0) ?></div>
                <div class="lbl">Pipeline Value</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="icon" style="background:rgba(0,255,136,0.12);color:var(--green);"><i class="fas fa-trophy"></i></div>
            <div>
                <div class="val">₹<?= number_format(($won['won_value'] ?? 0), 0) ?></div>
                <div class="lbl">Won (<?= $won['won_count'] ?? 0 ?> deals)</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="icon" style="background:rgba(255,68,68,0.12);color:#ff4444;"><i class="fas fa-exclamation-circle"></i></div>
            <div>
                <div class="val" style="color:#ff4444;"><?= $overdue['overdue'] ?? 0 ?></div>
                <div class="lbl">Overdue Follow-ups</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="icon" style="background:rgba(147,112,219,0.15);color:#9370DB;"><i class="fas fa-percentage"></i></div>
            <div>
                <div class="val">
                    <?php
                    $rate = ($stats['total'] > 0) ? round(($won['won_count'] / $stats['total']) * 100) : 0;
                    echo $rate . '%';
                    ?>
                </div>
                <div class="lbl">Win Rate</div>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-wrapper">

        <!-- Filter Row -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:0.8rem;color:var(--muted);">Filter:</span>
            <button class="filter-btn active" onclick="filterLeads('all', this)">All</button>
            <button class="filter-btn" onclick="filterLeads('overdue', this)"><i class="fas fa-clock"></i> Overdue</button>
            <button class="filter-btn" onclick="filterLeads('high', this)"><i class="fas fa-fire"></i> High Priority</button>
            <button class="filter-btn" onclick="filterLeads('today', this)"><i class="fas fa-calendar-day"></i> Due Today</button>
            <div style="margin-left:auto;font-size:0.8rem;color:var(--muted);">
                <i class="fas fa-info-circle"></i> Red border = overdue follow-up
            </div>
        </div>

        <div class="kanban-board" id="kanbanBoard">

            <?php
            $stageClasses = [
                'New' => 'col-new',
                'Contacted' => 'col-contacted',
                'Meeting' => 'col-meeting',
                'Proposal' => 'col-proposal',
                'Won' => 'col-won',
            ];
            $stageIcons = [
                'New' => 'fa-star',
                'Contacted' => 'fa-phone',
                'Meeting' => 'fa-handshake',
                'Proposal' => 'fa-file-alt',
                'Won' => 'fa-trophy',
            ];
            $today = date('Y-m-d');
            foreach ($stages as $stage):
                $leads = $leads_by_stage[$stage];
                $cls = $stageClasses[$stage];
                $icon = $stageIcons[$stage];
            ?>
            <div class="kanban-col <?= $cls ?>" data-stage="<?= $stage ?>">
                <div class="col-header">
                    <span><i class="fas <?= $icon ?> me-1"></i><?= $stage ?></span>
                    <span class="badge-count"><?= count($leads) ?></span>
                </div>
                <div class="col-body" id="stage-<?= strtolower($stage) ?>">
                    <?php if (empty($leads)): ?>
                    <div class="empty-col">
                        <i class="fas fa-inbox"></i>
                        No leads here yet
                    </div>
                    <?php else: ?>
                    <?php foreach ($leads as $lead):
                        $is_overdue = !empty($lead['follow_up_date']) && $lead['follow_up_date'] < $today && $stage !== 'Won';
                        $is_today = !empty($lead['follow_up_date']) && $lead['follow_up_date'] === $today;
                        $initials = strtoupper(substr($lead['contact_name'] ?? $lead['name'] ?? 'L', 0, 1));
                        $priority = $lead['priority'] ?? 'Medium';
                        $pclass = ['High'=>'p-high','Medium'=>'p-medium','Low'=>'p-low'][$priority] ?? 'p-medium';
                    ?>
                    <a href="view.php?id=<?= $lead['id'] ?>"
                       class="lead-card <?= $is_overdue ? 'overdue' : '' ?>"
                       data-priority="<?= strtolower($priority) ?>"
                       data-overdue="<?= $is_overdue ? '1' : '0' ?>"
                       data-today="<?= $is_today ? '1' : '0' ?>"
                       data-name="<?= htmlspecialchars(strtolower($lead['name'] ?? '')) ?>">

                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <div class="lead-avatar"><?= $initials ?></div>
                            <div style="flex:1;min-width:0;">
                                <div class="lead-name" style="display:flex;align-items:center;gap:6px;">
                                    <span class="priority-dot <?= $pclass ?>"></span>
                                    <?= htmlspecialchars($lead['name'] ?? 'Unnamed Lead') ?>
                                    <?php if ($is_overdue): ?>
                                    <span class="overdue-badge">LATE</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($lead['company_name']) || !empty($lead['contact_name'])): ?>
                                <div class="lead-company">
                                    <?php if (!empty($lead['contact_name'])): ?>
                                    <i class="fas fa-user" style="width:12px;"></i> <?= htmlspecialchars($lead['contact_name']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['company_name'])): ?>
                                    &nbsp;·&nbsp;<i class="fas fa-building" style="width:12px;"></i> <?= htmlspecialchars($lead['company_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($lead['deal_value']) && $lead['deal_value'] > 0): ?>
                        <div class="lead-value" style="margin-top:8px;">
                            <i class="fas fa-rupee-sign" style="font-size:0.75rem;"></i>
                            <?= number_format($lead['deal_value'], 0) ?>
                        </div>
                        <?php endif; ?>

                        <div class="lead-meta">
                            <div class="follow-up <?= $is_overdue ? 'overdue-text' : 'ok-text' ?>">
                                <?php if (!empty($lead['follow_up_date'])): ?>
                                <i class="fas fa-<?= $is_overdue ? 'exclamation-circle' : 'clock' ?>"></i>
                                <?php
                                    $fd = new DateTime($lead['follow_up_date']);
                                    $now = new DateTime($today);
                                    $diff = $now->diff($fd);
                                    if ($is_overdue) echo $diff->days . 'd overdue';
                                    elseif ($is_today) echo 'Due today';
                                    else echo $fd->format('M d');
                                ?>
                                <?php else: ?>
                                <i class="fas fa-minus-circle" style="opacity:0.4;"></i>
                                <span style="opacity:0.4;">No date</span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <?php if (!empty($lead['source'])): ?>
                                <span style="font-size:0.7rem;color:var(--muted);background:rgba(255,255,255,0.05);padding:2px 6px;border-radius:4px;">
                                    <?= htmlspecialchars($lead['source']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div><!-- /kanban-board -->
    </div><!-- /kanban-wrapper -->

</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.lead-card').forEach(card => {
        const name = card.dataset.name || '';
        card.style.display = (q === '' || name.includes(q)) ? 'block' : 'none';
    });
});

// Filter
function filterLeads(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.lead-card').forEach(card => {
        let show = true;
        if (type === 'overdue')  show = card.dataset.overdue === '1';
        if (type === 'high')     show = card.dataset.priority === 'high';
        if (type === 'today')    show = card.dataset.today === '1';
        card.style.display = show ? 'block' : 'none';
    });
}
</script>
</body>
</html>