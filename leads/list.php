<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$uid = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
$msg = '';

// ── Plan claim limits ──────────────────────────────────────────────────────
$claim_limits = ['free'=>3, 'silver'=>40, 'gold'=>80, 'platinum'=>PHP_INT_MAX];
$user_plan    = $pdo->query("SELECT plan FROM users WHERE id=$uid")->fetchColumn() ?: 'free';
$claim_limit  = $claim_limits[$user_plan] ?? 3;
$claims_this_month = (int)$pdo->query("SELECT COUNT(*) FROM public_leads WHERE claimed_by_member_id=$uid AND MONTH(claimed_at)=MONTH(NOW()) AND YEAR(claimed_at)=YEAR(NOW())")->fetchColumn();

// ── Claim action ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_lead_id'])) {
    $lid = (int)$_POST['claim_lead_id'];
    if ($claims_this_month >= $claim_limit) {
        $upgrade_url = '/membership/upgrade.php?plan=' . ($user_plan==='free'?'silver':($user_plan==='silver'?'gold':'platinum'));
        $msg = "error:You've used {$claims_this_month}/{$claim_limit} lead claims this month on the " . ucfirst($user_plan) . " plan. <a href='{$upgrade_url}' style='color:#FFD700;font-weight:700;'>Upgrade to claim more →</a>";
    } else {
        try {
            $chk = $pdo->prepare("SELECT status FROM public_leads WHERE id=?");
            $chk->execute([$lid]);
            $lead = $chk->fetch();
            if ($lead && in_array($lead['status'], ['new','open'])) {
                $pdo->prepare("UPDATE public_leads SET status='claimed', claimed_by=?, claimed_by_member_id=?, claimed_at=NOW() WHERE id=? AND status IN ('new','open')")
                    ->execute([$uid, $uid, $lid]);
                try { sendNotification($pdo, $uid, "Lead Claimed", "You successfully claimed a new lead.", 'crm'); } catch(Exception $e){}
                $claims_this_month++;
                $msg = 'success:Lead claimed! You have used ' . $claims_this_month . '/' . ($claim_limit === PHP_INT_MAX ? '∞' : $claim_limit) . ' claims this month.';
            } else {
                $msg = 'error:This lead has already been claimed.';
            }
        } catch(Exception $e) {
            $msg = 'error:' . $e->getMessage();
        }
    }
}

