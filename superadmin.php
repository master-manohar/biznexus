<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes_functions.php';

$isReadOnly = true; // Temporary definition before we know the actual status on line 26
$top_nav = ['dashboard'=>'[D]','members'=>'[U]','leads'=>'[L]','agents'=>'[A]'];

$uid = (int)$_SESSION['user_id'];
// Fetch current user and group role
$stmt = $pdo->prepare("SELECT role, group_role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$u_res = $stmt->fetch();
$adminRole = $u_res['role'] ?? 'member';
$gRole = $u_res['group_role'] ?? 'member';

// Permission check: admin, moderator, or a group president
if (!in_array($adminRole, ['admin', 'moderator']) && $gRole !== 'president') {
    http_response_code(403);
    echo "<div style='text-align:center;padding:80px;font-family:Inter,sans-serif;background:#0a0a0f;color:#ff4d6d;min-height:100vh;'><h2>Access Denied</h2><p>Restricted Access only.</p><a href='/dashboard/index.php' style='color:#FFD700;'>Go to Dashboard</a></div>";
    exit;
}

$isReadOnly = ($adminRole !== 'admin');

// Scoping Logic:
// 1. Admin/Moderator sees everything.
// 2. A President (group_role) who is NOT an admin/moderator sees ONLY their group.
$mod_group_id = null;
$limit_to_group = false;

$stmt = $pdo->prepare("SELECT group_id, group_role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$uinfo = $stmt->fetch();
$my_group_id = $uinfo['group_id'] ?? null;
$my_group_role = $uinfo['group_role'] ?? '';

if ($adminRole === 'moderator') {
    $limit_to_group = false; // Moderator sees all members view-only (as requested)
} elseif ($adminRole !== 'admin' && $my_group_role === 'president') {
    $limit_to_group = true;
    $mod_group_id = $my_group_id;
}

// -- POST Actions (Admin Only) --------------------------------------------------------------------
$action = $_POST['action'] ?? '';
if ($action && $isReadOnly) {
    // Moderators cannot perform any POST actions
    $action = ''; 
    $error = "View Only: You do not have permission to modify data.";
}

if ($action === 'toggle_status') {
    $tid = (int)$_POST['user_id'];
    $cur = $pdo->prepare("SELECT status FROM users WHERE id=?"); $cur->execute([$tid]);
    $ns = $cur->fetchColumn() === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$ns, $tid]);
} elseif ($action === 'edit_plan') {
    $tid = (int)$_POST['user_id'];
    $new_plan = $_POST['new_plan'];
    $days = (int)($_POST['plan_days'] ?? 30);
    if ($tid && in_array($new_plan, ['free', 'silver', 'gold', 'platinum'])) {
        $expiry = ($new_plan === 'free') ? null : date('Y-m-d H:i:s', strtotime("+$days days"));
        $pdo->prepare("UPDATE users SET plan = ?, plan_expires_at = ? WHERE id = ?")->execute([$new_plan, $expiry, $tid]);
        $pName = ucfirst($new_plan);
        $expMsg = $expiry ? date('d M Y', strtotime($expiry)) : 'Never';
        sendNotification($pdo, $tid, 'Plan Updated', "Your membership has been manually updated to $pName by Admin. Expiry: $expMsg", 'system');
        header("Location: superadmin.php?s=members&msg=" . urlencode("User updated to $pName."));
        exit;
    }
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
    $gid = (int)($_POST['ugroup'] ?? 0);
    $grole = in_array($_POST['ugrole'] ?? '', ['president','vice_president','gen_secretary','joint_secretary','treasurer','member']) ? $_POST['ugrole'] : 'member';
    
    if ($grole === 'president' && $gid) {
        $pdo->prepare("UPDATE users SET group_role='member' WHERE group_id=? AND group_role='president'")->execute([$gid]);
        $pdo->prepare("UPDATE groups SET president_user_id=?, term_started_at=NOW() WHERE id=?")->execute([$tid, $gid]);
    }
    
    $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=?, group_id=?, group_role=?, category=? WHERE id=?")->execute([
        trim($_POST['uname']), trim($_POST['uemail']), trim($_POST['uphone']), $_POST['ustatus'], $gid ?: null, $grole, trim($_POST['ucat']), $tid
    ]);
} elseif ($action === 'update_lead_status') {
    $lid = (int)$_POST['lead_id']; $ns = $_POST['status']; $type = $_POST['type'] ?? 'AI Engine';
    $tbl = ($type === 'Referral') ? 'referrals' : 'public_leads';
    if ($lid && in_array($ns, ['new','open','claimed', 'lapsed', 'closed'])) {
        $pdo->prepare("UPDATE $tbl SET status=? WHERE id=?")->execute([$ns, $lid]);
    }
} elseif ($action === 'assign_lead') {
    $lid = (int)$_POST['lead_id']; $mid = (int)$_POST['member_id']; $type = $_POST['type'] ?? 'AI Engine';
    if ($lid && $mid) {
        if ($type === 'Referral') {
            $pdo->prepare("UPDATE referrals SET status='claimed', receiver_id=?, assigned_at=NOW() WHERE id=?")->execute([$mid, $lid]);
        } else {
            $pdo->prepare("UPDATE public_leads SET status='claimed', claimed_by_member_id=?, assigned_at=NOW() WHERE id=?")->execute([$mid, $lid]);
        }
        try { sendNotification($pdo, $mid, "Lead Assigned by Admin", "A lead has been manually assigned to you.", 'crm'); } catch(Exception $e){}
        // Economy Update: -50 VooCoins for manual assignment
        awardCoins($pdo, $mid, -50, "Lead Assigned: ID $lid");
    }
} elseif ($action === 'create_cat') {
    $cname = trim($_POST['cname']);
    if ($cname) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cname), '-'));
        $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)")->execute([$cname, $slug]);
    }
} elseif ($action === 'delete_cat') {
    $cid = (int)$_POST['cat_id'];
    if ($cid) $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cid]);
} elseif ($action === 'delegate_task') {
    $goal = trim($_POST['goal'] ?? '');
    if ($goal) {
        require_once __DIR__ . "/agent/supervisor.php";
        spawnTasks($goal);
        header("Location: superadmin.php?s=agents&msg=Task+Delegated");
        exit;
    }
} elseif ($action === 'cancel_task') {
    $tid = (int)$_POST['task_id'];
    if ($tid) $pdo->prepare("UPDATE agent_tasks SET status='cancelled' WHERE id=?")->execute([$tid]);
}

