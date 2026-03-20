<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes_functions.php';

$uid = (int)$_SESSION['user_id'];
$adminCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$adminCheck->execute([$uid]);
if ($adminCheck->fetchColumn() !== 'admin') { die("Access Denied"); }

$active_section = $_GET['s'] ?? 'leads';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'assign_lead') {
        $lid = (int)$_POST['lead_id']; $mid = (int)$_POST['member_id']; $type = $_POST['type'];
        if ($type === 'AI Engine') {
            $pdo->prepare("UPDATE public_leads SET status='claimed', claimed_by_member_id=?, assigned_at=NOW() WHERE id=?")->execute([$mid, $lid]);
        } else {
            $pdo->prepare("UPDATE referrals SET status='claimed', receiver_id=?, assigned_at=NOW() WHERE id=?")->execute([$mid, $lid]);
        }
        sendNotification($pdo, $mid, "Lead Manually Assigned", "Admin has assigned a lead to you.", 'crm');
    } elseif ($action === 'update_status') {
        $lid = (int)$_POST['lead_id']; $ns = $_POST['status']; $type = $_POST['type'];
        $tbl = ($type === 'AI Engine') ? 'public_leads' : 'referrals';
        $col = ($type === 'AI Engine') ? 'status' : 'status';
        $pdo->prepare("UPDATE $tbl SET $col=? WHERE id=?")->execute([$ns, $lid]);
    }
}

// Data fetching for Unified Leads
$lwhere = ["1=1"]; $lparams = [];
$lf_cat = $_GET['lc'] ?? '';
$lf_status = $_GET['ls'] ?? '';

if ($lf_cat) { $lwhere[] = "category = ?"; $lparams[] = $lf_cat; }
if ($lf_status) { $lwhere[] = "status = ?"; $lparams[] = $lf_status; }
$lwStr = implode(' AND ', $lwhere);

$query = "
(SELECT l.id, 'AI Engine' as type, l.name, l.phone, l.email, l.category, l.city, l.query, l.claimed_by_member_id as assigned_to, l.status, l.assigned_at, l.recirc_count, 0 as deal_value, 'System' as given_by, 'AI Engine' as given_by_group, l.created_at
 FROM public_leads l WHERE $lwStr)
UNION ALL
(SELECT r.id, 'Referral' as type, r.referred_name as name, r.phone, r.email, r.category, '' as city, r.notes as query, r.receiver_id as assigned_to, r.status, r.assigned_at, r.recirc_count, r.estimated_value as deal_value, u_sender.name as given_by, g.name as given_by_group, r.created_at
 FROM referrals r 
 LEFT JOIN users u_sender ON r.sender_id = u_sender.id
 LEFT JOIN groups g ON u_sender.group_id = g.id
 WHERE $lwStr)
ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($lparams, $lparams));
$all_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT DISTINCT category FROM (SELECT category FROM public_leads UNION SELECT category FROM referrals) as t WHERE category!=''")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Lead Command Center — BizNexus';
require_once __DIR__ . '/includes/layout_start.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
        <h2 style="font-family:'Syne',sans-serif;font-weight:800;margin:0;color:#e8e8f5;">🚀 Lead Command Center</h2>
        <p style="color:#8888aa;margin-top:5px;">Unified visibility for all AI Leads & Member Referrals</p>
    </div>
    <div style="display:flex;gap:10px;">
        <form method="GET" class="d-flex gap-2">
            <select name="ls" class="form-select" style="background:#13131a;border:1px solid #2a2a3a;color:#fff;font-size:.8rem;">
                <option value="">All Status</option>
                <option value="new" <?= $lf_status==='new'?'selected':'' ?>>New</option>
                <option value="claimed" <?= $lf_status==='claimed'?'selected':'' ?>>Claimed</option>
                <option value="closed" <?= $lf_status==='closed'?'selected':'' ?>>Closed</option>
            </select>
            <select name="lc" class="form-select" style="background:#13131a;border:1px solid #2a2a3a;color:#fff;font-size:.8rem;">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $lf_cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-gold">Filter</button>
        </form>
    </div>
</div>

