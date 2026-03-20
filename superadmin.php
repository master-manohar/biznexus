<?php
session_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes_functions.php';

$uid = (int)$_SESSION['user_id'];
$adminCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$adminCheck->execute([$uid]);
$adminRole = $adminCheck->fetchColumn();
if ($adminRole !== 'admin') {
    http_response_code(403);
    echo "<div style='text-align:center;padding:80px;font-family:Inter,sans-serif;background:#0a0a0f;color:#ff4d6d;min-height:100vh;'><h2>Access Denied</h2><p>Super Admin only.</p><a href='/dashboard/index.php' style='color:#FFD700;'>Go to Dashboard</a></div>";
    exit;
}

// -- POST Actions --------------------------------------------------------------------------------
$action = $_POST['action'] ?? '';
if ($action === 'toggle_status') {
    $tid = (int)$_POST['user_id'];
    $cur = $pdo->prepare("SELECT status FROM users WHERE id=?"); $cur->execute([$tid]);
    $ns = $cur->fetchColumn() === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$ns, $tid]);
} elseif ($action === 'delete_user') {
    $tid = (int)$_POST['user_id'];
    $pdo->beginTransaction();
    try {
        foreach(["notifications","coin_transactions","meetings","voocoin_balances","lead_dispatches","member_badges"] as $t) {
            try { $pdo->prepare("DELETE FROM $t WHERE user_id=?")->execute([$tid]); } catch(Exception $e){}
        }
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$tid]);
        $pdo->commit();
    } catch(Exception $e) { $pdo->rollBack(); }
} elseif ($action === 'award_badge') {
    $tid = (int)$_POST['target_user_id']; $btype = $_POST['badge_type']; $blabel = $_POST['badge_label'];
    if ($tid && $btype) $pdo->prepare("INSERT INTO member_badges (user_id, badge_type, label, awarded_by) VALUES (?,?,?,?)")->execute([$tid, $btype, $blabel, $uid]);
} elseif ($action === 'assign_group_role') {
    $tid = (int)$_POST['target_user_id'];
    $role = in_array($_POST['group_role'], ['president','vice_president','gen_secretary','joint_secretary','treasurer','member']) ? $_POST['group_role'] : 'member';
    if ($tid) {
        if ($role === 'president') {
            $g = $pdo->prepare("SELECT group_id FROM users WHERE id=?"); $g->execute([$tid]);
            $gid = $g->fetchColumn();
            if ($gid) {
                $pdo->prepare("UPDATE users SET group_role='member' WHERE group_id=? AND group_role='president'")->execute([$gid]);
                $pdo->prepare("UPDATE groups SET president_user_id=?, term_started_at=NOW() WHERE id=?")->execute([$tid, $gid]);
            }
        }
        $pdo->prepare("UPDATE users SET group_role=? WHERE id=?")->execute([$role, $tid]);
        $rl = ucfirst(str_replace('_',' ',$role));
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?,?,?,'group',NOW())")->execute([$tid, "Role Updated", "You have been assigned: $rl"]);
    }
} elseif ($action === 'broadcast_news') {
    $title = trim($_POST['title']); $msg = trim($_POST['message']);
    if ($title && $msg) $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) SELECT id,?,?,'news',NOW() FROM users WHERE status='active'")->execute([$title,$msg]);
} elseif ($action === 'create_group') {
    $gname = trim($_POST['gname']); $tier = $_POST['gtier'] ?? 'Nexus'; $cap = (int)($_POST['gcap'] ?? 100);
    if ($gname) $pdo->prepare("INSERT INTO groups (name, tier, max_members, is_active, is_active_group, created_by, created_at) VALUES (?,?,?,1,1,?,NOW())")->execute([$gname, $tier, $cap, $uid]);
} elseif ($action === 'reset_password') {
    $tid = (int)$_POST['user_id']; $np = trim($_POST['new_password']);
    if ($tid && strlen($np)>=6) $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($np, PASSWORD_DEFAULT), $tid]);
} elseif ($action === 'edit_user') {
    $tid = (int)$_POST['user_id'];
    $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=?")->execute([trim($_POST['uname']), trim($_POST['uemail']), trim($_POST['uphone']), $_POST['ustatus'], $tid]);
} elseif ($action === 'update_lead_status') {
    $lid = (int)$_POST['lead_id']; $ns = $_POST['status'];
    if ($lid && in_array($ns, ['new','open','claimed','lapsed','closed'])) {
        $pdo->prepare("UPDATE public_leads SET status=? WHERE id=?")->execute([$ns, $lid]);
    }
} elseif ($action === 'assign_lead') {
    $lid = (int)$_POST['lead_id']; $mid = (int)$_POST['member_id'];
    if ($lid && $mid) {
        $pdo->prepare("UPDATE public_leads SET status='claimed', claimed_by_member_id=?, claimed_at=NOW() WHERE id=?")->execute([$mid, $lid]);
        try { sendNotification($pdo, $mid, "Lead Assigned by Admin", "A lead has been manually assigned to you.", 'crm'); } catch(Exception $e){}
    }
}

