<?php
// BIZNEXUS CRON MONITOR — view progress and trigger manually
session_start();
if (isset($_POST['pass'])) {
    if ($_POST['pass'] === 'BizNexus@2024') $_SESSION['m'] = true;
    else $err = 'Wrong password';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit; }
$auth = !empty($_SESSION['m']);

define('DATA_DIR', __DIR__ . '/data/');
define('CRON_KEY', 'BizCron2024');
define('TOTAL', 40);

$task_names = [1=>'Database',2=>'DB Connection',3=>'CSS Theme',4=>'JS Utils',5=>'Homepage',6=>'Auth System',7=>'Dashboard',8=>'Profiles',9=>'Referrals',10=>'Meetings',11=>'Marketplace',12=>'CRM',13=>'Invoicing',14=>'Community+AI',15=>'Admin Panel',16=>'Coins+Pages',17=>'Directory+Reviews',18=>'Onboarding',19=>'Payments',20=>'Ads+Nav',21=>'API+PWA',22=>'Cron Jobs',23=>'SEO+Contact',24=>'CEO Report',25=>'Final Polish'];

if (isset($_GET['status'])) {
    header('Content-Type: application/json');
    $prog = json_decode(file_exists(DATA_DIR.'autorun_progress.json') ? file_get_contents(DATA_DIR.'autorun_progress.json') : '{}', true) ?: [];
    $done = count(array_filter($prog, fn($p) => $p['status'] === 'DONE'));
    $logs = [];
    if (file_exists(DATA_DIR.'autorun.log')) {
        $lines = array_filter(explode("\n", trim(file_get_contents(DATA_DIR.'autorun.log'))));
        $logs = array_slice(array_reverse(array_values($lines)), 0, 30);
    }
    echo json_encode(['progress' => $prog, 'done' => $done, 'logs' => $logs]);
    exit;
}

