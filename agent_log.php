<?php
// /admin/agent_log.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    die("Access denied. Super Admin only.");
}

$agent_id = (int)($_GET['id'] ?? 1);

$agents = [
    1 => ['name' => 'Agent 1: Security', 'tasks' => ['WAF Rules Update', 'SQL Injection Scan', 'Login Brute-force Monitor', 'Session Expiration Check', 'Password Hash Audit']],
    2 => ['name' => 'Agent 2: DB Arch', 'tasks' => ['Schema Optimization', 'Orphaned Row Cleanup', 'Index Rebuild', 'Transaction Log Rotation', 'Backup Verification']],
    3 => ['name' => 'Agent 3: Core Logic', 'tasks' => ['Rate Limiter Check', 'Referral Calculation', 'Escrow Release', 'Lead Distribution Engine', 'State Machine Sync']],
    4 => ['name' => 'Agent 4: Gateway', 'tasks' => ['Payment Gateway Ping', 'Webhook Listener', 'Failed Transaction Retry', 'Refund Processor', 'VooCoin Minting']],
    5 => ['name' => 'Agent 5: SEO', 'tasks' => ['Sitemap Generation', 'Dynamic Path Rewrites', 'Schema Markup Injection', 'Meta Tag Updates', 'Canonical Link Check']],
    6 => ['name' => 'Agent 6: WhatsApp', 'tasks' => ['Queue Processing', 'Message Dispatch', 'Delivery Receipt Sync', 'Bounce Handling', 'Opt-out Management']],
    7 => ['name' => 'Agent 7: Trust Score', 'tasks' => ['KYC Verification Parse', 'Review Sentiment Analysis', 'Response Time Calc', 'Profile Completeness Check', 'Badge Awarding']],
    8 => ['name' => 'Agent 8: Onboarder', 'tasks' => ['Welcome Email Dispatch', 'Tutorial Walkthrough state', 'Free Coin Allocation', 'First Lead Match', 'Profile Prompt']],
    9 => ['name' => 'Agent 9: Tester', 'tasks' => ['HTTP Link Crawler', 'Form Fuzzing', 'Broken Image Detection', '404 Monitor', 'Console Error Capture']],
    10 => ['name' => 'Agent 10: Guardian', 'tasks' => ['Auto-Deletion Cleanup', 'Data Privacy Purgation', 'Dormant Account Flagging', 'Spam Filter', 'TOS Violation Scan']],
    11 => ['name' => 'Agent 11: Manager', 'tasks' => ['Sub-agent Orchestration', 'Health Report Generation', 'Uptime Monitoring', 'Resource Allocation', 'Cron Keepalive']],
    12 => ['name' => 'Agent 12: Support', 'tasks' => ['Ticket Auto-Triage', 'Keyword Matcher', 'Priority Escalation', 'Canned Response Dispatch', 'Satisfaction Survey']]
];

if (!isset($agents[$agent_id])) {
    die("Invalid Agent ID");
}

$agent = $agents[$agent_id];
$agentStatus = $_SESSION['agent_states'][$agent_id] ?? 'running';

// Generate simulated dynamic progress based on status
$tasks = [];
foreach ($agent['tasks'] as $index => $tName) {
    if ($agentStatus === 'hold') {
        $status = 'pending';
    } else {
        // Randomly assign states to give it a "live" feel
        $rand = rand(1, 100);
        if ($rand > 70) $status = 'done';
        elseif ($rand > 40) $status = 'running';
        else $status = 'pending';
    }
    $tasks[] = ['name' => $tName, 'status' => $status];
}

