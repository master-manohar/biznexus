<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, plan, plan_expires_at, coins FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$plan        = $user['plan'] ?? 'free';
$expires     = $user['plan_expires_at'] ?? null;
$days_left   = $expires ? max(0, (int)((strtotime($expires) - time()) / 86400)) : 0;
$coins       = (int)($user['coins'] ?? 0);

// Coins just awarded (from session flash or query param)
$coins_awarded = (int)($_GET['coins'] ?? 0);
$billing       = $_GET['billing'] ?? 'monthly';

$plan_info = [
    'free'     => ['emoji'=>'🆓','color'=>'#666699','label'=>'Free'],
    'silver'   => ['emoji'=>'⚪','color'=>'#c0c0c0','label'=>'Silver'],
    'gold'     => ['emoji'=>'🥇','color'=>'#FFD700','label'=>'Gold'],
    'platinum' => ['emoji'=>'💎','color'=>'#a259ff','label'=>'Platinum'],
];
$pi = $plan_info[$plan] ?? $plan_info['free'];

$page_title = 'Payment Successful — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<style>
@keyframes popIn { from{opacity:0;transform:scale(.85) translateY(20px)} to{opacity:1;transform:scale(1) translateY(0)} }
@keyframes shimmer { 0%,100%{opacity:1} 50%{opacity:.6} }
@keyframes confetti-fall {
    0%   { transform: translateY(-10px) rotate(0deg);   opacity:1; }
    100% { transform: translateY(120vh) rotate(720deg); opacity:0; }
}

.success-wrap { max-width:560px; margin:0 auto; padding:20px 0 40px; }

.confetti-piece {
    position:fixed; width:10px; height:10px; border-radius:2px;
    animation: confetti-fall linear forwards;
    pointer-events:none; z-index:9999;
}

.success-card {
    background:#13131a; border:1px solid rgba(255,215,0,.2);
    border-radius:20px; padding:36px 32px; text-align:center;
    animation: popIn .5s cubic-bezier(.34,1.56,.64,1);
    position:relative; overflow:hidden;
}
.success-card::before {
    content:''; position:absolute; top:0;left:0;right:0;height:4px;
    background:linear-gradient(90deg,#FFD700,#ff8c00,#FFD700);
    background-size:200%; animation:shimmer 2s infinite;
}
.check-circle {
    width:80px; height:80px; background:rgba(0,232,122,.1);
    border:2px solid rgba(0,232,122,.3); border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; margin:0 auto 20px;
}
.plan-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.25);
    border-radius:40px; padding:8px 20px; margin:12px 0;
    font-size:1rem; font-weight:700;
}

.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:22px 0; text-align:left; }
.detail-box { background:#0d0d16; border:1px solid #1e1e2e; border-radius:12px; padding:14px 16px; }
.detail-box .d-label { font-size:.68rem; color:#666699; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px; }
.detail-box .d-value { font-size:1rem; font-weight:700; color:#e8e8f5; }

.days-bar { height:6px; background:#1a1a28; border-radius:3px; overflow:hidden; margin-top:8px; }
.days-bar-fill { height:100%; border-radius:3px; }

.btn-dash {
    display:block; width:100%; padding:13px;
    background:linear-gradient(135deg,#FFD700,#ff8c00); color:#000;
    font-weight:800; border-radius:10px; text-decoration:none;
    font-size:.95rem; margin-top:20px; transition:.2s;
}
.btn-dash:hover { opacity:.9; transform:translateY(-1px); color:#000; }
.btn-secondary {
    display:block; width:100%; padding:11px; color:#8888aa;
    border:1px solid #2a2a3a; border-radius:10px; text-decoration:none;
    font-size:.84rem; margin-top:10px; transition:.2s;
}
.btn-secondary:hover { border-color:#FFD700; color:#e8e8f5; }
</style>

<!-- Confetti JS -->
<script>
(function(){
    var colors = ['#FFD700','#ff8c00','#00e87a','#4488ff','#a259ff','#ff4d6d'];
    for(var i=0;i<60;i++){
        setTimeout(function(){
            var el = document.createElement('div');
            el.className = 'confetti-piece';
            el.style.cssText = [
                'left:'+(Math.random()*100)+'vw',
                'top:-10px',
                'background:'+colors[Math.floor(Math.random()*colors.length)],
                'animation-duration:'+(2+Math.random()*3)+'s',
                'animation-delay:'+(Math.random()*.5)+'s',
                'width:'+(6+Math.random()*10)+'px',
                'height:'+(6+Math.random()*10)+'px',
                'border-radius:'+Math.round(Math.random())+'50%'
            ].join(';');
            document.body.appendChild(el);
            setTimeout(function(){el.remove();},5000);
        }, i*60);
    }
})();
</script>

<div class="success-wrap">
    <div class="success-card">
        <div class="check-circle">✅</div>

        <h2 style="color:#e8e8f5;font-family:'Syne',sans-serif;font-weight:800;margin-bottom:6px;">Payment Successful!</h2>
        <p style="color:#8888aa;font-size:.88rem;">Welcome to BizNexus <?= $pi['label'] ?> — you're all set!</p>

        <div class="plan-badge">
            <span style="font-size:1.3rem;"><?= $pi['emoji'] ?></span>
            <span style="color:<?= $pi['color'] ?>;"><?= $pi['label'] ?> Plan</span>
            <span style="color:#8888aa;font-size:.75rem;font-weight:500;"><?= ucfirst($billing) ?></span>
        </div>

        <div class="detail-grid">
            <div class="detail-box">
                <div class="d-label">Plan Active Until</div>
                <div class="d-value" style="font-size:.85rem;"><?= $expires ? date('d M Y', strtotime($expires)) : '—' ?></div>
                <?php $pct = $billing==='yearly'?min(100,round(($days_left/365)*100)):min(100,round(($days_left/30)*100)); ?>
                <div class="days-bar"><div class="days-bar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,<?= $pi['color'] ?>,#ff8c00);"></div></div>
            </div>
            <div class="detail-box">
                <div class="d-label">Days Remaining</div>
                <div class="d-value" style="color:<?= $pi['color'] ?>;"><?= $days_left ?> days</div>
            </div>
            <div class="detail-box">
                <div class="d-label">Bonus Coins Earned</div>
                <div class="d-value" style="color:#FFD700;">+<?= number_format($coins_awarded) ?> 🪙</div>
            </div>
            <div class="detail-box">
                <div class="d-label">Total Coin Balance</div>
                <div class="d-value" style="color:#FFD700;"><?= number_format($coins) ?> 🪙</div>
            </div>
        </div>

        <div style="background:#0d0d16;border-radius:10px;padding:12px 14px;font-size:.79rem;color:#666699;text-align:left;margin-top:4px;">
            🔒 Payment secured by <strong style="color:#9090b8;">Razorpay</strong> · Managed by <strong style="color:#9090b8;">BookAnEvent</strong>
        </div>

        <a href="/dashboard/index.php" class="btn-dash">→ Go to Dashboard</a>
        <a href="/profile/edit.php" class="btn-secondary">👤 View Membership in Profile</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