if (isset($_GET['trigger']) && $auth) {
    // Manually trigger one cron run
    header('Location: cron.php?key=' . CRON_KEY);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="30">
<title>BizNexus Build Monitor</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0a0a0f;color:#e0e0f0;font-family:monospace;padding:16px}
h1{color:#FFD700;margin-bottom:4px}
.sub{color:#666;font-size:.8rem;margin-bottom:20px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat{background:#13131a;border-radius:10px;padding:16px;text-align:center}
.stat .n{font-size:2rem;font-weight:bold;color:#FFD700}
.stat .l{color:#666;font-size:.75rem}
.bar-bg{background:#2a2a3a;border-radius:6px;height:10px;margin-bottom:16px}
.bar-fill{height:10px;border-radius:6px;background:linear-gradient(90deg,#FFD700,#00ff88);transition:width .5s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:20px}
.tc{background:#13131a;border:1px solid #2a2a3a;border-radius:8px;padding:10px}
.tc.done{border-color:#00ff88}
.tc.failed{border-color:#ff4444}
.tc.running{border-color:#FFD700;animation:p 1.2s infinite}
@keyframes p{0%,100%{opacity:1}50%{opacity:.5}}
.tn{font-size:.65rem;color:#666}
.tt{font-size:.78rem;font-weight:bold;margin:3px 0}
.ts{font-size:.7rem}
.ts.done{color:#00ff88}.ts.failed{color:#ff4444}.ts.running{color:#FFD700}.ts.pending{color:#555}
.logs{background:#13131a;border-radius:10px;padding:14px;max-height:250px;overflow-y:auto;font-size:.72rem}
.ll{padding:2px 0;border-bottom:1px solid #1a1a2a;color:#666}
.ll.ok{color:#00ff88}.ll.er{color:#ff4444}.ll.wn{color:#FFD700}
.btn{background:#FFD700;color:#000;border:none;padding:10px 20px;border-radius:8px;font-weight:bold;cursor:pointer;font-size:.85rem;text-decoration:none;display:inline-block}
.btn-s{background:transparent;border:1px solid #2a2a3a;color:#666;padding:8px 14px;border-radius:6px;cursor:pointer;font-size:.78rem;font-family:monospace}
.lw{display:flex;align-items:center;justify-content:center;min-height:100vh}
.lb{background:#13131a;border:2px solid #FFD700;border-radius:16px;padding:36px;width:340px;text-align:center}
.lb h1{margin-bottom:20px}
.lb input{width:100%;padding:12px;background:#1a1a24;border:1px solid #2a2a3a;border-radius:8px;color:#e0e0f0;font-size:1rem;margin-bottom:12px;font-family:monospace;text-align:center}
.note{background:#1a1200;border:1px solid #FFD700;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.8rem;color:#FFD700}
</style>
</head>
<body>
<?php if (!$auth): ?>
<div class="lw">
<div class="lb">
<h1>⚡ BizNexus Monitor</h1>
<?php if (!empty($err)): ?><p style="color:#ff4444;margin-bottom:10px"><?= $err ?></p><?php endif; ?>
<form method="POST">
<input type="password" name="pass" placeholder="Password" autofocus>
<button type="submit" class="btn" style="width:100%">Login</button>
</form>
</div></div>
<?php else: ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
<h1>⚡ BizNexus Build Monitor</h1>
<div style="display:flex;gap:8px">
<a href="cron.php?key=<?= CRON_KEY ?>" class="btn" style="font-size:.78rem;padding:8px 14px">▶ Run Next Task</a>
<a href="?logout" class="btn-s">Logout</a>
</div>
</div>
<p class="sub">Auto-refreshes every 30s • Setup Hostinger cron to run automatically</p>

<div class="note">
⚙️ <strong>Hostinger Cron Setup (do once):</strong><br>
hPanel → Advanced → Cron Jobs → Add:<br>
Every 5 min | Command: <code>curl "https://agent.biznexus.in/cron.php?key=BizCron2024"</code>
</div>

<div class="stats">
<div class="stat"><div class="n" id="s-done">-</div><div class="l">Done</div></div>
<div class="stat"><div class="n" id="s-pct">0%</div><div class="l">Complete</div></div>
<div class="stat"><div class="n" id="s-fail" style="color:#ff4444">-</div><div class="l">Failed</div></div>
<div class="stat"><div class="n" id="s-rem" style="color:#4488ff">-</div><div class="l">Remaining</div></div>
</div>

<div class="bar-bg"><div class="bar-fill" id="bar" style="width:0%"></div></div>

<div class="grid" id="grid">
<?php for ($i=1; $i<=TOTAL; $i++): ?>
<div class="tc" id="tc-<?=$i?>">
<div class="tn">Task <?=$i?></div>
<div class="tt"><?= $task_names[$i] ?? "Module $i" ?></div>
<div class="ts pending" id="ts-<?=$i?>">⏳ Pending</div>
</div>
<?php endfor; ?>
</div>

<div class="logs" id="logs"><div class="ll">Loading logs...</div></div>

<script>
async function refresh() {
    const r = await fetch('?status=1');
    const d = await r.json();
    const done = d.done || 0;
    const failed = Object.values(d.progress||{}).filter(p=>p.status==='FAILED').length;
    const pct = Math.round(done/<?=TOTAL?>*100);

    document.getElementById('s-done').textContent = done;
    document.getElementById('s-pct').textContent = pct+'%';
    document.getElementById('s-fail').textContent = failed;
    document.getElementById('s-rem').textContent = <?=TOTAL?> - done;
    document.getElementById('bar').style.width = pct+'%';

    for (const [num, prog] of Object.entries(d.progress||{})) {
        const tc = document.getElementById('tc-'+num);
        const ts = document.getElementById('ts-'+num);
        if (!tc||!ts) continue;
        if (prog.status==='DONE') { tc.className='tc done'; ts.textContent='✅ Done'; ts.className='ts done'; }
        else if (prog.status==='FAILED') { tc.className='tc failed'; ts.textContent='❌ Failed'; ts.className='ts failed'; }
        else if (prog.status==='running') { tc.className='tc running'; ts.textContent='⚡ Running'; ts.className='ts running'; }
    }

    const logs = document.getElementById('logs');
    logs.innerHTML = (d.logs||[]).map(l => {
        const type = l.includes('DONE')||l.includes('FILE_OK')?'ok':l.includes('ERROR')||l.includes('FAIL')?'er':'wn';
        return `<div class="ll ${type}">${l}</div>`;
    }).join('');
}

refresh();
setInterval(refresh, 10000);
</script>
<?php endif; ?>
</body>
</html>