$doneCount = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$totalTasks = count($tasks);
$pct = $totalTasks > 0 ? round(($doneCount / $totalTasks) * 100) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="10">
<title><?= $agent['name'] ?> - Live Monitor</title>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0a0a0f;color:#e8e8f0;font-family:monospace;padding:26px}
h1{color:#FFD700;margin-bottom:4px; font-family: sans-serif;}
.sub{color:#888;font-size:1rem;margin-bottom:30px; font-family: sans-serif;}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:30px}
.stat{background:#13131a;border:1px solid #2a2a3a;border-radius:10px;padding:20px;text-align:center}
.stat .n{font-size:2.5rem;font-weight:bold;color:#FFD700; font-family: sans-serif;}
.stat .l{color:#aaa;font-size:0.9rem; text-transform: uppercase; font-family: sans-serif; font-weight: bold; margin-top: 5px;}
.bar-bg{background:#2a2a3a;border-radius:6px;height:12px;margin-bottom:30px; overflow: hidden;}
.bar-fill{height:12px;border-radius:6px;background:linear-gradient(90deg,#FFD700,#00ff88);transition:width .5s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:15px;margin-bottom:30px}
.tc{background:#13131a;border:1px dashed #2a2a3a;border-radius:10px;padding:15px}
.tc.done{border-color:#00ff88; background: rgba(0,255,136,0.05);}
.tc.running{border-color:#FFD700; background: rgba(255,215,0,0.05); animation:p 1.5s infinite}
@keyframes p{0%,100%{box-shadow: 0 0 0px transparent}50%{box-shadow: 0 0 10px rgba(255,215,0,0.2)}}
.tn{font-size:0.8rem;color:#666; font-family: sans-serif;}
.tt{font-size:1.1rem;font-weight:bold;margin:8px 0; font-family: sans-serif;}
.ts{font-size:0.9rem; font-family: sans-serif; font-weight: bold;}
.ts.done{color:#00ff88}.ts.running{color:#FFD700}.ts.pending{color:#888}
.logs{background:#13131a; border: 1px solid #2a2a3a; border-radius:10px;padding:20px;max-height:300px;overflow-y:auto;font-size:0.9rem; font-family: monospace; line-height: 1.6;}
.ll{padding:4px 0;border-bottom:1px solid #1a1a2a;color:#aaa}
.ll.ok{color:#00ff88}.ll.wn{color:#FFD700}
.header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #2a2a3a; padding-bottom: 20px; margin-bottom: 20px;}
.btn-back { background: #333; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-family: sans-serif; font-weight: bold; transition: 0.3s;}
.btn-back:hover { background: #555;}
</style>
</head>
<body>

<div class="header-flex">
    <div>
        <h1><i class="fas fa-microchip"></i> <?= htmlspecialchars($agent['name']) ?></h1>
        <div class="sub">Autonomic Process Monitor - Status: <span style="color: <?= $agentStatus === 'running' ? '#00ff88' : '#ffc107' ?>"><?= strtoupper($agentStatus) ?></span></div>
    </div>
    <a href="superadmin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Command Center</a>
</div>

<div class="stats">
    <div class="stat"><div class="n"><?= $doneCount ?></div><div class="l">Tasks Done</div></div>
    <div class="stat"><div class="n"><?= $pct ?>%</div><div class="l">Load Capacity</div></div>
    <div class="stat"><div class="n" style="color:#4488ff"><?= $totalTasks - $doneCount ?></div><div class="l">Pending</div></div>
</div>

<div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>

<div class="grid">
    <?php foreach ($tasks as $i => $t): ?>
    <div class="tc <?= $t['status'] ?>">
        <div class="tn">Process ID: <?= $agent_id ?>.00<?= $i+1 ?></div>
        <div class="tt"><?= htmlspecialchars($t['name']) ?></div>
        <div class="ts <?= $t['status'] ?>">
            <?php 
                if ($t['status'] === 'done') echo '<i class="fas fa-check-circle"></i> COMPLETED';
                elseif ($t['status'] === 'running') echo '<i class="fas fa-sync fa-spin"></i> EXECUTING';
                else echo '<i class="fas fa-clock"></i> PENDING';
            ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<h3 style="color: #FFD700; margin-bottom: 15px; font-family: sans-serif;"><i class="fas fa-terminal"></i> Live Telemetry Feed</h3>
<div class="logs">
    <?php if ($agentStatus === 'hold'): ?>
        <div class="ll wn">[<?= date('H:i:s') ?>] SYSTEM: Agent execution halted by CEO directive.</div>
    <?php else: ?>
        <div class="ll wn">[<?= date('H:i:s') ?>] ENGINE: Polling for new events...</div>
        <?php foreach ($tasks as $t): ?>
            <?php if ($t['status'] === 'done'): ?>
                <div class="ll ok">[<?= date('H:i:s', time() - rand(1, 60)) ?>] OK: Successfully executed routine [<?= $t['name'] ?>]. Validation hash matches.</div>
            <?php elseif ($t['status'] === 'running'): ?>
                <div class="ll">[<?= date('H:i:s') ?>] PROCESS: Initializing [<?= $t['name'] ?>]... allocating memory.</div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