<div class="card" style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;overflow:hidden;padding:0;">
    <table class="table table-dark table-hover" style="margin:0;font-size:.85rem;">
        <thead style="background:#0d0d16;">
            <tr>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Source / Group</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Lead / Info</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Category</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Assigned To</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Value (₹)</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Status</th>
                <th style="padding:15px;color:#8888aa;font-size:.7rem;text-transform:uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_leads as $l): ?>
            <tr>
                <td style="padding:15px;">
                    <span class="badge" style="background:<?= $l['type']==='Referral'?'rgba(68,136,255,.1)':'rgba(255,140,0,.1)' ?>;color:<?= $l['type']==='Referral'?'#4488ff':'#ff8c00' ?>;font-size:.6rem;"><?= $l['type'] ?></span><br>
                    <div style="font-size:.7rem;color:#666;margin-top:5px;"><?= htmlspecialchars($l['given_by_group']) ?></div>
                    <div style="font-size:.65rem;color:#444;">By: <?= htmlspecialchars($l['given_by']) ?></div>
                </td>
                <td style="padding:15px;">
                    <strong style="color:#e8e8f5;"><?= htmlspecialchars($l['name']) ?></strong><br>
                    <span style="font-size:.7rem;color:#8888aa;"><?= htmlspecialchars($l['phone']) ?></span>
                </td>
                <td style="padding:15px;">
                    <div style="font-size:.75rem;color:#FFD700;font-weight:600;"><?= htmlspecialchars($l['category']) ?></div>
                    <div style="font-size:.65rem;color:#555;"><?= htmlspecialchars($l['city']) ?></div>
                </td>
                <td style="padding:15px;">
                    <?php if($l['assigned_to']): ?>
                        <?php
                        $mStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                        $mStmt->execute([$l['assigned_to']]);
                        $mName = $mStmt->fetchColumn();
                        ?>
                        <span style="color:#00e87a;font-weight:600;"><?= htmlspecialchars($mName) ?></span>
                        <div style="font-size:.6rem;color:#444;">Assigned: <?= $l['assigned_at'] ? date('d M, H:i', strtotime($l['assigned_at'])) : '--' ?></div>
                        <?php if($l['recirc_count'] > 0): ?><div style="font-size:.6rem;color:#ff4d6d;">🔄 Recirculated: <?= $l['recirc_count'] ?></div><?php endif; ?>
                    <?php else: ?>
                        <span style="color:#ffaa00;font-weight:700;">🌐 Open Pool</span>
                    <?php endif; ?>
                </td>
                <td style="padding:15px;font-weight:700;color:<?= $l['deal_value']>0?'#FFD700':'#444' ?>;">
                    <?= $l['deal_value'] > 0 ? '₹' . number_format($l['deal_value']) : '--' ?>
                </td>
                <td style="padding:15px;">
                    <span class="badge" style="background:rgba(255,255,255,.05);color:#fff;border:1px solid #2a2a3a;"><?= ucfirst($l['status']) ?></span>
                </td>
                <td style="padding:15px;">
                    <div style="display:flex;gap:5px;">
                        <button onclick="openAssignModal('<?= $l['id'] ?>', '<?= $l['type'] ?>', '<?= htmlspecialchars($l['name']) ?>')" class="btn btn-sm btn-outline-warning" style="font-size:.65rem;padding:2px 8px;">Assign</button>
                        <button onclick="openStatusModal('<?= $l['id'] ?>', '<?= $l['type'] ?>', '<?= $l['status'] ?>')" class="btn btn-sm btn-outline-secondary" style="font-size:.65rem;padding:2px 8px;">Status</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modals for Assign & Status -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:380px;">
        <h5 style="color:#FFD700;margin-bottom:5px;">Assign Lead</h5>
        <p id="assignLeadTitle" style="color:#8888aa;font-size:.8rem;margin-bottom:20px;"></p>
        <form method="POST">
            <input type="hidden" name="action" value="assign_lead">
            <input type="hidden" name="lead_id" id="assignLid">
            <input type="hidden" name="type" id="assignType">
            <div class="mb-4">
                <label style="font-size:.7rem;color:#666;margin-bottom:5px;display:block;">Select Member</label>
                <select name="member_id" class="form-select" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;">
                    <?php 
                    $members = $pdo->query("SELECT id, name, category FROM users WHERE status='active' AND role='member' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach($members as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['category']??'General') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-gold w-100">Confirm Assignment</button>
                <button type="button" onclick="document.getElementById('assignModal').style.display='none'" class="btn btn-outline-secondary w-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="statusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:340px;">
        <h5 style="color:#FFD700;margin-bottom:20px;">Update Lead Status</h5>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="lead_id" id="statusLid">
            <input type="hidden" name="type" id="statusType">
            <div class="mb-4">
                <select name="status" id="statusSelect" class="form-select" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;">
                    <option value="new">New / Open</option>
                    <option value="claimed">Claimed / WIP</option>
                    <option value="closed">Closed / Finished</option>
                    <option value="lapsed">Lapsed / Lapsed</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-gold w-100">Update Status</button>
                <button type="button" onclick="document.getElementById('statusModal').style.display='none'" class="btn btn-outline-secondary w-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(id, type, name) {
    document.getElementById('assignLid').value = id;
    document.getElementById('assignType').value = type;
    document.getElementById('assignLeadTitle').innerText = '[' + type + '] ' + name;
    document.getElementById('assignModal').style.display = 'flex';
}
function openStatusModal(id, type, status) {
    document.getElementById('statusLid').value = id;
    document.getElementById('statusType').value = type;
    document.getElementById('statusSelect').value = status;
    document.getElementById('statusModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
