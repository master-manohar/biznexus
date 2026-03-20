<?php
// /admin/leads.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

// Super Admin Check
$uid = (int)$_SESSION['user_id'];
global $pdo;

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    die("Access denied. Super Admin only.");
}

// Fetch all leads
$stmtLeads = $pdo->prepare("SELECT * FROM public_leads ORDER BY created_at DESC");
$stmtLeads->execute();
$leads = $stmtLeads->fetchAll();

// Fetch dispatches mapping
$stmtDispatches = $pdo->prepare("SELECT * FROM lead_dispatches ORDER BY dispatch_rank ASC");
$stmtDispatches->execute();
$allDisps = $stmtDispatches->fetchAll();

// Group dispatches by lead_id
$dispatchesByLead = [];
foreach($allDisps as $d) {
    if (!isset($dispatchesByLead[$d['lead_id']])) {
        $dispatchesByLead[$d['lead_id']] = [];
    }
    $dispatchesByLead[$d['lead_id']][] = $d;
}

?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Lead Tracker - Super Admin</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap' rel='stylesheet'>
    <link href='/assets/css/biznexus.css' rel='stylesheet'>
    <style>
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .bg-new { background: rgba(0, 255, 136, 0.2); color: #00ff88; }
        .bg-claimed { background: rgba(255, 215, 0, 0.2); color: #FFD700; }
        .bg-closed { background: rgba(255, 68, 68, 0.2); color: #ff4455; }
        .member-list { font-size: 0.85rem; color: var(--text2); }
        .accordion-button { background: var(--card); color: var(--text); border: 1px solid var(--border); }
        .accordion-button:not(.collapsed) { background: var(--card2); color: var(--gold); box-shadow: none; }
        .accordion-body { background: var(--bg); border: 1px solid var(--border); border-top: none; }
    </style>
</head>
<body>

<div class='sidebar'>
    <div class='sidebar-logo'>⚡ Super Admin</div>
    <nav class='nav flex-column' style='flex:1'>
        <a class='nav-link' href='/admin/superadmin.php'>🏠 Dashboard</a>
        <a class='nav-link active' href='/admin/leads.php'>🔥 Lead Tracker</a>
        <!-- Other links exist in standard sidebar, keeping concise here -->
    </nav>
    <a href='/auth/logout.php' style='color:#ff4455;padding:16px 20px;text-decoration:none;'>🚪 Logout</a>
</div>

<div class='main p-4'>
    <h2 class="mb-4" style="font-family: 'Syne', sans-serif; font-weight: 800;">Lead Tracker</h2>

    <div class="card p-4" style="background: var(--card); border: 1px solid var(--border); border-radius: 14px;">
        <h5 style="color: var(--gold);">All Public Leads Generated via AI Engine</h5>
        
        <div class="accordion mt-4" id="leadsAccordion">
            <?php foreach ($leads as $idx => $lead): ?>
                <div class="accordion-item" style="border: none; margin-bottom: 10px; border-radius: 10px; overflow: hidden;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $lead['id'] ?>">
                            <strong><?= htmlspecialchars($lead['category']) ?></strong> &nbsp;in&nbsp; <?= htmlspecialchars($lead['city'] ?: 'Any') ?> &nbsp;—&nbsp; 
                            <?= date('M d, H:i', strtotime($lead['created_at'])) ?> &nbsp; 
                            <span class="badge badge-status bg-new ms-2"><?= htmlspecialchars($lead['total_members_notified']) ?> Members Notified</span>
                        </button>
                    </h2>
                    <div id="collapse<?= $lead['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#leadsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-md-5 border-end border-secondary">
                                    <h6 style="color: var(--gold);">Lead Contact Info</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($lead['name']) ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($lead['phone']) ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($lead['email'] ?: 'N/A') ?></p>
                                    <p class="mt-2 text-muted"><strong>Query:</strong> <?= htmlspecialchars($lead['query']) ?></p>
                                </div>
                                <div class="col-md-7">
                                    <h6 style="color: var(--gold);">Dispatched Members</h6>
                                    <?php if (isset($dispatchesByLead[$lead['id']]) && count($dispatchesByLead[$lead['id']]) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-dark table-sm table-borderless member-list">
                                                <thead>
                                                    <tr style="border-bottom: 1px solid var(--border);">
                                                        <th>Rank</th>
                                                        <th>Member</th>
                                                        <th>Business</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dispatchesByLead[$lead['id']] as $disp): ?>
                                                        <tr>
                                                            <td>#<?= $disp['dispatch_rank'] ?></td>
                                                            <td><a href="/profile/view.php?id=<?= $disp['member_id'] ?>" style="color: var(--text);"><?= htmlspecialchars($disp['member_name']) ?></a></td>
                                                            <td><?= htmlspecialchars($disp['business_name']) ?></td>
                                                            <td style="color: #00ff88;"><?= htmlspecialchars($disp['status']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No members were matched with this lead initially.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($leads) === 0): ?>
                <p class="text-muted">No leads have been generated yet.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>