$active_section = $_GET['s'] ?? 'leads';

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
// Get only members who actually exist (for assignment and filtering)
$all_users = $pdo->query("SELECT u.id, u.name, u.email, g.name as group_name FROM users u LEFT JOIN groups g ON u.group_id = g.id WHERE u.status='active' AND u.name!='' ORDER BY u.name ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$groups      = $pdo->query("SELECT g.*, u.name as president_name, COUNT(um.id) as member_count FROM groups g LEFT JOIN users u ON g.president_user_id=u.id LEFT JOIN users um ON um.group_id=g.id GROUP BY g.id ORDER BY g.id ASC")->fetchAll(PDO::FETCH_ASSOC);
$badges      = $pdo->query("SELECT mb.*, u.name as user_name, a.name as awarded_by_name FROM member_badges mb LEFT JOIN users u ON mb.user_id=u.id LEFT JOIN users a ON mb.awarded_by=a.id ORDER BY mb.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$broadcasts  = $pdo->query("SELECT n.title, n.message, n.created_at, COUNT(*) as recipients FROM notifications n WHERE n.type='news' GROUP BY n.title, n.message, DATE(n.created_at) ORDER BY n.created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
// $ad_stats    = $pdo->query("SELECT referral_source as source, COUNT(*) as count FROM users WHERE referral_source IS NOT NULL AND referral_source != '' GROUP BY referral_source ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
$ad_stats = []; 

// Members (paginated + filtered)
$per_page = 50; $members_page = max(1,(int)($_GET['p']??1)); $offset = ($members_page-1)*$per_page;
$mf_search = trim($_GET['mq']??''); $mf_status = trim($_GET['ms']??''); $mf_plan = trim($_GET['mp']??''); $mf_group = (int)($_GET['mg']??0); $mf_cat = trim($_GET['mc']??'');
$mwhere = ["1=1"]; $mparams = [];
if ($limit_to_group && $mod_group_id) { $mwhere[] = "u.group_id=?"; $mparams[] = $mod_group_id; }
if ($mf_search) { $mwhere[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.business_name LIKE ?)"; $mparams = array_merge($mparams, ["%$mf_search%","%$mf_search%","%$mf_search%","%$mf_search%"]); }
if ($mf_status) { $mwhere[] = "u.status=?"; $mparams[] = $mf_status; }
if ($mf_plan)   { $mwhere[] = "u.plan=?"; $mparams[] = $mf_plan; }
if ($mf_group)  { $mwhere[] = "u.group_id=?"; $mparams[] = $mf_group; }
if ($mf_cat)    { $mwhere[] = "u.category=?"; $mparams[] = $mf_cat; }
$mwStr = implode(' AND ',$mwhere);
$mq = $pdo->prepare("SELECT u.*, g.name as group_name FROM users u LEFT JOIN groups g ON u.group_id = g.id WHERE $mwStr ORDER BY u.id DESC LIMIT $per_page OFFSET $offset");
$mq->execute($mparams); $members = $mq->fetchAll(PDO::FETCH_ASSOC);
$cq = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $mwStr"); $cq->execute($mparams); $total_members_count = $cq->fetchColumn();

// Leads (Unified: AI + Referrals)
$leads_per_page = 50; 
$leads_page = max(1, (int)($_GET['lp'] ?? 1));
$lf_search = trim($_GET['lq']??''); 
$lf_status = trim($_GET['ls']??''); 
$lf_cat = trim($_GET['lc']??'');
$lf_source = trim($_GET['lsrc']??'');
$lf_assignee = (int)($_GET['lmid']??0);
$lf_min_val = (int)($_GET['lmv']??0);

// Status Mapping Logic
$status_map = [
    'open'    => ['new', 'sent'],
    'claimed' => ['claimed', 'accepted', 'meeting_done', 'deal_in_progress'],
    'closed'  => ['closed', 'closed_won'],
    'lapsed'  => ['lapsed', 'closed_lost']
];

// Unified Filtering Logic (Applied after UNION for collation stability)
$lWhere = ["1=1"]; $lParams = [];
if ($lf_search) { 
    $lWhere[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR category LIKE ?)"; 
    $lParams = array_merge($lParams, ["%$lf_search%","%$lf_search%","%$lf_search%","%$lf_search%"]); 
}
if ($lf_status && isset($status_map[$lf_status])) { 
    $lWhere[] = "status IN (".implode(',', array_fill(0, count($status_map[$lf_status]), '?')).")"; 
    $lParams = array_merge($lParams, $status_map[$lf_status]); 
}
if ($lf_cat)    { $lWhere[] = "category = ?"; $lParams[] = $lf_cat; }
if ($lf_source === 'ai') { $lWhere[] = "type = 'AI Engine'"; }
if ($lf_source === 'referral') { $lWhere[] = "type = 'Referral'"; }
if ($lf_assignee) { $lWhere[] = "assigned_to = ?"; $lParams[] = $lf_assignee; }
if ($lf_min_val) { $lWhere[] = "deal_value >= ?"; $lParams[] = $lf_min_val; }

$glWhere = implode(' AND ', $lWhere);

$lq_str = "
SELECT * FROM (
    SELECT id, 
           CAST('AI Engine' AS CHAR) COLLATE utf8mb4_unicode_ci as type, 
           CAST(name AS CHAR) COLLATE utf8mb4_unicode_ci as name, 
           CAST(phone AS CHAR) COLLATE utf8mb4_unicode_ci as phone, 
           CAST(email AS CHAR) COLLATE utf8mb4_unicode_ci as email, 
           CAST(category AS CHAR) COLLATE utf8mb4_unicode_ci as category, 
           CAST(city AS CHAR) COLLATE utf8mb4_unicode_ci as city, 
           CAST(query AS CHAR) COLLATE utf8mb4_unicode_ci as query, 
           claimed_by_member_id as assigned_to, 
           CAST(status AS CHAR) COLLATE utf8mb4_unicode_ci as status, 
           assigned_at, recirc_count, 0 as deal_value, 
           CAST('System' AS CHAR) COLLATE utf8mb4_unicode_ci as given_by, 
           CAST('AI Engine' AS CHAR) COLLATE utf8mb4_unicode_ci as given_by_group, 
           created_at, lat, lng, 
           CAST(ai_strategy AS CHAR) COLLATE utf8mb4_unicode_ci as ai_strategy
     FROM public_leads
    UNION ALL
    SELECT r.id, 
           CAST('Referral' AS CHAR) COLLATE utf8mb4_unicode_ci as type, 
           CAST(r.referred_name AS CHAR) COLLATE utf8mb4_unicode_ci as name, 
           CAST(r.phone AS CHAR) COLLATE utf8mb4_unicode_ci as phone, 
           CAST(r.email AS CHAR) COLLATE utf8mb4_unicode_ci as email, 
           CAST(r.category AS CHAR) COLLATE utf8mb4_unicode_ci as category, 
           '' COLLATE utf8mb4_unicode_ci as city, 
           CAST(r.notes AS CHAR) COLLATE utf8mb4_unicode_ci as query, 
           r.receiver_id as assigned_to, 
           CAST(r.status AS CHAR) COLLATE utf8mb4_unicode_ci as status, 
           r.assigned_at, r.recirc_count, r.estimated_value as deal_value, 
           CAST(u.name AS CHAR) COLLATE utf8mb4_unicode_ci as given_by, 
           CAST(g.name AS CHAR) COLLATE utf8mb4_unicode_ci as given_by_group, 
           r.created_at, 0 as lat, 0 as lng,
           CAST(r.ai_strategy AS CHAR) COLLATE utf8mb4_unicode_ci as ai_strategy
     FROM referrals r LEFT JOIN users u ON r.sender_id = u.id LEFT JOIN groups g ON u.group_id = g.id
) as unified_leads
WHERE $glWhere
ORDER BY created_at DESC LIMIT 100";

$lq = $pdo->prepare($lq_str);
$lq->execute($lParams);
$all_leads = $lq->fetchAll(PDO::FETCH_ASSOC);
$total_leads_count = count($all_leads);

// Summary Counts
$sum_open = 0; $sum_claimed = 0; $sum_closed = 0; $total_value = 0;
foreach($all_leads as $sl) {
    if (in_array($sl['status'], $status_map['open'])) $sum_open++;
    elseif (in_array($sl['status'], $status_map['claimed'])) $sum_claimed++;
    elseif (in_array($sl['status'], $status_map['closed'])) $sum_closed++;
    $total_value += $sl['deal_value'];
}

$lead_categories = $pdo->query("SELECT DISTINCT category FROM (SELECT CAST(category AS CHAR) COLLATE utf8mb4_unicode_ci as category FROM public_leads UNION SELECT CAST(category AS CHAR) COLLATE utf8mb4_unicode_ci FROM referrals) as t WHERE category!=''")->fetchAll(PDO::FETCH_COLUMN);

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
    var isRO = <?= $isReadOnly ? 'true' : 'false' ?>;
    var items = [
        {k:'dashboard',i:'[D]',l:'Dashboard'},
        {k:'members',  i:'[U]',l:'Members'},
        {k:'groups',   i:'[G]',l:'Groups', restricted:true},
        {k:'categories',i:'[C]',l:'Categories', restricted:true},
        {k:'leads',    i:'[L]',l:'Leads Control'},
        {k:'badges',   i:'[A]',l:'Award Badges', restricted:true},
        {k:'roles',    i:'[R]',l:'Assign Roles', restricted:true},
        {k:'broadcast',i:'[B]',l:'Broadcast', restricted:true},
        {k:'referrals',i:'[🔗]',l:'Referral Growth', restricted:true},
        {k:'links',    i:'[L]',l:'Link Health', restricted:true},
        {k:'agents',   i:'[A]',l:'Agents', restricted:true},
        {k:'ads',      i:'[P]',l:'Campaign ROI', restricted:true},
        {k:'meet',     i:'[📅]',l:'Meeting Registrants', restricted:true},
        {k:'scout',    i:'[🔍]',l:'Lead Scout', restricted:true},
        {k:'settings', i:'[S]',l:'Settings', restricted:true},
    ];
    var div = document.createElement('div');
    div.className = 'admin-subnav';
    var h = '<div class="adm-hdr">[S] '+(isRO?'Moderator Console':'Admin Control')+'</div>';
    items.forEach(function(it) {
        if (isRO && it.restricted) return;
        h += '<a href="/superadmin.php?s='+it.k+'"'+(it.k===s?' class="active"':'')+'>'+
             '<span style="font-size:.95rem">'+it.i+'</span><span>'+it.l+'</span></a>';
    });
    div.innerHTML = h;
    var lg = sidebar.querySelector('.sidebar-logout');
    if (lg) sidebar.insertBefore(div, lg); else sidebar.appendChild(div);
    
    // Automation Pulse (Runs in background)
    setTimeout(function() {
        console.log("BizNexus System Pulse Sent...");
        fetch('/agent/system_pulse.php?key=BizCron2024')
            .then(r => r.text())
            .then(d => { console.log("Pulse Response:", d); });
    }, 5000);
});
function openEdit(m) {
    document.getElementById('editModal').style.display='flex';
    document.getElementById('edit_uid').value=m.id;
    document.getElementById('edit_name').value=m.name||'';
    document.getElementById('edit_email').value=m.email||'';
    document.getElementById('edit_phone').value=m.phone||'';
    document.getElementById('edit_status').value=m.status||'active';
    document.getElementById('edit_group').value=m.group_id||'0';
    document.getElementById('edit_grole').value=m.group_role||'member';
    document.getElementById('edit_cat').value=m.category||'';
    document.getElementById('pw_uid').value=m.id;
}
function closeEdit(){document.getElementById('editModal').style.display='none';}
</script>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:14px 18px;background:#13131a;border:1px solid #2a2a3a;border-radius:12px;">
    <div>
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#FFD700;">
            <?= $isReadOnly ? 'Limited Moderator Access' : 'Super Admin Control Center' ?>
        </div>
        <div style="font-size:1.1rem;font-weight:800;color:#e8e8f5;font-family:'Syne',sans-serif;">BizNexus -- Mission Control</div>
        <?php if($isReadOnly): ?><div style="font-size:.65rem;color:#ff4d6d;font-weight:700;margin-top:5px;">⚠️ VIEW-ONLY MODE ENABLED</div><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;">
        <?php 
        $top_nav = ['dashboard'=>'[D]','members'=>'[U]','leads'=>'[L]','agents'=>'[A]'];
                if (!$isReadOnly) $top_nav = array_merge($top_nav, ['groups'=>'[G]','referrals'=>'[🔗]','broadcast'=>'[B]','migration'=>'[M]','links'=>'[L]', 'ads'=>'[P]', 'scout'=>'[🔍]']);
        foreach($top_nav as $k=>$ico): 
        ?>
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
    <?php if(!$isReadOnly): ?>
    <select name="mg" class="form-select" style="width:150px;background:#0d0d16;border:1px solid #2a2a3a;color:#c0c0d8;border-radius:8px;padding:7px 11px;font-size:.83rem;">
        <option value="0">All Groups</option>
        <?php foreach($groups as $gr): ?>
        <option value="<?= $gr['id'] ?>" <?= $mf_group==$gr['id']?'selected':'' ?>><?= htmlspecialchars($gr['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="mc" class="form-select" style="width:150px;background:#0d0d16;border:1px solid #2a2a3a;color:#c0c0d8;border-radius:8px;padding:7px 11px;font-size:.83rem;">
        <option value="">All Categories</option>
        <?php 
        $cats = $pdo->query("SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
        foreach($cats as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $mf_cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-gold">Filter</button>
    <a href="?s=members" style="padding:7px 14px;background:rgba(255,255,255,.06);border:1px solid #2a2a3a;border-radius:8px;color:#c0c0d8;font-size:.83rem;text-decoration:none;">Reset</a>
</form>
<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>#</th><th>Name</th><th>Email / Phone</th><th>Last Active</th><th>Group</th><th>Plan</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
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
    <td><strong style="color:#e8e8f5;"><?= htmlspecialchars($m['name']) ?></strong></td>
    <td><span style="font-size:.7rem;color:#888aa8;"><?= htmlspecialchars($m['category']??'--') ?></span></td>
    <td>
        <div style="font-size:.8rem;"><?= htmlspecialchars($m['email']) ?></div>
        <div style="font-size:.7rem;color:#666;"><?= htmlspecialchars($m['phone']??'--') ?></div>
    </td>
    <td>
        <?php if($m['last_login']): ?>
        <div style="font-size:.78rem;font-weight:600;color:#e8e8f5;"><?= date('d M, h:i A', strtotime($m['last_login'])) ?></div>
        <div style="font-size:.65rem;color:#666;"><?php 
            $diff = time() - strtotime($m['last_login']);
            if($diff < 60) echo "Just now";
            elseif($diff < 3600) echo floor($diff/60)."m ago";
            elseif($diff < 86400) echo floor($diff/3600)."h ago";
            else echo floor($diff/86400)."d ago";
        ?></div>
        <?php else: ?>
        <span style="font-size:.7rem;color:#555;">Never</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if($m['group_name']): ?>
        <div style="font-size:.78rem;font-weight:700;color:#e8e8f5;"><?= htmlspecialchars($m['group_name']) ?></div>
        <div style="font-size:.62rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;"><?= ucfirst(str_replace('_',' ',$m['group_role']??'member')) ?></div>
        <?php else: ?>--<?php endif; ?>
    </td>
    <td><span class="pill" style="background:<?= $m_plan_col ?>22;color:<?= $m_plan_col ?>;border-color:<?= $m_plan_col ?>44;"><?= strtoupper($m_plan) ?></span></td>
    <td>
        <?php 
        $sys_role = $m['role'];
        $grp_role = $m['group_role'] ?? 'member';
        if($sys_role === 'admin'): ?><span class="pill pill-gold">Admin</span>
        <?php elseif($sys_role === 'moderator'): ?><span class="pill pill-blue">Moderator</span>
        <?php elseif($grp_role === 'president'): ?><span class="pill" style="background:rgba(255,215,0,0.1);color:#FFD700;border:1px solid #FFD700;"><i class="fas fa-crown me-1"></i> Pres.</span>
        <?php else: ?><span class="pill" style="background:#2a2a3a;color:#888aa8;">Member</span>
        <?php endif; ?>
    </td>
    <td><span class="pill pill-<?= $m['status']==='active'?'green':'red' ?>"><?= ucfirst($m['status']) ?></span></td>
    <td>
        <?php if(!$isReadOnly): ?>
        <button onclick="openEdit(<?= htmlspecialchars(json_encode($m)) ?>)" style="background:rgba(68,136,255,.12);color:#4488ff;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.7rem;cursor:pointer;">Edit</button>
        <button onclick="openPlanModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['name']) ?>', '<?= $m_plan ?>')" style="background:rgba(255,215,0,.12);color:#FFD700;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.7rem;cursor:pointer;">Plan</button>
        <?php else: ?>
        <span style="font-size:.7rem;color:#555;">View Only</span>
        <?php endif; ?>
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

<?php if ($active_section === 'ads'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[P] Campaign Ad Analytics</div><div class="sa-subtitle">Real-time performance of Instagram & Meta campaigns</div></div>
    <a href="https://adsmanager.facebook.com/" target="_blank" class="btn-gold">Open Meta Ads Mgr</a>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:25px;">
    <div class="stat-card" style="border-top: 3px solid #FFD700;">
        <div class="s-num" style="color:#FFD700;"><?= count($ad_stats) ?></div>
        <div class="s-lbl">Active Ad Sources</div>
    </div>
    <div class="stat-card" style="border-top: 3px solid #00e87a;">
        <div class="s-num" style="color:#00e87a;"><?php 
            $tot_ad = 0; foreach($ad_stats as $as) $tot_ad += $as['count'];
            echo number_format($tot_ad);
        ?></div>
        <div class="s-lbl">Total Ad Conversions</div>
    </div>
    <div class="stat-card" style="border-top: 3px solid #4488ff;">
        <div class="s-num" style="color:#4488ff;"><?= $tot_ad > 0 ? round(($tot_ad / $stats['total']) * 100, 1) : 0 ?>%</div>
        <div class="s-lbl">Growth Contribution</div>
    </div>
</div>

<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>Campaign Source / Link Parameter</th><th>Total Sign-ups</th><th>Market Influence</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($ad_stats as $ads): ?>
<tr>
    <td>
        <strong style="color:#e8e8f5; letter-spacing:1px;"><?= htmlspecialchars($ads['source']) ?></strong><br>
        <span style="font-size:.65rem; color:#666;">/register_business.php?src=<?= htmlspecialchars($ads['source']) ?></span>
    </td>
    <td style="font-size: 1.1rem; font-weight: 800; color: #FFD700;"><?= number_format($ads['count']) ?></td>
    <td style="width: 250px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="flex:1; background:#1a1a25; height:8px; border-radius:4px; overflow:hidden; border:1px solid #2a2a3a;">
                <div style="width: <?= ($tot_ad > 0 ? ($ads['count'] / $tot_ad) * 100 : 0) ?>%; background:#FFD700; height:100%;"></div>
            </div>
            <span style="font-size:.7rem; color:#888;"><?= ($tot_ad > 0 ? round(($ads['count'] / $tot_ad) * 100, 1) : 0) ?>%</span>
        </div>
    </td>
    <td>
        <a href="?s=members&mq=<?= urlencode($ads['source']) ?>" class="btn-gold" style="padding:4px 10px; font-size:.7rem; text-decoration:none;">View Members</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if(empty($ad_stats)): ?>
<tr><td colspan="4" style="text-align:center; color:#555; padding:40px;">No ad conversions detected yet. Run your first Instagram ad!</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if ($active_section === 'scout'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[🔍] AI Lead Scout</div><div class="sa-subtitle">Discover new business leads across major cities</div></div>
</div>

<div class="sa-card" style="padding:22px; margin-bottom:25px; background:#13131a; border:1px solid #2a2a3a; border-radius:12px;">
    <div style="display:flex; gap:15px; align-items:flex-end;">
        <div style="flex:1;">
            <label style="display:block; font-size:.7rem; color:#888; margin-bottom:5px; text-transform:uppercase; letter-spacing:1px;">Keyword / Business Sector</label>
            <input type="text" id="scout_keyword" placeholder="e.g. Photographers, BNI, Web Design" style="width:100%; background:#0a0a0f; border:1px solid #2a2a3a; color:#fff; padding:10px; border-radius:8px;">
        </div>
        <div style="flex:1;">
            <label style="display:block; font-size:.7rem; color:#888; margin-bottom:5px; text-transform:uppercase; letter-spacing:1px;">Location (City)</label>
            <input type="text" id="scout_city" value="Hyderabad" style="width:100%; background:#0a0a0f; border:1px solid #2a2a3a; color:#fff; padding:10px; border-radius:8px;">
        </div>
        <button onclick="runScoutSearch(event)" class="btn-gold" style="padding:10px 25px; height:42px;">Search Leads</button>
    </div>
</div>

<div id="scout_results_wrap" class="sa-table-wrap" style="display:none; margin-top:20px;">
    <table class="sa-table">
        <thead><tr><th>Business Name</th><th>Category</th><th>City</th><th>Intelligence Source</th><th>Action</th></tr></thead>
        <tbody id="scout_results_body"></tbody>
    </table>
</div>

<script>
function runScoutSearch(e) {
    const kw = document.getElementById('scout_keyword').value;
    const ct = document.getElementById('scout_city').value;
    if(!kw) return alert("Please enter a keyword");
    
    const btn = e.target;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Scanning..."; btn.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'search');
    fd.append('keyword', kw);
    fd.append('city', ct);
    
    fetch('/agent/leads_scout_agent.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = oldHtml; btn.disabled = false;
            if(res.status === 'success') {
                document.getElementById('scout_results_wrap').style.display='block';
                let h = '';
                res.data.forEach(d => {
                    h += `<tr>
                        <td><strong style="color:#FFD700">${d.name}</strong></td>
                        <td><span class="badge" style="background:rgba(255,215,0,.1); color:#FFD700; border:1px solid rgba(255,215,0,.2);">${d.category}</span></td>
                        <td>${d.city}</td>
                        <td style="font-size:.65rem; color:#666">${d.contact || 'Market Discovery'}</td>
                        <td><button onclick="saveProspect(this, '${btoa(JSON.stringify(d))}')" class="btn-gold" style="padding:4px 10px; font-size:.7rem;">Save to Leads</button></td>
                    </tr>`;
                });
                document.getElementById('scout_results_body').innerHTML = h;
            }
        }).catch(err => {
            alert("Error connecting to Scout Agent");
            btn.innerHTML = oldHtml; btn.disabled = false;
        });
}

function saveProspect(btn, dataB64) {
    const d = JSON.parse(atob(dataB64));
    btn.innerHTML = "Importing..."; btn.disabled = true;
    
    const fd = new FormData();
    fd.append('action', 'save_prospect');
    fd.append('name', d.name);
    fd.append('category', d.category);
    fd.append('city', d.city);
    fd.append('contact', d.contact || '');
    
    fetch('/agent/leads_scout_agent.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') {
                btn.innerHTML = "Saved ✔";
                btn.style.background = "#00ff88"; btn.style.color = "#000";
                btn.style.borderColor = "#00ff88";
            }
        });
}
</script>
<?php endif; ?>

<?php if ($active_section === 'leads'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[L] Leads Command Center</div><div class="sa-subtitle">Managing AI Insights & Member-to-Member Referrals</div></div>
</div>

<!-- Leads Summary Stats -->
<div class="stat-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 25px;">
    <div class="stat-card" style="border-top: 3px solid #4488ff;"><div class="s-num" style="color:#4488ff;"><?= $sum_open ?></div><div class="s-lbl">Open / Sent</div></div>
    <div class="stat-card" style="border-top: 3px solid #00e87a;"><div class="s-num" style="color:#00e87a;"><?= $sum_claimed ?></div><div class="s-lbl">In Progress</div></div>
    <div class="stat-card" style="border-top: 3px solid #ff4d6d;"><div class="s-num" style="color:#ff4d6d;"><?= $sum_closed ?></div><div class="s-lbl">Closed / Won</div></div>
    <div class="stat-card" style="border-top: 3px solid #FFD700;"><div class="s-num" style="color:#FFD700;">₹<?= number_format($total_value) ?></div><div class="s-lbl">Total Value (in view)</div></div>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;background:rgba(255,255,255,.03);padding:15px;border-radius:10px;border:1px solid #2a2a3a;">
    <input type="hidden" name="s" value="leads">
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.65rem;color:#666;display:block;margin-bottom:3px;">Search Lead</label>
        <input type="text" name="lq" value="<?= htmlspecialchars($lf_search) ?>" placeholder="Name, phone, email..." style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;width:100%;">
    </div>
    <div style="width:120px;">
        <label style="font-size:.65rem;color:#666;display:block;margin-bottom:3px;">Source</label>
        <select name="lsrc" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;width:100%;">
            <option value="">All Sources</option>
            <option value="ai" <?= $lf_source==='ai'?'selected':'' ?>>AI Engine</option>
            <option value="referral" <?= $lf_source==='referral'?'selected':'' ?>>Referral</option>
        </select>
    </div>
    <div style="width:150px;">
        <label style="font-size:.65rem;color:#666;display:block;margin-bottom:3px;">Assignee</label>
        <select name="lmid" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;width:100%;">
            <option value="0">All Members</option>
            <?php foreach($all_users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $lf_assignee==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="width:140px;">
        <label style="font-size:.65rem;color:#666;display:block;margin-bottom:3px;">Category</label>
        <select name="lc" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;width:100%;">
            <option value="">All Categories</option>
            <?php foreach($lead_categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $lf_cat===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="width:130px;">
        <label style="font-size:.65rem;color:#666;display:block;margin-bottom:3px;">Status</label>
        <select name="ls" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px 12px;border-radius:8px;width:100%;">
            <option value="">All Status</option>
            <option value="open" <?= $lf_status==='open'?'selected':'' ?>>Open / Sent</option>
            <option value="claimed" <?= $lf_status==='claimed'?'selected':'' ?>>In Progress</option>
            <option value="closed" <?= $lf_status==='closed'?'selected':'' ?>>Closed / Won</option>
            <option value="lapsed" <?= $lf_status==='lapsed'?'selected':'' ?>>Lapsed / Lost</option>
        </select>
    </div>
    <div style="display:flex;align-items:flex-end;gap:8px;">
        <button type="submit" class="btn-gold" style="padding:8px 20px;">Apply</button>
        <a href="?s=leads" style="background:#2a2a3a;color:#888;padding:8px 15px;border-radius:8px;text-decoration:none;font-size:.8rem;">Reset</a>
    </div>
</form>

<div class="sa-table-wrap">
<table class="sa-table">
<thead><tr><th>Source</th><th>Lead Info</th><th>Details</th><th>Assigned To & Group</th><th>Value (₹)</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($all_leads as $l): ?>
<tr>
    <td>
        <span class="badge" style="background:<?= $l['type']==='Referral'?'rgba(68,136,255,.1)':'rgba(255,140,0,.1)' ?>;color:<?= $l['type']==='Referral'?'#4488ff':'#ff8c00' ?>;font-size:.65rem;border:1px solid currentColor;border-radius:4px;padding:2px 6px;"><?= $l['type'] ?></span><br>
        <div style="font-size:.65rem;color:#666;margin-top:5px;"><?= htmlspecialchars($l['given_by_group']) ?></div>
    </td>
    <td>
        <strong style="color:#e8e8f5;"><?= htmlspecialchars($l['name']) ?></strong><br>
        <span style="font-size:.7rem;color:#8888aa;"><?= htmlspecialchars($l['phone']) ?></span>
    </td>
    <td>
        <div style="font-size:.75rem;color:#FFD700;font-weight:600;"><?= htmlspecialchars($l['category']) ?></div>
        <div style="font-size:.68rem;color:#888;max-width:200px;"><?= htmlspecialchars($l['query']) ?></div>
        <?php if($l['ai_strategy']): ?>
            <div style="margin-top:8px;padding:6px;background:rgba(255,215,0,.05);border-left:2px solid #FFD700;font-size:.65rem;color:#FFD700;line-height:1.3;">
                <div style="font-weight:800;text-transform:uppercase;font-size:.55rem;margin-bottom:3px;opacity:.7;">AI Strategy</div>
                <?= nl2br(htmlspecialchars($l['ai_strategy'])) ?>
            </div>
        <?php endif; ?>
    </td>
    <td>
        <?php if($l['assigned_to']): 
            $mStmt = $pdo->prepare("SELECT u.name, u.lat, u.lng, g.name as gname FROM users u LEFT JOIN groups g ON u.group_id=g.id WHERE u.id = ?"); $mStmt->execute([$l['assigned_to']]); $m = $mStmt->fetch(PDO::FETCH_ASSOC); ?>
            <span style="color:#00e87a;font-weight:600;"><?= htmlspecialchars($m['name']??'Unknown') ?></span>
            <div style="font-size:.68rem;color:#68688a;"><?= htmlspecialchars($m['gname'] ?? '-- No Group --') ?></div>
            <?php 
                if($l['lat'] && $l['lng'] && $m['lat'] && $m['lng']) {
                    $dist = round(getDistance($l['lat'], $l['lng'], $m['lat'], $m['lng']), 1);
                    echo "<div style='font-size:.65rem;color:".($dist<=100?'#00e87a':'#ff4d6d').";font-weight:700;'>📍 $dist km away</div>";
                }
            ?>
            <div style="font-size:.6rem;color:#444;"><?= $l['assigned_at'] ? date('d M, H:i', strtotime($l['assigned_at'])) : '--' ?></div>
            <?php if($l['recirc_count'] > 0): ?><div style="font-size:.6rem;color:#ff4d6d;">🔄 Re: <?= $l['recirc_count'] ?></div><?php endif; ?>
        <?php else: ?>
            <span style="color:#ffaa00;font-weight:700;">🌐 Open Pool</span>
        <?php endif; ?>
    </td>
    <td style="font-weight:700;color:<?= $l['deal_value']>0?'#FFD700':'#444' ?>;">
        <?= $l['deal_value'] > 0 ? '₹' . number_format($l['deal_value']) : '--' ?>
    </td>
    <td>
        <?php 
        $disp_st = 'Open'; 
        foreach($status_map as $smk=>$smv) { if(in_array($l['status'],$smv)) $disp_st = ucfirst($smk); }
        $st_col = $disp_st==='Open'?'#4488ff':($disp_st==='Claimed'?'#00e87a':($disp_st==='Closed'?'#ff4d6d':'#888'));
        ?>
        <span class="pill" style="background:<?= $st_col ?>22;color:<?= $st_col ?>;border-color:<?= $st_col ?>44;"><?= $disp_st ?></span>
    </td>
    <td>
        <?php if(!$isReadOnly): ?>
        <div style="display:flex;gap:5px;">
            <button onclick="openAssignModal(<?= $l['id'] ?>, '<?= $l['type'] ?>', '<?= htmlspecialchars($l['name']) ?>')" class="btn-gold" style="padding:4px 8px;font-size:.65rem;">Assign</button>
            <button onclick="openStatusModal(<?= $l['id'] ?>, '<?= $l['type'] ?>', '<?= $l['status'] ?>')" style="background:rgba(255,255,255,.05);color:#fff;border:1px solid #2a2a3a;padding:4px 8px;border-radius:4px;font-size:.65rem;cursor:pointer;">Status</button>
        </div>
        <?php else: ?>
        <span style="font-size:.65rem;color:#555;">Locked</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Leads Pagination -->
<?php if($total_leads_count >= 100): ?>
<div style="text-align:center;padding:20px;color:#666;font-size:.8rem;">
    Displaying latest 100 leads. Refine search to see more results.
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
                <option value="">-- Choose Member --</option>
                <?php foreach($all_users as $au): ?>
                <option value="<?= $au['id'] ?>"><?= htmlspecialchars($au['name']) ?> | <?= htmlspecialchars($au['group_name'] ?? 'No Group') ?> (<?= htmlspecialchars($au['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="type" id="assignType">
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Assign Now</button><button type="button" onclick="closeAssignModal()" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
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
                <optgroup label="AI Engine States">
                    <option value="new">New</option>
                    <option value="claimed">Claimed</option>
                    <option value="closed">Closed / Finished</option>
                    <option value="lapsed">Lapsed</option>
                </optgroup>
                <optgroup label="Referral States">
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="meeting_done">Meeting Done</option>
                    <option value="deal_in_progress">Deal In Progress</option>
                    <option value="closed_won">Closed Won</option>
                    <option value="closed_lost">Closed Lost</option>
                </optgroup>
            </select>
            <input type="hidden" name="type" id="statusType">
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Update</button><button type="button" onclick="closeStatusModal()" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<?php if ($active_section === 'categories'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[C] Business Categories</div><div class="sa-subtitle">Manage business sectors across Telugu States</div></div>
    <button onclick="document.getElementById('catModal').style.display='flex'" class="btn-gold" style="padding:9px 18px;font-size:.85rem;font-weight:700;">+ New Category</button>
</div>

<div class="sa-table-wrap">
    <table class="sa-table">
    <thead><tr><th>ID</th><th>Category Name</th><th>Slug</th><th>Manage</th></tr></thead>
    <tbody>
    <?php 
    $all_cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($all_cats as $cat): ?>
    <tr>
        <td><?= $cat['id'] ?></td>
        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
        <td style="font-size:.7rem;color:#666;"><?= htmlspecialchars($cat['slug']) ?></td>
        <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete category?')">
                <input type="hidden" name="action" value="delete_cat">
                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                <button type="submit" style="background:transparent;border:none;color:#ff4d6d;font-size:.7rem;cursor:pointer;">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>

<div id="catModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:340px;">
    <h5 style="color:#FFD700;margin-bottom:15px;">Add New Category</h5>
    <form method="POST">
        <input type="hidden" name="action" value="create_cat">
        <div class="mb-4">
            <label style="color:#8883;font-size:.75rem;display:block;margin-bottom:8px;">Category Name</label>
            <input type="text" name="cname" class="form-control" placeholder="e.g. Agri-Tech" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:10px;border-radius:8px;" required>
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Create</button><button type="button" onclick="document.getElementById('catModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div style="background:rgba(0,232,122,.1); border:1px solid #00e87a; color:#00e87a; padding:12px; border-radius:8px; margin-bottom:20px; font-size:.85rem; display:flex; align-items:center; gap:10px;">
        <span style="font-size:1.2rem;">✨</span> <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<?php if ($active_section === 'agents'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[A] Agent Command Center</div><div class="sa-subtitle">Autonomous orchestration & worker status</div></div>
    <div style="display:flex;gap:10px;">
        <a href="agent/import_prospects.php" class="btn-gold" style="text-decoration:none;background:rgba(255,215,0,.1);border:1px solid #FFD700;padding:9px 18px;">📂 Import Leads</a>
        <button onclick="document.getElementById('taskModal').style.display='flex'" class="btn-gold">🔎 AI Lead Hunt / Task</button>
    </div>
</div>

<!-- QA System Health Audit -->
<div class="sa-card" style="border-left: 4px solid #FFD700; margin-bottom: 25px;">
    <?php 
    $qa = json_decode(@file_get_contents(__DIR__ . '/agent/qa_status.json'), true);
    $qa_time = $qa['timestamp'] ?? 'Never';
    $qa_pass = $qa['pass'] ?? false;
    $qa_score = $qa['score'] ?? 0;
    $qa_total = $qa['total'] ?? 0;
    ?>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h6 style="color:#FFD700; margin:0; font-size:.9rem;"><i class="fas fa-microscope me-2"></i> Daily System Health Audit</h6>
            <div style="font-size:.7rem; color:#888; margin-top:4px;">Last Audit: <strong style="color:#c0c0d8;"><?= $qa_time ?></strong></div>
        </div>
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="text-align:right;">
                <div style="font-size:1.2rem; font-weight:800; color:<?= $qa_pass ? '#00e87a' : '#ff4d6d' ?>;">
                    <?= $qa_pass ? 'PASS' : 'WARNING' ?> 
                    <span style="font-size:.8rem; color:#888; font-weight:400;">(<?= $qa_score ?>/<?= $qa_total ?>)</span>
                </div>
                <div style="font-size:.6rem; text-transform:uppercase; letter-spacing:1px; color:#666;">System Integrity Score</div>
            </div>
            <a href="agent/qa_agent.php" target="_blank" class="btn-gold" style="padding:6px 15px; font-size:.75rem; text-decoration:none;">Run Audit Now</a>
        </div>
    </div>
</div>

<?php
// ── Agent Registry: all built agents with live DB stats ──────────────────────
$AGENT_REGISTRY = [
    // task_type               label                           icon          color     file
    ['type'=>'profile_nudge',       'label'=>'Profile Nudge',         'icon'=>'fa-user-edit',   'color'=>'#FFD700', 'file'=>'profile_nudge_agent.php',       'desc'=>'Emails members to complete their profile'],
    ['type'=>'followup',            'label'=>'Follow-Up',             'icon'=>'fa-redo',         'color'=>'#4488ff', 'file'=>'followup_agent.php',             'desc'=>'Re-engages members inactive 7+ days'],
    ['type'=>'welcome_drip',        'label'=>'Welcome Drip',          'icon'=>'fa-envelope-open','color'=>'#00e87a', 'file'=>'welcome_drip_agent.php',         'desc'=>'3-step onboarding (Day 1 / 3 / 7)'],
    ['type'=>'outreach_marketing',  'label'=>'Outreach Mailer',       'icon'=>'fa-paper-plane',  'color'=>'#ff8c00', 'file'=>'outreach_marketing_agent.php',   'desc'=>'Sends intro emails to scraped prospects'],
    ['type'=>'prospect_discovery',  'label'=>'Prospect Scout',        'icon'=>'fa-search-location','color'=>'#a855f7','file'=>'prospect_discovery_agent.php','desc'=>'AI lead hunter — 10 SMBs/run across sectors'],
    ['type'=>'social_posting',      'label'=>'Instagram Agent',       'icon'=>'fa-camera',       'color'=>'#ec4899', 'file'=>'social_media_agent.php',         'desc'=>'Posts branded reels/photos every 4 hours'],
    ['type'=>'seo_dominance',       'label'=>'SEO Page Factory',      'icon'=>'fa-search',       'color'=>'#22d3ee', 'file'=>'seo_power_agent.php',            'desc'=>'Generates 50 local SEO pages per run'],
    ['type'=>'referral_nudge',      'label'=>'Referral Nudge',        'icon'=>'fa-share-alt',    'color'=>'#00e87a', 'file'=>'referral_nudge_agent.php',       'desc'=>'Reminds members to share referral link'],
    ['type'=>'review_collector',    'label'=>'Review Collector',      'icon'=>'fa-star',         'color'=>'#FFD700', 'file'=>'review_collector_agent.php',     'desc'=>'Asks 30-day members for testimonial'],
];
// Bulk-fetch stats for all agent types in one query
$statsRaw = $pdo->query("SELECT task_type,
    COUNT(*) as total,
    SUM(status='completed' OR status='done') as completed,
    SUM(status='running') as running,
    SUM(status='failed') as failed,
    MAX(updated_at) as last_run
    FROM agent_tasks GROUP BY task_type")->fetchAll(PDO::FETCH_ASSOC);
$statsMap = [];
foreach ($statsRaw as $r) $statsMap[$r['task_type']] = $r;
?>

<div class="row g-4 mb-4">
  <div class="col-md-12">
    <div class="sa-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h6 style="color:#FFD700;margin:0;"><i class="fas fa-robot me-2"></i> Agent Registry — 9 Active Bots</h6>
        <div style="display:flex;gap:8px;">
          <a href="agent/agent_scheduler.php?key=BizCron2024" target="_blank" class="btn-gold" style="padding:6px 14px;font-size:.75rem;text-decoration:none;">⚡ Fire All Tasks</a>
          <a href="agent/runner.php?key=BizCron2024&auto=1" target="_blank" class="btn-gold" style="padding:6px 14px;font-size:.75rem;text-decoration:none;background:rgba(0,232,122,.1);border-color:#00e87a;color:#00e87a;">▶ Run Queue</a>
        </div>
      </div>
      <div class="row g-3">
        <?php foreach ($AGENT_REGISTRY as $ag):
            $s = $statsMap[$ag['type']] ?? ['total'=>0,'completed'=>0,'running'=>0,'failed'=>0,'last_run'=>null];
            $isRunning = $s['running'] > 0;
            $lastRun = $s['last_run'] ? date('d M H:i', strtotime($s['last_run'])) : 'Never';
            $borderColor = $isRunning ? '#4488ff' : ($s['total'] > 0 ? $ag['color'] : '#2a2a3a');
        ?>
        <div class="col-md-4 col-lg-4">
          <div style="background:rgba(255,255,255,.03);padding:14px;border-radius:12px;border:1px solid <?= $borderColor ?>;position:relative;transition:.2s;">
            <?php if($isRunning): ?>
              <div style="position:absolute;top:8px;right:8px;"><span class="pill pill-blue" style="font-size:.5rem;animation:pulse 2s infinite;">RUNNING</span></div>
            <?php elseif($s['total'] > 0): ?>
              <div style="position:absolute;top:8px;right:8px;"><span style="font-size:.55rem;color:#00e87a;background:rgba(0,232,122,.1);padding:2px 6px;border-radius:4px;border:1px solid rgba(0,232,122,.2);">ACTIVE</span></div>
            <?php else: ?>
              <div style="position:absolute;top:8px;right:8px;"><span style="font-size:.55rem;color:#555;background:rgba(255,255,255,.03);padding:2px 6px;border-radius:4px;border:1px solid #2a2a3a;">IDLE</span></div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:<?= $ag['color'] ?>22;display:flex;align-items:center;justify-content:center;border:1px solid <?= $ag['color'] ?>44;">
                <i class="fas <?= $ag['icon'] ?>" style="color:<?= $ag['color'] ?>;font-size:.9rem;"></i>
              </div>
              <div>
                <div style="font-size:.8rem;font-weight:700;color:#fff;"><?= $ag['label'] ?></div>
                <div style="font-size:.6rem;color:#555;"><?= htmlspecialchars($ag['file']) ?></div>
              </div>
            </div>

            <div style="font-size:.68rem;color:#888;margin-bottom:10px;line-height:1.4;"><?= $ag['desc'] ?></div>

            <div style="display:flex;gap:8px;font-size:.6rem;margin-bottom:10px;">
              <span style="color:#00e87a;">✓ <?= (int)$s['completed'] ?> done</span>
              <span style="color:#ff4d6d;">✗ <?= (int)$s['failed'] ?> fail</span>
              <span style="color:#666;">🕐 <?= $lastRun ?></span>
            </div>

            <a href="agent/<?= $ag['file'] ?>?key=BizCron2024" target="_blank"
               style="display:block;text-align:center;background:<?= $ag['color'] ?>11;color:<?= $ag['color'] ?>;border:1px solid <?= $ag['color'] ?>33;border-radius:6px;padding:5px;font-size:.65rem;text-decoration:none;font-weight:700;">
              ▶ Run Now
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Future Agent Roadmap -->
<div class="row g-4 mb-4">
  <div class="col-md-12">
    <div class="sa-card" style="border-left:4px solid #a855f7;">
      <h6 style="color:#a855f7;margin-bottom:18px;"><i class="fas fa-road me-2"></i> Future Agent Roadmap — Coming Soon</h6>
      <div class="row g-3">
        <?php
        $FUTURE_AGENTS = [
            ['label'=>'WhatsApp Broadcast',    'icon'=>'fa-whatsapp',        'color'=>'#25d366','desc'=>'Auto-send WhatsApp blasts to leads using WA API or Twilio'],
            ['label'=>'Google My Business',    'icon'=>'fa-google',          'color'=>'#4285f4','desc'=>'Auto-post updates to Google My Business listing weekly'],
            ['label'=>'Lead Confirmation',     'icon'=>'fa-check-circle',    'color'=>'#00e87a','desc'=>'Auto "Thank You" email to customer when lead is captured'],
            ['label'=>'AI Blog Writer',        'icon'=>'fa-pen-fancy',       'color'=>'#FFD700','desc'=>'Generates 1 SEO blog article per week (e.g. "Top Jewellers in Hyderabad")'],
            ['label'=>'Trust Score Booster',   'icon'=>'fa-shield-alt',      'color'=>'#22d3ee','desc'=>'Nudges members to verify KYC/GST to improve their trust score'],
            ['label'=>'LinkedIn Poster',       'icon'=>'fa-linkedin',        'color'=>'#0a66c2','desc'=>'Automated posts to BizNexus LinkedIn company page (token ready)'],
            ['label'=>'Competitor Monitor',    'icon'=>'fa-binoculars',      'color'=>'#f59e0b','desc'=>'Tracks JustDial/IndiaMART listings for new business categories'],
            ['label'=>'SMS OTP Nudge',         'icon'=>'fa-sms',             'color'=>'#ff4d6d','desc'=>'Sends OTP verification SMS to unverified new members'],
        ];
        foreach($FUTURE_AGENTS as $fa): ?>
        <div class="col-md-3">
          <div style="background:rgba(255,255,255,.02);padding:12px;border-radius:10px;border:1px dashed #2a2a3a;opacity:.75;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
              <div style="width:30px;height:30px;border-radius:6px;background:<?= $fa['color'] ?>11;display:flex;align-items:center;justify-content:center;">
                <i class="fab <?= $fa['icon'] ?> fa-sm" style="color:<?= $fa['color'] ?>;"></i>
              </div>
              <div style="font-size:.75rem;font-weight:700;color:#888;"><?= $fa['label'] ?></div>
            </div>
            <div style="font-size:.62rem;color:#444;line-height:1.4;"><?= $fa['desc'] ?></div>
            <div style="margin-top:8px;font-size:.55rem;color:#333;text-transform:uppercase;letter-spacing:1px;">🔜 Planned</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="sa-table-wrap">
    <table class="sa-table">
    <thead><tr><th>ID</th><th>Focus</th><th>Goal</th><th>Status</th><th>Updated</th><th>Action</th></tr></thead>
    <tbody>
    <?php 
    $tasks = $pdo->query("SELECT * FROM agent_tasks ORDER BY updated_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach($tasks as $t): 
        $st_col = ['pending'=>'#888','running'=>'#4488ff','done'=>'#00e87a','failed'=>'#ff4d6d','cancelled'=>'#555','completed'=>'#00e87a'][$t['status']] ?? '#888';
    ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><span class="pill pill-blue" style="font-size:.6rem;"><?= strtoupper($t['task_type']) ?></span></td>
                <td style="font-size:.78rem;max-width:300px;"><?= htmlspecialchars($t['goal']) ?></td>
                <td><span class="pill" style="background:<?= $st_col ?>22;color:<?= $st_col ?>;border-color:<?= $st_col ?>44;"><?= ucfirst($t['status']) ?></span></td>
                <td style="font-size:.7rem;color:#666;"><?= date('H:i:s', strtotime($t['updated_at'])) ?></td>
                <td>
                    <?php if(in_array($t['status'], ['pending','running'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="cancel_task">
                        <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn-gold" style="padding:2px 6px;font-size:.6rem;background:rgba(255,215,0,.1);color:#FFD700;">Stop</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($tasks)): ?><tr><td colspan="6" style="text-align:center;padding:20px;color:#444;">No active agents found.</td></tr><?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-card" style="padding:15px;">
            <h6 style="color:#FFD700;font-size:.8rem;margin-bottom:12px;display:flex;justify-content:space-between;">
                <span>🧠 Agent Activity Feed</span>
                <a href="?s=agents" style="font-size:.65rem;color:#555;text-decoration:none;">Refresh</a>
            </h6>
            <div style="font-family:'Inter',monospace;font-size:.7rem;max-height:400px;overflow-y:auto;background:rgba(0,0,0,.2);padding:10px;border-radius:8px;">
                <?php 
                $logs = $pdo->query("SELECT * FROM agent_logs ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
                foreach($logs as $l):
                    $lc = $l['is_error'] ? '#ff4d6d' : '#8888aa';
                ?>
                <div style="margin-bottom:8px;line-height:1.4;">
                    <span style="color:#444;"><?= date('H:i:s', strtotime($l['created_at'])) ?></span>
                    <span style="color:#FFD700;font-weight:700;">[<?= htmlspecialchars($l['agent_name']) ?>]</span>
                    <span style="color:<?= $lc ?>;"><?= htmlspecialchars($l['detail']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if(empty($logs)): ?><div style="color:#333;">Waiting for agent logs...</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>
<?php endif; ?>

<?php if ($active_section === 'referrals'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[🔗] Referral Growth & Attribution</div><div class="sa-subtitle">Tracking the platform's viral growth coefficient</div></div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="sa-card">
            <h6 style="color:#FFD700;margin-bottom:15px;"><i class="fas fa-trophy me-2"></i> Top Ambassadors</h6>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead><tr><th>Ambassador</th><th>Joins</th></tr></thead>
                    <tbody>
                        <?php 
                        $leaders = $pdo->query("SELECT r.name, COUNT(u.id) as total FROM users u JOIN users r ON u.referred_by = r.id GROUP BY r.id ORDER BY total DESC LIMIT 10")->fetchAll();
                        foreach($leaders as $ld): ?>
                        <tr><td><strong><?= htmlspecialchars($ld['name']) ?></strong></td><td><span class="pill pill-gold"><?= $ld['total'] ?></span></td></tr>
                        <?php endforeach; ?>
                        <?php if(empty($leaders)): ?><tr><td colspan="2" class="text-center py-4 opacity-50">No referrals yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="sa-card">
            <h6 style="color:#4488ff;margin-bottom:15px;"><i class="fas fa-history me-2"></i> Recent Referrals Log</h6>
            <div class="sa-table-wrap">
                <table class="sa-table text-nowrap">
                    <thead><tr><th>New Partner</th><th>Referred By</th><th>Date</th><th>Award</th></tr></thead>
                    <tbody>
                        <?php 
                        $relogs = $pdo->query("SELECT u.name as new_user, u.created_at, r.name as referrer FROM users u JOIN users r ON u.referred_by = r.id ORDER BY u.created_at DESC LIMIT 30")->fetchAll();
                        foreach($relogs as $rl): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rl['new_user']) ?></strong></td>
                            <td><span style="color:#888;">via</span> <?= htmlspecialchars($rl['referrer']) ?></td>
                            <td style="font-size:.7rem;"><?= date('d M, H:i', strtotime($rl['created_at'])) ?></td>
                            <td><span class="badge bg-dark" style="color:#00e87a; font-size:.65rem;">+200 Coins</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($relogs)): ?>
                            <tr><td colspan="4" class="text-center py-5 opacity-50">No referrals tracked yet. Launch the program!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($active_section === 'migration'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[M] Network Migration Hub</div><div class="sa-subtitle">Targeting BNI, H2H, and Traditional Networks</div></div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="sa-card">
            <h6 style="color:#FFD700;margin-bottom:15px;"><i class="fas fa-file-import me-2"></i> Import Referral Chapter</h6>
            <p style="font-size:.8rem;color:#888;margin-bottom:20px;">Use this tool to migrate contact lists from traditional networks. Members will receive an automatic <strong>+200 BSR (Trust Score)</strong> boost upon registration.</p>
            
            <form action="agent/process_migration.php" method="POST" enctype="multipart/form-data" style="background:rgba(255,255,255,.03);padding:20px;border:1px solid #2a2a3a;border-radius:10px;">
                <div class="mb-4">
                    <label style="color:#c0c0d8;font-size:.75rem;display:block;margin-bottom:10px;">Select Network Type:</label>
                    <select name="network_type" class="form-select" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:8px;border-radius:6px;width:100%;">
                        <option value="BNI">BNI (Business Network International)</option>
                        <option value="H2H">H2H (Heart to Heart)</option>
                        <option value="JCI">JCI / Rotary / Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label style="color:#c0c0d8;font-size:.75rem;display:block;margin-bottom:10px;">Upload Membership CSV:</label>
                    <input type="file" name="csv_file" class="form-control" style="background:transparent;border:1px dashed #444;color:#888;padding:20px;text-align:center;" required>
                </div>
                <button type="submit" class="btn-gold w-100">🚀 Initiate AI Upgrade (Import)</button>
            </form>
            
            <div style="margin-top:20px;text-align:center;">
                <a href="agent/migration_template.csv" style="color:#4488ff;font-size:.75rem;text-decoration:none;"><i class="fas fa-download me-1"></i> Download Specialized BNI/H2H Template</a>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="sa-card">
            <h6 style="color:#4488ff;margin-bottom:15px;"><i class="fas fa-shield-alt me-2"></i> Why AI Migration?</h6>
            <ul style="font-size:.8rem;color:#c0c0d8;padding-left:15px;">
                <li style="margin-bottom:10px;"><strong>Automated Prospecting</strong>: Traditional networks require manual effort. BizNexus AI never sleeps.</li>
                <li style="margin-bottom:10px;"><strong>Universal SEO</strong>: BNI members are only visible to their chapter. BizNexus members are visible to the world.</li>
                <li style="margin-bottom:10px;"><strong>Digital Referral Slips</strong>: Tracking cross-network referrals is instant and validated.</li>
            </ul>
        </div>
        <div class="sa-card" style="background:linear-gradient(135deg, rgba(255,215,0,.05) 0%, rgba(0,0,0,0) 100%); border-color:rgba(255,215,0,.2);">
            <h6 style="color:#FFD700;margin-bottom:10px;">The BSR Boost</h6>
            <p style="font-size:.75rem;color:#888;">Migrating members are granted "Vetted" status immediately. This increases their lead visibility by <strong>40%</strong> during their first 30 days.</p>
        </div>
    </div>
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

</div>
</div>

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
function closeAssignModal(){ document.getElementById('assignModal').style.display = 'none'; }
function openAssignModal(lid, type, name) {
    document.getElementById('assignLid').value = lid;
    document.getElementById('assignType').value = type;
    document.getElementById('assignLeadName').textContent = 'Assign: ' + name + ' (' + type + ')';
    document.getElementById('assignModal').style.display = 'flex';
}
function closeStatusModal(){ document.getElementById('leadStatusModal').style.display = 'none'; }
</script>
<?php if ($active_section === 'meet'): ?>
<div class="sa-topbar">
    <div><div class="sa-title">[📅] Meeting Registrants</div><div class="sa-subtitle">Registrations from Promotional Ads</div></div>
</div>
<?php
// Ensure table exists (just in case)
$pdo->exec("CREATE TABLE IF NOT EXISTS meeting_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, name VARCHAR(100), email VARCHAR(100), phone VARCHAR(20), category VARCHAR(100), created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$meet_users = $pdo->query("SELECT * FROM meeting_registrations ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="sa-table-wrap" style="margin-bottom:30px;">
<table class="sa-table">
<thead><tr><th>ID</th><th>Name & Business</th><th>Email / Phone</th><th>Category</th><th>Registered At</th></tr></thead>
<tbody>
<?php foreach($meet_users as $mu): ?>
<tr>
    <td style="color:#666699;"><?= $mu['id'] ?></td>
    <td>
        <strong style="color:#e8e8f5;"><?= htmlspecialchars($mu['name']) ?></strong>
        <?php if(!empty($mu['business_name'])): ?>
            <div style="font-size:.7rem;color:#FFD700;margin-top:2px;"><?= htmlspecialchars($mu['business_name']) ?></div>
        <?php endif; ?>
    </td>
    <td>
        <div style="font-size:.8rem;"><?= htmlspecialchars($mu['email']) ?></div>
        <div style="font-size:.7rem;color:#666;"><?= htmlspecialchars($mu['phone']) ?></div>
    </td>
    <td><span class="pill pill-blue"><?= htmlspecialchars($mu['category']) ?></span></td>
    <td><div style="font-size:.78rem;font-weight:600;color:#e8e8f5;"><?= date('d M Y, h:i A', strtotime($mu['created_at'])) ?></div></td>
</tr>
<?php endforeach; ?>
<?php if(empty($meet_users)): ?>
<tr><td colspan="5" style="text-align:center;padding:30px;color:#666;">No registrations yet. Link to start: <a href="/meet.php" target="_blank" style="color:#FFD700;">/meet.php</a></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php 
// Modals placed at the end for reliable UI loading
?>
<!-- Edit User Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:380px;">
    <h5 style="color:#FFD700;margin-bottom:18px;">Edit User Profile</h5>
    <form method="POST">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" id="edit_uid">
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Full Name</label><input type="text" name="uname" id="edit_name" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Email</label><input type="email" name="uemail" id="edit_email" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Phone</label><input type="text" name="uphone" id="edit_phone" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Category</label><input type="text" name="ucat" id="edit_cat" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;" placeholder="e.g. Photography"></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Status</label><select name="ustatus" id="edit_status" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <div class="mb-3"><label style="color:#8883;font-size:.75rem;">Assigned Group</label>
            <select name="ugroup" id="edit_group" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;">
                <option value="0">None / Individual</option>
                <?php foreach($groups as $gr): ?>
                <option value="<?= $gr['id'] ?>"><?= htmlspecialchars($gr['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4"><label style="color:#8883;font-size:.75rem;">Group Role</label>
            <select name="ugrole" id="edit_grole" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;">
                <option value="member">Member</option>
                <option value="president">President 👑</option>
                <option value="vice_president">Vice President</option>
                <option value="gen_secretary">Gen. Secretary</option>
                <option value="joint_secretary">Joint Secretary</option>
                <option value="treasurer">Treasurer</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Save Changes</button><button type="button" onclick="closeEdit()" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
    <hr style="border-top:1px solid #2a2a3a;margin:20px 0;">
    <h6 style="color:#FFD700;font-size:.8rem;margin-bottom:12px;">Reset Password</h6>
    <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="user_id" id="pw_uid">
        <div class="mb-3"><input type="password" name="new_password" placeholder="New Password (min 6 chars)" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;"></div>
        <button type="submit" class="btn-gold w-100" style="padding:6px;font-size:.75rem;">Update Password</button>
    </form>
</div>
</div>

<!-- Task Modal -->
<div id="taskModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:12px;padding:24px;width:340px;">
    <h5 style="color:#FFD700;margin-bottom:15px;">🔎 AI Lead Hunt / Goal</h5>
    <form method="POST">
        <input type="hidden" name="action" value="delegate_task">
        <div class="mb-3">
            <label style="color:#888;font-size:.7rem;display:block;margin-bottom:8px;">Quick Presets:</label>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:15px;">
                <button type="button" onclick="setGoal('Find 20 Wedding Photographers in Hyderabad')" style="background:#2a2a3a;color:#fff;border:none;padding:5px 8px;font-size:.7rem;border-radius:4px;cursor:pointer;">📸 Photographers</button>
                <button type="button" onclick="setGoal('Search for 30 Construction SMEs in Medak')" style="background:#2a2a3a;color:#fff;border:none;padding:5px 8px;font-size:.7rem;border-radius:4px;cursor:pointer;">🏗️ Contractors</button>
                <button type="button" onclick="setGoal('Find 20 Event Planners in Warangal')" style="background:#2a2a3a;color:#fff;border:none;padding:5px 8px;font-size:.7rem;border-radius:4px;cursor:pointer;">🎈 Event Planners</button>
            </div>
            <label style="color:#8883;font-size:.75rem;display:block;margin-bottom:8px;">Custom Goal Description</label>
            <textarea name="goal" id="taskGoal" class="form-control" placeholder="e.g. Find 20 new business leads in..." style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:10px;border-radius:8px;height:80px;" required></textarea>
        </div>
        <script>function setGoal(g){ document.getElementById('taskGoal').value = g; }</script>
        <div style="display:flex;gap:8px;"><button type="submit" class="btn-gold" style="flex:1;">Spawn Agent</button><button type="button" onclick="document.getElementById('taskModal').style.display='none'" style="flex:1;background:#2a2a3a;color:#c0c0d8;border:none;border-radius:8px;">Cancel</button></div>
    </form>
</div>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