$active_section = $_GET['s'] ?? 'dashboard';

// -- Data ----------------------------------------------------------------------------------------
$stats = [
    'total'     => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active'    => $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
    'inactive'  => $pdo->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn(),
    'new_month' => $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn(),
    'groups'    => $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn(),
    'leads'     => $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn(),
    'badges'    => $pdo->query("SELECT COUNT(*) FROM member_badges")->fetchColumn(),
];
$renew_30 = $pdo->query("SELECT COUNT(*) FROM users WHERE plan_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$renew_60 = $pdo->query("SELECT COUNT(*) FROM users WHERE plan_expires_at BETWEEN DATE_ADD(NOW(), INTERVAL 30 DAY) AND DATE_ADD(NOW(), INTERVAL 60 DAY)")->fetchColumn();
$renew_90 = $pdo->query("SELECT COUNT(*) FROM users WHERE plan_expires_at BETWEEN DATE_ADD(NOW(), INTERVAL 60 DAY) AND DATE_ADD(NOW(), INTERVAL 90 DAY)")->fetchColumn();
$income_data = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as mo, SUM(amount) as total FROM coin_transactions WHERE type='debit' AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY mo DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_users   = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$groups      = $pdo->query("SELECT g.*, u.name as president_name, COUNT(um.id) as member_count FROM groups g LEFT JOIN users u ON g.president_user_id=u.id LEFT JOIN users um ON um.group_id=g.id GROUP BY g.id ORDER BY g.id ASC")->fetchAll(PDO::FETCH_ASSOC);
$badges      = $pdo->query("SELECT mb.*, u.name as user_name, a.name as awarded_by_name FROM member_badges mb LEFT JOIN users u ON mb.user_id=u.id LEFT JOIN users a ON mb.awarded_by=a.id ORDER BY mb.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$broadcasts  = $pdo->query("SELECT n.title, n.message, n.created_at, COUNT(*) as recipients FROM notifications n WHERE n.type='news' GROUP BY n.title, n.message, DATE(n.created_at) ORDER BY n.created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

// Members (paginated + filtered)
$per_page = 50; $members_page = max(1,(int)($_GET['p']??1)); $offset = ($members_page-1)*$per_page;
$mf_search = trim($_GET['mq']??''); $mf_status = trim($_GET['ms']??''); $mf_plan = trim($_GET['mp']??'');
$mwhere = ["1=1"]; $mparams = [];
if ($mf_search) { $mwhere[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $mparams = array_merge($mparams, ["%$mf_search%","%$mf_search%","%$mf_search%"]); }
if ($mf_status) { $mwhere[] = "status=?"; $mparams[] = $mf_status; }
if ($mf_plan)   { $mwhere[] = "plan=?"; $mparams[] = $mf_plan; }
$mwStr = implode(' AND ',$mwhere);
$mq = $pdo->prepare("SELECT * FROM users WHERE $mwStr ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$mq->execute($mparams); $members = $mq->fetchAll(PDO::FETCH_ASSOC);
$cq = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $mwStr"); $cq->execute($mparams); $total_members_count = $cq->fetchColumn();

// Leads (paginated + filtered)
$leads_per_page = 50; $leads_page = max(1,(int)($_GET['lp']??1)); $l_offset = ($leads_page-1)*$leads_per_page;
$lf_search = trim($_GET['lq']??''); $lf_status = trim($_GET['ls']??''); $lf_cat = trim($_GET['lc']??'');
$lwhere = ["1=1"]; $lparams = [];
if ($lf_search) { $lwhere[] = "(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.query LIKE ?)"; $lparams = array_merge($lparams, ["%$lf_search%","%$lf_search%","%$lf_search%","%$lf_search%"]); }
if ($lf_status) { $lwhere[] = "l.status=?"; $lparams[] = $lf_status; }
if ($lf_cat)    { $lwhere[] = "l.category=?"; $lparams[] = $lf_cat; }
$lwStr = implode(' AND ',$lwhere);
$lq = $pdo->prepare("SELECT l.*, u.name as claimed_by_name FROM public_leads l LEFT JOIN users u ON l.claimed_by_member_id=u.id WHERE $lwStr ORDER BY l.id DESC LIMIT $leads_per_page OFFSET $l_offset");
$lq->execute($lparams); $all_leads = $lq->fetchAll(PDO::FETCH_ASSOC);
$lcq = $pdo->prepare("SELECT COUNT(*) FROM public_leads WHERE $lwStr"); $lcq->execute($lparams); $total_leads_count = $lcq->fetchColumn();
$lead_categories = $pdo->query("SELECT DISTINCT category FROM public_leads WHERE category!=''")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Super Admin -- BizNexus Control Center';
require_once __DIR__ . '/includes/layout_start.php';
?>

<style>
.admin-subnav { border-top: 1px solid rgba(255,215,0,.15); margin-top: 8px; padding-top: 4px; }
.admin-subnav .adm-hdr { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#FFD700; padding:10px 16px 4px; }
.admin-subnav a { display:flex; align-items:center; gap:10px; padding:9px 14px; color:#9090b8; text-decoration:none; font-size:.83rem; font-weight:500; border-left:3px solid transparent; transition:.15s; }
.admin-subnav a:hover { background:rgba(255,215,0,.05); color:#e8e8f5; border-left-color:rgba(255,215,0,.3); }
.admin-subnav a.active { background:rgba(255,215,0,.09); color:#FFD700; border-left-color:#FFD700; font-weight:700; }
.sa-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,.07); }
.sa-title { font-size:1.3rem; font-weight:800; color:#e8e8f5; font-family:'Syne',sans-serif; }
.sa-subtitle { font-size:.8rem; color:#8888aa; margin-top:3px; }
.stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; margin-bottom:22px; }
.stat-card { background:#13131a; border:1px solid #2a2a3a; border-radius:12px; padding:16px 14px; }
.stat-card .s-num { font-size:1.7rem; font-weight:800; line-height:1; }
.stat-card .s-lbl { font-size:.68rem; color:#8888aa; margin-top:4px; text-transform:uppercase; letter-spacing:.8px; }
.sa-table { width:100%; border-collapse:collapse; }
.sa-table th { padding:8px 10px; text-align:left; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#8888aa; border-bottom:1px solid #2a2a3a; background:#0d0d16; }
.sa-table td { padding:9px 10px; border-bottom:1px solid rgba(42,42,58,.5); font-size:.82rem; color:#c0c0d8; }
.sa-table tr:hover td { background:rgba(255,255,255,.015); }
.sa-table-wrap { background:#13131a; border:1px solid #2a2a3a; border-radius:12px; overflow:hidden; }
.sa-card { background:#13131a; border:1px solid #2a2a3a; border-radius:14px; padding:20px; margin-bottom:18px; }
.pill { display:inline-block; padding:3px 9px; border-radius:20px; font-size:.65rem; font-weight:700; text-transform:uppercase; }
.pill-green { background:rgba(0,232,122,.12); color:#00e87a; border:1px solid rgba(0,232,122,.3); }
.pill-red { background:rgba(255,77,109,.12); color:#ff4d6d; border:1px solid rgba(255,77,109,.3); }
.pill-blue { background:rgba(68,136,255,.12); color:#4488ff; border:1px solid rgba(68,136,255,.3); }
.pill-gold { background:rgba(255,215,0,.12); color:#FFD700; border:1px solid rgba(255,215,0,.3); }
.ls-ok { background:#00e87a; } .ls-warn { background:#ffaa00; } .ls-err { background:#ff4d6d; }
.ls-dot { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:5px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar-nav');
    if (!sidebar) return;
    var s = '<?= htmlspecialchars($active_section) ?>';
    var items = [
        {k:'dashboard',i:'[D]',l:'Dashboard'},
        {k:'members',  i:'[U]',l:'Members'},
        {k:'groups',   i:'[G]',l:'Groups'},
        {k:'leads',    i:'[L]',l:'Leads Control'},
        {k:'badges',   i:'[A]',l:'Award Badges'},
        {k:'roles',    i:'[R]',l:'Assign Roles'},
        {k:'broadcast',i:'[B]',l:'Broadcast'},
        {k:'links',    i:'[L]',l:'Link Health'},
        {k:'agents',   i:'[A]',l:'Agents'},
        {k:'settings', i:'[S]',l:'Settings'},
    ];
    var div = document.createElement('div');
    div.className = 'admin-subnav';
    var h = '<div class="adm-hdr">[S] Admin Control</div>';
    items.forEach(function(it) {
        h += '<a href="/superadmin.php?s='+it.k+'"'+(it.k===s?' class="active"':'')+'>'+
             '<span style="font-size:.95rem">'+it.i+'</span><span>'+it.l+'</span></a>';
    });
    div.innerHTML = h;
    var lg = sidebar.querySelector('.sidebar-logout');
    if (lg) sidebar.insertBefore(div, lg); else sidebar.appendChild(div);
});
function openEdit(m) {
    document.getElementById('editModal').style.display='flex';
    document.getElementById('edit_uid').value=m.id;
    document.getElementById('edit_name').value=m.name||'';
    document.getElementById('edit_email').value=m.email||'';
    document.getElementById('edit_phone').value=m.phone||'';
    document.getElementById('edit_status').value=m.status||'active';
    document.getElementById('pw_uid').value=m.id;
}
function closeEdit(){document.getElementById('editModal').style.display='none';}
</script>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:14px 18px;background:#13131a;border:1px solid #2a2a3a;border-radius:12px;">
    <div>
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#FFD700;">Super Admin Control Center</div>
        <div style="font-size:1.1rem;font-weight:800;color:#e8e8f5;font-family:'Syne',sans-serif;">BizNexus -- Mission Control</div>
    </div>
    <div style="display:flex;gap:8px;">
        <?php foreach(['dashboard'=>'[D]','members'=>'[U]','groups'=>'[G]','broadcast'=>'[B]','links'=>'[L]'] as $k=>$ico): ?>
        <a href="?s=<?= $k ?>" style="padding:6px 12px;background:<?= $active_section===$k?'rgba(255,215,0,.15)':'rgba(255,255,255,.04)' ?>;border:1px solid <?= $active_section===$k?'rgba(255,215,0,.4)':'#2a2a3a' ?>;border-radius:8px;text-decoration:none;font-size:.78rem;color:<?= $active_section===$k?'#FFD700':'#9090b8' ?>;"><?= $ico ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($active_section === 'dashboard'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[D] Dashboard Overview</div><div class="sa-subtitle">Platform statistics -- <?= date('d M Y, h:i A') ?></div></div>
    <a href="?s=members" class="btn-gold">View Members -></a>
</div>
<div class="stat-grid">
    <div class="stat-card"><div class="s-num" style="color:#FFD700;"><?= number_format($stats['total']) ?></div><div class="s-lbl">Total Members</div></div>
    <div class="stat-card"><div class="s-num" style="color:#00e87a;"><?= number_format($stats['active']) ?></div><div class="s-lbl">Active</div></div>
    <div class="stat-card"><div class="s-num" style="color:#ff4d6d;"><?= number_format($stats['inactive']) ?></div><div class="s-lbl">Inactive</div></div>
    <div class="stat-card"><div class="s-num" style="color:#a259ff;"><?= number_format($stats['new_month']) ?></div><div class="s-lbl">New This Month</div></div>
    <div class="stat-card"><div class="s-num" style="color:#4488ff;"><?= number_format($stats['groups']) ?></div><div class="s-lbl">Groups</div></div>
    <div class="stat-card"><div class="s-num" style="color:#ff9900;"><?= number_format($stats['leads']) ?></div><div class="s-lbl">Leads</div></div>
    <div class="stat-card"><div class="s-num" style="color:#ff4d6d;"><?= number_format($renew_30) ?></div><div class="s-lbl">Expire in 30d</div></div>
    <div class="stat-card"><div class="s-num" style="color:#ffaa00;"><?= number_format($renew_60) ?></div><div class="s-lbl">Expire in 60d</div></div>
</div>
<?php endif; ?>

<?php if ($active_section === 'members'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[U] Member Management</div><div class="sa-subtitle"><?= number_format($total_members_count) ?> members total</div></div>
</div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
    <input type="hidden" name="s" value="members">
    <input type="text" name="mq" value="<?= htmlspecialchars($mf_search) ?>" class="form-control" placeholder="Search name/email/phone" style="width:220px;background:#0d0d16;border:1px solid #2a2a3a;color:#e8e8f5;border-radius:8px;padding:7px 11px;font-size:.83rem;">
    <select name="ms" class="form-select" style="width:140px;background:#0d0d16;border:1px solid #2a2a3a;color:#c0c0d8;border-radius:8px;padding:7px 11px;font-size:.83rem;">
        <option value="">All Status</option><option value="active" <?= $mf_status==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $mf_status==='inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <select name="mp" class="form-select" style="width:130px;background:#0d0d16;border:1px solid #2a2a3a;color:#c0c0d8;border-radius:8px;padding:7px 11px;font-size:.83rem;">
        <option value="">All Plans</option>
        <option value="free"     <?= $mf_plan==='free'    ?'selected':'' ?>>Free</option>
        <option value="silver"   <?= $mf_plan==='silver'  ?'selected':'' ?>>Silver</option>
        <option value="gold"     <?= $mf_plan==='gold'    ?'selected':'' ?>>Gold</option>
        <option value="platinum" <?= $mf_plan==='platinum'?'selected':'' ?>>Platinum</option>
    </select>
    <button type="submit" class="btn-gold">Filter</button>
    <a href="?s=members" style="padding:7px 14px;background:rgba(255,255,255,.06);border:1px solid #2a2a3a;border-radius:8px;color:#c0c0d8;font-size:.83rem;text-decoration:none;">Reset</a>
</form>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Plan</th><th>Days Left</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($members as $m): ?>
<?php
$m_plan     = $m['plan'] ?? 'free';
$m_exp      = $m['plan_expires_at'] ?? null;
$m_days     = $m_exp ? max(0,(int)((strtotime($m_exp)-time())/86400)) : 0;
$m_plan_col = ['free'=>'#666699','silver'=>'#c0c0c0','gold'=>'#FFD700','platinum'=>'#a259ff'][$m_plan] ?? '#666699';
$m_urgent   = $m_days > 0 && $m_days <= 7;
?>
<tr>
    <td style="color:#666699;"><?= $m['id'] ?></td>
    <td><strong style="color:#e8e8f5;"><?= htmlspecialchars($m['name']) ?></strong><?php if($m['is_verified']??0): ?> [v]<?php endif; ?></td>
    <td style="font-size:.8rem;"><?= htmlspecialchars($m['email']) ?></td>
    <td style="font-size:.8rem;"><?= htmlspecialchars($m['phone']??'--') ?></td>
    <td><span style="font-size:.72rem;font-weight:700;color:<?= $m_plan_col ?>;"><?= ucfirst($m_plan) ?></span></td>
    <td>
        <?php if ($m_plan !== 'free' && $m_exp): ?>
        <div style="font-size:.75rem;font-weight:700;color:<?= $m_urgent?'#ff4d6d':($m_days<30?'#ffaa00':'#00e87a') ?>;"><?= $m_days ?>d</div>
        <div style="height:3px;width:50px;background:#1a1a28;border-radius:2px;margin-top:3px;overflow:hidden;"><div style="width:<?= min(100,round($m_days/30*100)) ?>%;height:100%;background:<?= $m_urgent?'#ff4d6d':'#00e87a' ?>;"></div></div>
        <?php else: ?>--<?php endif; ?>
    </td>
    <td><span class="pill pill-blue" style="font-size:.6rem;"><?= ucfirst(str_replace('_',' ',$m['group_role']??'member')) ?></span></td>
    <td><span class="pill <?= $m['status']==='active'?'pill-green':'pill-red' ?>"><?= ucfirst($m['status']) ?></span></td>
    <td style="font-size:.75rem;color:#8888aa;"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
    <td>
        <button onclick="openEdit(<?= htmlspecialchars(json_encode($m)) ?>)" style="background:rgba(68,136,255,.12);color:#4488ff;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.7rem;cursor:pointer;">Edit</button>
        <button onclick="openPlanModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['name']) ?>', '<?= $m_plan ?>')" style="background:rgba(255,215,0,.12);color:#FFD700;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.7rem;cursor:pointer;">Plan</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if ($active_section === 'groups'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[G] Group Management</div><div class="sa-subtitle"><?= count($groups) ?> active networking groups</div></div>
    <button onclick="document.getElementById('groupModal').style.display='flex'" class="btn-gold">+ New Group</button>
</div>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>ID</th><th>Group Name</th><th>Tier</th><th>Capacity</th><th>President</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($groups as $g): ?>
<?php 
$pr_name = $g['president_name'] ?: '<span style="color:#ff4d6d">Vacant</span>';
?>
<tr>
    <td><?= $g['id'] ?></td>
    <td><strong style="color:#e8e8f5;"><?= htmlspecialchars($g['name']) ?></strong></td>
    <td><span class="pill pill-gold"><?= $g['tier'] ?></span></td>
    <td><?= $g['member_count'] ?> / <?= $g['max_members'] ?></td>
    <td><?= $pr_name ?></td>
    <td><button class="btn-gold" style="padding:4px 8px;font-size:.7rem;">Manage</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div id="groupModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:380px;">
    <h5 style="color:#FFD700;margin-bottom:18px;">Create New Group</h5>
    <form method="POST">
        <input type="hidden" name="action" value="create_group">
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;display:block;margin-bottom:5px;">Group Name</label><input type="text" name="gname" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;" required></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;display:block;margin-bottom:5px;">Tier</label><select name="gtier" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"><option>Nexus</option><option>Elite</option><option>Global</option></select></div>
        <div class="mb-4"><label style="color:#8883;font-size:.75rem;display:block;margin-bottom:5px;">Max Capacity</label><input type="number" name="gcap" class="form-control" value="100" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Create</button><button type="button" onclick="document.getElementById('groupModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<?php if ($active_section === 'badges'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[A] Award Badges</div><div class="sa-subtitle">Recognize outstanding members</div></div>
</div>
<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:20px;">
    <div class="sa-table-wrap">
        <table class="sa-table">
        <thead><tr><th>Member</th><th>Badge</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($badges as $b): ?>
        <tr><td><?= htmlspecialchars($b['user_name']) ?></td><td><span class="pill pill-gold"><?= htmlspecialchars($b['label']) ?></span></td><td style="font-size:.7rem;"><?= date('d M', strtotime($b['created_at'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="sa-card">
        <h6 style="color:#FFD700;margin-bottom:15px;">Give New Award</h6>
        <form method="POST">
            <input type="hidden" name="action" value="award_badge">
            <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Select Member</label><select name="target_user_id" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"><?php foreach($all_users as $au): ?><option value="<?= $au['id'] ?>"><?= htmlspecialchars($au['name']) ?></option><?php endforeach; ?></select></div>
            <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Badge Type</label><select name="badge_type" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"><option value="gold_member">Gold Member</option><option value="top_referrer">Top Referrer</option><option value="community_star">Community Star</option></select></div>
            <div class="mb-4"><label style="color:#8883;font-size:.75rem;">Display Label</label><input type="text" name="badge_label" placeholder="e.g. Rising Star 2025" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
            <button type="submit" class="btn-gold w-100">Award Badge</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($active_section === 'broadcast'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[B] News Broadcast</div><div class="sa-subtitle">Send notifications to all active users</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:20px;">
    <div class="sa-card">
        <h6 style="color:#FFD700;margin-bottom:15px;">New Broadcast</h6>
        <form method="POST">
            <input type="hidden" name="action" value="broadcast_news">
            <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Title</label><input type="text" name="title" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
            <div class="mb-4"><label style="color:#8883;font-size:.75rem;">Message</label><textarea name="message" rows="4" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></textarea></div>
            <button type="submit" class="btn-gold w-100">Send News</button>
        </form>
    </div>
    <div class="sa-table-wrap">
        <table class="sa-table">
        <thead><tr><th>Latest Broadcasts</th><th>Recipients</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($broadcasts as $br): ?>
        <tr><td><strong><?= htmlspecialchars($br['title']) ?></strong></td><td><?= $br['recipients'] ?></td><td style="font-size:.7rem;"><?= date('d M', strtotime($br['created_at'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($active_section === 'links'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[L] System Link Health</div><div class="sa-subtitle">Monitoring core page status</div></div>
</div>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>Page Name</th><th>System Path</th><th>Status</th></tr></thead>
<tbody>
<?php 
$check_links = [
    ['Dashboard','/dashboard/index.php'],['Leads','/leads/list.php'],['Meetings','/meetings/list.php'],
    ['Community','/community/index.php'],['Groups','/groups/tiers.php'],['Analytics','/analytics/index.php'],
    ['Superadmin','/superadmin.php'],['Sitemap','/sitemap.xml'],['Login','/auth/login.php']
];
foreach($check_links as $sl): ?>
<?php $ok = file_exists(__DIR__ . $sl[1]); ?>
<tr>
    <td><?= $sl[0] ?></td>
    <td style="color:#666699;font-size:.75rem;"><?= $sl[1] ?></td>
    <td><span class="ls-dot <?= $ok?'ls-ok':'ls-err' ?>"></span> <span style="font-size:.75rem;"><?= $ok?'Operational':'Missing' ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if ($active_section === 'roles'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[R] Assign Group Roles</div><div class="sa-subtitle">Manage leadership in networking groups</div></div>
</div>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>ID</th><th>Member</th><th>Group</th><th>Current Role</th><th>Actions</th></tr></thead>
<tbody>
<?php 
$rq = $pdo->query("SELECT u.id, u.name, u.group_role, g.name as gname FROM users u LEFT JOIN groups g ON u.group_id=g.id WHERE u.status='active' ORDER BY u.id DESC LIMIT 100");
while($rm = $rq->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
    <td><?= $rm['id'] ?></td>
    <td><strong><?= htmlspecialchars($rm['name']) ?></strong></td>
    <td><?= $rm['gname'] ? htmlspecialchars($rm['gname']) : '--' ?></td>
    <td><span class="pill pill-blue"><?= ucfirst(str_replace('_',' ',$rm['group_role']??'member')) ?></span></td>
    <td><button onclick="openRoleModal(<?= $rm['id'] ?>, '<?= htmlspecialchars($rm['name']) ?>')" class="btn-gold" style="padding:4px 8px;font-size:.7rem;">Role</button></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<div id="roleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:340px;">
    <h5 style="color:#FFD700;margin-bottom:4px;">Assign Group Role</h5>
    <p id="roleMemberName" style="color:#8888aa;font-size:.8rem;margin-bottom:20px;"></p>
    <form method="POST">
        <input type="hidden" name="action" value="assign_group_role">
        <input type="hidden" name="target_user_id" id="roleUid">
        <div class="mb-4"><label style="color:#8883;font-size:.75rem;display:block;margin-bottom:8px;">New Role</label>
        <select name="group_role" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:10px;border-radius:8px;">
            <option value="president">President</option>
            <option value="vice_president">Vice President</option>
            <option value="gen_secretary">Gen Secretary</option>
            <option value="joint_secretary">Joint Secretary</option>
            <option value="treasurer">Treasurer</option>
            <option value="member">Member</option>
        </select></div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Assign</button><button type="button" onclick="document.getElementById('roleModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php if ($active_section === 'leads'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[L] Leads Control Center</div><div class="sa-subtitle"><?= $total_leads_count ?> total leads captured via AI Engine</div></div>
</div>

<!-- Leads Filters -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    <input type="hidden" name="s" value="leads">
    <input type="text" name="lq" value="<?= htmlspecialchars($lf_search) ?>" placeholder="Search name, email, query..." style="background:#13131a;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;flex:1;min-width:200px;">
    <select name="ls" style="background:#13131a;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;">
        <option value="">All Status</option>
        <option value="new" <?= $lf_status==='new'?'selected':'' ?>>New</option>
        <option value="claimed" <?= $lf_status==='claimed'?'selected':'' ?>>Claimed</option>
        <option value="closed" <?= $lf_status==='closed'?'selected':'' ?>>Closed</option>
        <option value="lapsed" <?= $lf_status==='lapsed'?'selected':'' ?>>Lapsed</option>
    </select>
    <select name="lc" style="background:#13131a;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;">
        <option value="">All Categories</option>
        <?php foreach($lead_categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $lf_cat===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-gold" style="padding:8px 20px;">Filter</button>
    <?php if($lf_search || $lf_status || $lf_cat): ?><a href="?s=leads" style="padding:8px 15px;background:#2a2a3a;color:#888;border-radius:8px;text-decoration:none;">Clear</a><?php endif; ?>
</form>

<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>ID</th><th>Lead Info</th><th>Details</th><th>Assigned To</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($all_leads as $l): ?>
<tr>
    <td><?= $l['id'] ?></td>
    <td>
        <strong style="color:#e8e8f5;"><?= htmlspecialchars($l['name']) ?></strong><br>
        <span style="font-size:.7rem;color:#8888aa;"><?= htmlspecialchars($l['phone']) ?> | <?= htmlspecialchars($l['email']) ?></span>
    </td>
    <td>
        <div style="font-size:.75rem;color:#FFD700;"><?= htmlspecialchars($l['category']) ?> IN <?= htmlspecialchars($l['city']) ?></div>
        <div style="font-size:.72rem;color:#888;max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($l['query']) ?>"><?= htmlspecialchars($l['query']) ?></div>
    </td>
    <td>
        <?php if($l['claimed_by_name']): ?>
            <span style="color:#4488ff;"><?= htmlspecialchars($l['claimed_by_name']) ?></span>
        <?php else: ?>
            <span style="color:#666;">Unassigned</span>
        <?php endif; ?>
    </td>
    <td><span class="pill <?= $l['status']==='claimed'?'pill-blue':($l['status']==='closed'?'pill-red':'pill-green') ?>"><?= ucfirst($l['status']) ?></span></td>
    <td>
        <div style="display:flex;gap:5px;">
            <button onclick="openAssignModal(<?= $l['id'] ?>, '<?= htmlspecialchars($l['name']) ?>')" class="btn-gold" style="padding:4px 8px;font-size:.65rem;background:rgba(255,215,0,.15);">Assign</button>
            <button onclick="openLeadStatusModal(<?= $l['id'] ?>, '<?= $l['status'] ?>')" style="background:rgba(255,255,255,.05);color:#fff;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.65rem;cursor:pointer;">Status</button>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Leads Pagination -->
<?php if($total_leads_count > $leads_per_page): ?>
<div style="display:flex;gap:5px;margin-top:20px;justify-content:center;">
    <?php for($i=1; $i<=ceil($total_leads_count/$leads_per_page); $i++): ?>
    <a href="?s=leads&lp=<?= $i ?>&lq=<?= urlencode($lf_search) ?>&ls=<?= urlencode($lf_status) ?>&lc=<?= urlencode($lf_cat) ?>" style="padding:5px 10px;background:<?= $leads_page==$i?'#FFD700':'#13131a' ?>;color:<?= $leads_page==$i?'#000':'#888' ?>;border-radius:4px;text-decoration:none;font-size:.75rem;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Assign Lead Modal -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:380px;">
    <h5 style="color:#FFD700;margin-bottom:4px;">Assign Lead</h5>
    <p id="assignLeadName" style="color:#8888aa;font-size:.8rem;margin-bottom:20px;"></p>
    <form method="POST">
        <input type="hidden" name="action" value="assign_lead">
        <input type="hidden" name="lead_id" id="assignLid">
        <div class="mb-4">
            <label style="color:#8883;font-size:.75rem;display:block;margin-bottom:8px;">Select Member</label>
            <select name="member_id" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:10px;border-radius:8px;">
                <?php foreach($all_users as $au): ?>
                <option value="<?= $au['id'] ?>"><?= htmlspecialchars($au['name']) ?> (<?= htmlspecialchars($au['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Assign Now</button><button type="button" onclick="document.getElementById('assignModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>

<!-- Lead Status Modal -->
<div id="leadStatusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:340px;">
    <h5 style="color:#FFD700;margin-bottom:18px;">Update Lead Status</h5>
    <form method="POST">
        <input type="hidden" name="action" value="update_lead_status">
        <input type="hidden" name="lead_id" id="statusLid">
        <div class="mb-4">
            <select name="status" id="statusSelect" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:10px;border-radius:8px;">
                <option value="new">New / Open</option>
                <option value="claimed">Claimed</option>
                <option value="lapsed">Lapsed</option>
                <option value="closed">Closed / Finished</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Update</button><button type="button" onclick="document.getElementById('leadStatusModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<?php if ($active_section === 'agents'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[A] Automation Agents</div><div class="sa-subtitle">Status of system background processes</div></div>
</div>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>Agent Name</th><th>Focus</th><th>Last Run</th><th>Status</th></tr></thead>
<tbody>
<?php 
$agents = [
    ['SEO Agent','Sitemap & Meta','agent/seo_agent.php','Active'],
    ['Social Agent','Content Gen','agent/social_agent.php','Active'],
    ['Trust Cron','Member Scores','agent/trust_cron.php','Active'],
    ['WA Cron','WhatsApp Queue','agent/wa_cron.php','Active'],
];
foreach($agents as $a): ?>
<tr>
    <td><strong><?= $a[0] ?></strong></td>
    <td style="font-size:.75rem;"><?= $a[1] ?></td>
    <td style="font-size:.7rem;"><?= date('d M, h:i A') ?></td>
    <td><span class="ls-dot ls-ok"></span> <span style="font-size:.75rem;"><?= $a[3] ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if ($active_section === 'settings'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[S] System Settings</div><div class="sa-subtitle">Global configuration & toggles</div></div>
</div>
<div class="sa-card" style="max-width:500px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #2a2a3a;">
        <div><strong>Maintenance Mode</strong><div style="font-size:.7rem;color:#888;">Disable public site access</div></div>
        <div style="width:40px;height:22px;background:#2a2a3a;border-radius:20px;position:relative;"><div style="width:18px;height:18px;background:#c0c0d8;border-radius:50%;position:absolute;top:2px;left:2px;"></div></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #2a2a3a;">
        <div><strong>New Registrations</strong><div style="font-size:.7rem;color:#888;">Allow new users to sign up</div></div>
        <div style="width:40px;height:22px;background:rgba(0,232,122,.2);border-radius:20px;position:relative;border:1px solid rgba(0,232,122,.4);"><div style="width:18px;height:18px;background:#00e87a;border-radius:50%;position:absolute;top:1px;right:2px;"></div></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div><strong>Live Chat Support</strong><div style="font-size:.7rem;color:#888;">Show help widget to users</div></div>
        <div style="width:40px;height:22px;background:rgba(0,232,122,.2);border-radius:20px;position:relative;border:1px solid rgba(0,232,122,.4);"><div style="width:18px;height:18px;background:#00e87a;border-radius:50%;position:absolute;top:1px;right:2px;"></div></div>
    </div>
</div>
<?php endif; ?>

<div id="planModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:380px;" class="sa-form">
    <h5 style="color:#FFD700;margin-bottom:4px;">Manage Membership Plan</h5>
    <p id="planMemberName" style="color:#8888aa;font-size:.8rem;margin-bottom:20px;"></p>
    <form method="POST">
        <input type="hidden" name="action" value="edit_plan">
        <input type="hidden" name="user_id" id="planUid">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
            <?php foreach(['free','silver','gold','platinum'] as $p): ?>
            <label style="cursor:pointer;"><input type="radio" name="new_plan" value="<?= $p ?>" id="pr_<?= $p ?>" style="display:none;"><div class="prb" data-plan="<?= $p ?>" style="padding:10px;border:1px solid #2a2a3a;border-radius:8px;text-align:center;font-size:.8rem;color:#c0c0d8;"><?= ucfirst($p) ?></div></label>
            <?php endforeach; ?>
        </div>
        <div id="daysRow" style="margin-bottom:14px;">
            <label style="font-size:.75rem;color:#8888aa;margin-bottom:6px;display:block;">Duration (Days)</label>
            <input type="number" name="plan_days" id="planDaysInput" class="form-control" value="30" style="background:#0d0d16;border:1px solid #2a2a3a;color:#e8e8f5;width:100%;padding:8px;border-radius:6px;">
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Update</button><button type="button" onclick="closePlanModal()" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>

<script>
function openPlanModal(uid, name, curPlan) {
    document.getElementById('planUid').value = uid;
    document.getElementById('planMemberName').textContent = 'User: ' + name;
    document.querySelectorAll('.prb').forEach(b => {
        const p = b.getAttribute('data-plan');
        b.style.borderColor = (p === curPlan) ? '#FFD700' : '#2a2a3a';
        b.onclick = () => {
            document.getElementById('pr_' + p).checked = true;
            highlightPlan(p);
            document.getElementById('daysRow').style.display = (p === 'free') ? 'none' : 'block';
        };
    });
    document.getElementById('planModal').style.display = 'flex';
}
function highlightPlan(plan) {
    document.querySelectorAll('.prb').forEach(b => b.style.borderColor = (b.getAttribute('data-plan') === plan) ? '#FFD700' : '#2a2a3a');
}
function closePlanModal(){ document.getElementById('planModal').style.display = 'none'; }
function openRoleModal(uid, name) {
    document.getElementById('roleModal').style.display = 'flex';
    document.getElementById('roleUid').value = uid;
    document.getElementById('roleMemberName').textContent = 'User: ' + name;
}
function openAssignModal(lid, name) {
    document.getElementById('assignModal').style.display = 'flex';
    document.getElementById('assignLid').value = lid;
    document.getElementById('assignLeadName').textContent = 'Lead: ' + name;
}
function openLeadStatusModal(lid, curStatus) {
    document.getElementById('leadStatusModal').style.display = 'flex';
    document.getElementById('statusLid').value = lid;
    document.getElementById('statusSelect').value = curStatus;
}
</script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