// ── Filters ────────────────────────────────────────────────────────────────
$f_city   = trim($_GET['city'] ?? '');
$f_cat    = trim($_GET['category'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_q      = trim($_GET['q'] ?? '');

$where  = ["1=1"]; $params = [];
if ($f_city)   { $where[] = "city LIKE ?";       $params[] = "%$f_city%"; }
if ($f_cat)    { $where[] = "category LIKE ?";   $params[] = "%$f_cat%"; }
if ($f_status) { $where[] = "status = ?";         $params[] = $f_status; }
if ($f_q)      { $where[] = "(name LIKE ? OR query LIKE ? OR intent LIKE ? OR category LIKE ?)"; $params = array_merge($params, ["%$f_q%","%$f_q%","%$f_q%","%$f_q%"]); }

$wStr = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM public_leads WHERE $wStr ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// totals (unfiltered)
$total_all  = (int)$pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn();
$total_new  = (int)$pdo->query("SELECT COUNT(*) FROM public_leads WHERE status IN ('new','open')")->fetchColumn();
$my_claimed = (int)$pdo->query("SELECT COUNT(*) FROM public_leads WHERE claimed_by_member_id=$uid")->fetchColumn();
$total_claimed = (int)$pdo->query("SELECT COUNT(*) FROM public_leads WHERE status='claimed'")->fetchColumn();

// filter options
$cities = $pdo->query("SELECT DISTINCT city FROM public_leads WHERE city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$cats   = $pdo->query("SELECT DISTINCT category FROM public_leads WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

if (isset($_GET['booked'])) $msg = 'success:Meeting booked!';

$page_title  = 'CRM Pipeline — BizNexus';
$active_page = 'crm';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<style>
/* ── Self-contained vars ── */
:root {
    --bg:#0a0a0f; --card:#13131a; --card2:#0f0f18; --border:#1e1e2e;
    --gold:#FFD700; --green:#00e87a; --red:#ff4d6d; --blue:#4488ff; --orange:#ff8c00;
    --text:#d0d0e8; --muted:#666688;
}

/* ── Stat bar ── */
.crm-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
.crm-stat  { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px 20px; display:flex; align-items:center; gap:12px; min-width:140px; }
.crm-stat .n { font-size:1.8rem; font-weight:800; font-family:'Syne',sans-serif; line-height:1; }
.crm-stat .l { font-size:.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; margin-top:3px; }

/* ── Filter bar ── */
.crm-filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px; align-items:center; }
.crm-filters input, .crm-filters select {
    background:var(--card); border:1px solid var(--border); color:var(--text);
    border-radius:8px; padding:9px 13px; font-size:.83rem; font-family:'Inter',sans-serif;
    outline:none; transition:.2s;
}
.crm-filters input:focus, .crm-filters select:focus { border-color:var(--gold); }
.crm-filters input { min-width:200px; flex:1; }
.crm-filters select option { background:#13131a; }

/* ── Grid ── */
.leads-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px; }

/* ── Lead Card ── */
.lead-card {
    background:var(--card); border:1px solid var(--border);
    border-radius:14px; padding:20px;
    display:flex; flex-direction:column; gap:12px;
    transition:border-color .2s, transform .2s;
    position:relative; overflow:hidden;
}
.lead-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg, var(--gold), var(--orange));
    opacity:0; transition:.2s;
}
.lead-card:hover { border-color:rgba(255,215,0,.35); transform:translateY(-2px); }
.lead-card:hover::before { opacity:1; }
.lead-card.s-claimed { border-color:rgba(68,136,255,.25); }
.lead-card.s-claimed::before { background:linear-gradient(90deg,#4488ff,#a259ff); opacity:.7; }
.lead-card.s-expired { opacity:.6; }

/* ── Card header ── */
.lc-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
.lc-name { font-weight:700; font-size:.95rem; color:#e8e8f5; }
.lc-meta { font-size:.73rem; color:var(--muted); margin-top:3px; display:flex; gap:8px; flex-wrap:wrap; }
.lc-meta span { display:flex; align-items:center; gap:3px; }

/* ── Status pill ── */
.lc-status { padding:3px 10px; border-radius:20px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; flex-shrink:0; }
.st-new, .st-open       { background:rgba(0,232,122,.1); color:var(--green); border:1px solid rgba(0,232,122,.3); }
.st-claimed             { background:rgba(68,136,255,.1); color:var(--blue); border:1px solid rgba(68,136,255,.3); }
.st-expired, .st-closed { background:rgba(255,77,109,.1); color:var(--red); border:1px solid rgba(255,77,109,.3); }

/* ── Intent/query text ── */
.lc-body { font-size:.83rem; color:#b0b0cc; line-height:1.6; flex:1; }
.lc-intent { font-size:.8rem; color:var(--muted); line-height:1.55; font-style:italic; margin-top:4px; }

/* ── Tags ── */
.lc-tags { display:flex; gap:6px; flex-wrap:wrap; }
.lc-tag { padding:3px 9px; background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:20px; font-size:.68rem; color:var(--muted); }
.lc-tag.cat { background:rgba(255,215,0,.07); border-color:rgba(255,215,0,.2); color:rgba(255,215,0,.8); }
.lc-tag.city { background:rgba(68,136,255,.07); border-color:rgba(68,136,255,.2); color:rgba(68,136,255,.9); }

/* ── Footer ── */
.lc-footer { display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border); }
.lc-time { font-size:.72rem; color:var(--muted); }

/* ── Buttons ── */
.btn-claim {
    background:linear-gradient(135deg,var(--gold),var(--orange));
    color:#000; font-weight:700; border:none; border-radius:8px;
    padding:7px 16px; cursor:pointer; font-size:.79rem;
    transition:opacity .2s, transform .2s; white-space:nowrap;
}
.btn-claim:hover { opacity:.9; transform:translateY(-1px); }

/* ── Empty state ── */
.empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
.empty-state .icon { font-size:3.5rem; margin-bottom:14px; }
.empty-state h3 { color:#8888aa; font-size:1.1rem; font-weight:600; margin:0 0 6px; }

/* ── Alert ── */
.crm-alert { padding:12px 16px; border-radius:10px; font-size:.85rem; margin-bottom:18px; }
.crm-alert.ok  { background:rgba(0,232,122,.08); border:1px solid rgba(0,232,122,.3); color:var(--green); }
.crm-alert.err { background:rgba(255,77,109,.08); border:1px solid rgba(255,77,109,.3); color:var(--red); }
</style>

<!-- ── Page header ── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
    <div>
        <h2 style="font-family:'Syne',sans-serif;font-weight:800;margin:0;color:#e8e8f5;font-size:1.4rem;">💼 CRM Pipeline</h2>
        <p style="color:var(--muted,#8888aa);margin:5px 0 0;font-size:.83rem;">Incoming business leads — claim and connect</p>
    </div>
    <a href="/leads/post.php" style="padding:9px 20px;background:linear-gradient(135deg,#FFD700,#ff8c00);color:#000;font-weight:700;border-radius:9px;text-decoration:none;font-size:.84rem;">+ Post Lead</a>
</div>

<?php if ($msg): [$type, $text] = explode(':', $msg, 2); ?>
<div class="crm-alert <?= $type === 'success' ? 'ok' : 'err' ?>">
    <?= $type === 'success' ? '✅' : '❌' ?> <?= $text /* intentionally not escaped — contains safe upgrade link */ ?>
</div>
<?php endif; ?>

<!-- ── Stats ── -->
<div class="crm-stats">
    <div class="crm-stat">
        <div><div class="n" style="color:var(--gold);"><?= $total_all ?></div><div class="l">Total Leads</div></div>
    </div>
    <div class="crm-stat">
        <div><div class="n" style="color:var(--green);"><?= $total_new ?></div><div class="l">Open / New</div></div>
    </div>
    <div class="crm-stat">
        <div><div class="n" style="color:var(--blue);"><?= $total_claimed ?></div><div class="l">Claimed</div></div>
    </div>
    <div class="crm-stat">
        <div><div class="n" style="color:#a259ff;"><?= $my_claimed ?></div><div class="l">My Claims</div></div>
    </div>
    <?php
    $limit_display = $claim_limit === PHP_INT_MAX ? '∞' : $claim_limit;
    $usage_pct = $claim_limit === PHP_INT_MAX ? 0 : min(100, round(($claims_this_month / $claim_limit) * 100));
    $usage_color = $usage_pct >= 90 ? '#ff4d6d' : ($usage_pct >= 60 ? '#ffaa00' : '#00e87a');
    ?>
    <div class="crm-stat" style="border-color:<?= $usage_pct>=90?'rgba(255,77,109,.3)':'rgba(255,215,0,.1)' ?>;">
        <div style="min-width:110px;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;">
                <div class="n" style="color:<?= $usage_color ?>;"><?= $claims_this_month ?></div>
                <div style="font-size:.78rem;color:var(--muted);">/ <?= $limit_display ?></div>
            </div>
            <div class="l">This Month (<?= ucfirst($user_plan) ?>)</div>
            <div style="height:4px;background:#1a1a28;border-radius:2px;margin-top:6px;overflow:hidden;">
                <div style="width:<?= $usage_pct ?>%;height:100%;background:<?= $usage_color ?>;border-radius:2px;transition:.5s;"></div>
            </div>
        </div>
    </div>
    <?php if (count($leads) !== $total_all): ?>
    <div class="crm-stat">
        <div><div class="n" style="color:var(--orange);"><?= count($leads) ?></div><div class="l">Filtered</div></div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Filters ── -->
<form method="GET" class="crm-filters">
    <input type="text" name="q" value="<?= htmlspecialchars($f_q) ?>" placeholder="🔍 Search name, query, category...">
    <select name="city">
        <option value="">🏙️ All Cities</option>
        <?php foreach ($cities as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $f_city === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="category">
        <option value="">📂 All Categories</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $f_cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">📊 All Status</option>
        <option value="new"     <?= $f_status === 'new'     ? 'selected' : '' ?>>🟢 New (Open)</option>
        <option value="claimed" <?= $f_status === 'claimed' ? 'selected' : '' ?>>🔵 Claimed</option>
        <option value="expired" <?= $f_status === 'expired' ? 'selected' : '' ?>>🔴 Expired</option>
    </select>
    <button type="submit" style="padding:9px 20px;background:linear-gradient(135deg,#FFD700,#ff8c00);color:#000;font-weight:700;border:none;border-radius:8px;cursor:pointer;font-size:.84rem;">Filter</button>
    <?php if ($f_q || $f_city || $f_cat || $f_status): ?>
    <a href="/leads/list.php" style="padding:9px 14px;background:rgba(255,255,255,.05);border:1px solid #2a2a3a;border-radius:8px;color:#8888aa;font-size:.83rem;text-decoration:none;">✕ Clear</a>
    <?php endif; ?>
</form>

<!-- ── Grid ── -->
<?php if (empty($leads)): ?>
<div class="empty-state">
    <div class="icon">📭</div>
    <h3>No leads found</h3>
    <p style="font-size:.84rem;margin-top:4px;">Try adjusting your filters or <a href="/leads/post.php" style="color:var(--gold);">post a new lead</a></p>
</div>
<?php else: ?>
<div style="font-size:.78rem;color:var(--muted,#8888aa);margin-bottom:12px;">Showing <strong style="color:#c0c0d8;"><?= count($leads) ?></strong> lead<?= count($leads) !== 1 ? 's' : '' ?></div>
<div class="leads-grid">
<?php foreach ($leads as $l):
    $isMine   = (int)($l['claimed_by_member_id'] ?? 0) === $uid;
    $statusCls = 'st-' . strtolower($l['status']);
    $cardCls   = in_array($l['status'], ['claimed']) ? 's-claimed' : ($l['status'] === 'expired' ? 's-expired' : '');
    $queryText = trim($l['query'] ?? $l['intent'] ?? '');
    $intentText = ($l['intent'] ?? '') && ($l['query'] ?? '') ? trim($l['intent']) : '';
?>
<div class="lead-card <?= $cardCls ?>">

    <!-- Header -->
    <div class="lc-header">
        <div>
            <div class="lc-name"><?= htmlspecialchars($l['name'] ?: 'Anonymous') ?></div>
            <div class="lc-meta">
                <?php if ($l['city']): ?><span>📍 <?= htmlspecialchars($l['city']) ?></span><?php endif; ?>
                <?php if ($l['phone'] && ($isMine || $is_admin)): ?><span>📞 <?= htmlspecialchars($l['phone']) ?></span><?php endif; ?>
                <?php if ($l['email'] && ($isMine || $is_admin)): ?><span>✉️ <?= htmlspecialchars($l['email']) ?></span><?php endif; ?>
            </div>
        </div>
        <span class="lc-status <?= $statusCls ?>">
            <?= match(strtolower($l['status'])) {
                'new'     => '🟢 Open',
                'open'    => '🟢 Open',
                'claimed' => '🔵 Claimed',
                'expired' => '🔴 Expired',
                'closed'  => '🔴 Closed',
                default   => ucfirst($l['status'])
            } ?>
        </span>
    </div>

    <!-- Query / Intent -->
    <?php if ($queryText): ?>
    <div class="lc-body"><?= htmlspecialchars(substr($queryText, 0, 130)) ?><?= strlen($queryText) > 130 ? '…' : '' ?></div>
    <?php endif; ?>
    <?php if ($intentText && strlen($intentText) > 5): ?>
    <div class="lc-intent"><?= htmlspecialchars(substr($intentText, 0, 120)) ?><?= strlen($intentText) > 120 ? '…' : '' ?></div>
    <?php endif; ?>

    <!-- Tags -->
    <div class="lc-tags">
        <?php if ($l['category']): ?><span class="lc-tag cat">📂 <?= htmlspecialchars($l['category']) ?></span><?php endif; ?>
        <?php if ($l['city']): ?><span class="lc-tag city">📍 <?= htmlspecialchars($l['city']) ?></span><?php endif; ?>
        <?php if ($l['claimed_count'] ?? 0): ?><span class="lc-tag">👤 <?= (int)$l['claimed_count'] ?> claims</span><?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="lc-footer">
        <span class="lc-time">🕐 <?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></span>
        <?php if (in_array(strtolower($l['status']), ['new','open'])): ?>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="claim_lead_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn-claim">🤝 Claim Lead</button>
            </form>
        <?php elseif ($isMine): ?>
            <span style="font-size:.78rem;color:var(--blue);font-weight:700;">✓ You claimed this</span>
        <?php else: ?>
            <span style="font-size:.76rem;color:var(--muted);">Already claimed</span>
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
