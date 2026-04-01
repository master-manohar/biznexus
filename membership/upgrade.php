<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/razorpay_config.php';

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$current_plan   = $user['plan'] ?? 'free';
$selected_plan  = $_GET['plan']    ?? 'gold';
$billing_cycle  = $_GET['billing'] ?? 'yearly'; // 'monthly' or 'yearly'
if (!isset(PLAN_PRICES[$selected_plan])) $selected_plan = 'gold';
if (!in_array($billing_cycle, ['monthly','yearly'])) $billing_cycle = 'yearly';

$plan_data = PLAN_PRICES[$selected_plan];
$amount    = $billing_cycle === 'yearly' ? $plan_data['yearly_amount'] : $plan_data['monthly_amount'];
$duration  = $billing_cycle === 'yearly' ? 365 : 30;

// Create Razorpay order
$order = null; $order_error = '';
try {
    $payload = json_encode([
        'amount'   => $amount,
        'currency' => RAZORPAY_CURRENCY,
        'receipt'  => 'bnx_'.$uid.'_'.time(),
        'notes'    => ['user_id'=>$uid,'plan'=>$selected_plan,'billing'=>$billing_cycle]
    ]);
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_USERPWD    => RAZORPAY_KEY_ID.':'.RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $order = json_decode($res, true);
    if ($code !== 200 || empty($order['id'])) { $order_error = $order['error']['description'] ?? 'Order creation failed'; $order = null; }
} catch(Exception $e) { $order_error = $e->getMessage(); }

$page_title = 'Upgrade Membership — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<style>
/* ── Billing Toggle ── */
.billing-toggle { display:inline-flex; background:#0d0d16; border:1px solid #2a2a3a; border-radius:40px; padding:4px; margin-bottom:28px; }
.billing-toggle a {
    padding:8px 24px; border-radius:35px; font-size:.83rem; font-weight:600;
    text-decoration:none; color:#8888aa; transition:.2s;
}
.billing-toggle a.active { background:linear-gradient(135deg,#FFD700,#ff8c00); color:#000; }
.save-tag { background:rgba(0,232,122,.12); border:1px solid rgba(0,232,122,.25); color:#00e87a; font-size:.65rem; font-weight:700; border-radius:20px; padding:2px 8px; margin-left:6px; vertical-align:middle; }

/* ── Plan Cards ── */
.plan-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-bottom:28px; }
.plan-card {
    background:#13131a; border:2px solid #1e1e2e; border-radius:16px; padding:24px 20px;
    text-decoration:none; display:block; position:relative; overflow:hidden; transition:.2s; cursor:pointer;
}
.plan-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; border-radius:16px 16px 0 0; }
.plan-card.silver::before { background:linear-gradient(90deg,#a0a0c0,#c8c8e0); }
.plan-card.gold::before   { background:linear-gradient(90deg,#FFD700,#ff8c00); }
.plan-card.platinum::before { background:linear-gradient(90deg,#a259ff,#4488ff); }
.plan-card:hover         { border-color:rgba(255,215,0,.4); transform:translateY(-2px); }
.plan-card.chosen        { border-color:#FFD700 !important; background:#0f0f18; }

.pop-badge { position:absolute; top:12px; right:14px; background:#FFD700; color:#000; font-size:.6rem; font-weight:800; padding:2px 9px; border-radius:20px; }

.plan-icon  { font-size:1.6rem; margin-bottom:8px; }
.plan-name  { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; margin-bottom:12px; }
.plan-price-main { font-size:2.1rem; font-weight:900; color:#e8e8f5; line-height:1; }
.plan-price-main sup { font-size:1rem; font-weight:600; vertical-align:top; margin-top:6px; display:inline-block; color:#c0c0d8; }
.plan-price-main span { font-size:.7rem; color:#8888aa; font-weight:500; }
.billed-note { font-size:.72rem; color:#666699; margin:6px 0 14px; }
.orig-price  { font-size:.75rem; color:#555577; text-decoration:line-through; margin-bottom:2px; }
.feat-list   { list-style:none; padding:0; margin:0; }
.feat-list li { font-size:.78rem; color:#a0a0c0; padding:4px 0; }
.feat-list li::before { content:'✓ '; color:#00e87a; font-weight:700; }

/* ── Checkout box ── */
.checkout-box { background:#13131a; border:1px solid #2a2a3a; border-radius:14px; padding:24px; }
.order-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid #1a1a2a; font-size:.85rem; }
.order-row:last-child { border:none; font-weight:700; font-size:.95rem; }
.btn-pay {
    width:100%; padding:14px; background:linear-gradient(135deg,#FFD700,#ff8c00);
    color:#000; font-weight:800; border:none; border-radius:10px;
    font-size:1rem; cursor:pointer; transition:.2s; margin-top:18px;
}
.btn-pay:hover { opacity:.9; transform:translateY(-1px); box-shadow:0 6px 20px rgba(255,215,0,.3); }
.secure-row { display:flex; gap:14px; justify-content:center; margin-top:10px; flex-wrap:wrap; }
.secure-row span { font-size:.7rem; color:#555577; display:flex; align-items:center; gap:4px; }
</style>

<!-- Header -->
<div style="margin-bottom:22px;">
    <h2 style="font-family:'Syne',sans-serif;font-weight:800;color:#e8e8f5;font-size:1.4rem;">💎 Upgrade Membership</h2>
    <p style="color:#8888aa;margin:5px 0 0;font-size:.84rem;">More value, bigger network, more leads. Switch anytime.</p>
</div>

<!-- Billing Toggle -->
<div style="text-align:center;">
    <div class="billing-toggle">
        <a href="?plan=<?= $selected_plan ?>&billing=monthly" class="<?= $billing_cycle==='monthly'?'active':'' ?>">Monthly</a>
        <a href="?plan=<?= $selected_plan ?>&billing=yearly"  class="<?= $billing_cycle==='yearly' ?'active':'' ?>">
            Yearly <span class="save-tag">SAVE UP TO 20%</span>
        </a>
    </div>
</div>

<!-- Plan Cards -->
<div class="plan-grid">
<?php foreach (PLAN_PRICES as $key => $p):
    $isChosen = ($key === $selected_plan);
    $ppm  = $billing_cycle === 'yearly' ? $p['yearly_ppm']  : $p['monthly_ppm'];
    $orig = $billing_cycle === 'yearly' ? $p['monthly_ppm'] : null;
    $note = $billing_cycle === 'yearly' ? "Billed {$p['yearly_total']} · {$p['saving']}" : 'Billed monthly';
    $numericPpm = preg_replace('/[^0-9]/', '', $ppm);
?>
<a href="?plan=<?= $key ?>&billing=<?= $billing_cycle ?>" class="plan-card <?= $key ?> <?= $isChosen?'chosen':'' ?>">
    <?php if (!empty($p['popular'])): ?><div class="pop-badge">⭐ POPULAR</div><?php endif; ?>
    <div class="plan-icon"><?= $p['emoji'] ?></div>
    <div class="plan-name" style="color:<?= $p['color'] ?>;"><?= $p['label'] ?></div>

    <?php if ($billing_cycle === 'yearly'): ?>
    <div class="orig-price"><?= $p['monthly_ppm'] ?>/mo</div>
    <?php endif; ?>

    <div class="plan-price-main"><sup>₹</sup><?= number_format((int)preg_replace('/[^0-9]/','',$ppm)) ?><span>/mo</span></div>
    <div class="billed-note"><?= $note ?></div>

    <ul class="feat-list">
        <?php foreach ($p['features'] as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?>
    </ul>
</a>
<?php endforeach; ?><?php if ($order):
    $coins = $billing_cycle === 'yearly' ? $plan_data['coins_yearly'] : $plan_data['coins_monthly'];
?>
</div>

<!-- Order Error -->
<?php if ($order_error): ?>
<div style="background:rgba(255,77,109,.1);border:1px solid rgba(255,77,109,.3);border-radius:10px;padding:14px 16px;color:#ff4d6d;font-size:.85rem;margin-bottom:20px;">❌ <?= htmlspecialchars($order_error) ?></div>
<?php endif; ?>

<!-- Checkout Box -->
<div class="checkout-box">
    <!-- Coupon Box -->
    <div style="background:rgba(255,215,0,0.03); border:1px solid rgba(255,215,0,0.15); border-radius:10px; padding:15px; margin-bottom:20px;">
        <h6 style="color:#FFD700;font-size:.8rem;margin-bottom:10px;"><i class="fas fa-ticket-alt"></i> Have a Coupon Code?</h6>
        <div class="input-group">
            <input type="text" id="couponCode" class="form-control" style="background:#0d0d16; border-color:#2a2a3a; color:#fff; font-size:.85rem;" placeholder="e.g. H2H50">
            <button type="button" class="btn btn-gold btn-sm" onclick="applyCoupon()">Apply</button>
        </div>
        <div id="couponMsg" style="font-size:.72rem;margin-top:6px;"></div>
    </div>

    <div style="background:#0d0d16; border-radius:12px; padding:18px; border:1px solid #1e1e2e;">
        <div class="order-row"><span>Plan Card</span> <span style="color:#FFD700;"><?= $plan_data['label'] ?></span></div>
        <div class="order-row"><span>Billing Cycle</span> <span style="text-transform:capitalize;"><?= $billing_cycle ?></span></div>
        <div class="order-row"><span>Original Amount</span> <span><?= $billing_cycle === 'yearly' ? $plan_data['yearly_total'] : $plan_data['monthly_ppm'] ?></span></div>
        
        <div id="discountRow" style="display:none;" class="order-row">
            <span style="color:#00e87a;">Discount Applied</span> 
            <span id="discountVal" style="color:#00e87a;font-weight:700;">-₹0</span>
        </div>

        <div class="order-row">
            <span style="color:#8888aa;">Bonus Coins</span>
            <span style="color:#FFD700;font-weight:700;">+<?= $coins ?> 🪙</span>
        </div>

        <hr style="border-color:#2a2a3a;margin:12px 0;">
        <div class="order-row" style="font-size:1.1rem;font-weight:900;color:#fff;border:none;">
            <span>Total Payable</span> 
            <span id="totalDisplay"><?= $billing_cycle === 'yearly' ? $plan_data['yearly_total'] : $plan_data['monthly_ppm'] ?></span>
        </div>
    </div>

    <button id="rzp-button1" class="btn-pay">🔐 Pay <span id="btnAmount"><?= $billing_cycle === 'yearly' ? $plan_data['yearly_total'] : $plan_data['monthly_ppm'] ?></span> with Razorpay</button>

    <div class="secure-row">
        <span>🔒 Secured by Razorpay</span>
        <span>🏢 BookAnEvent</span>
        <span>📅 Event Support</span>
    </div>
    <p style="font-size:.65rem; color:#555577; text-align:center; margin-top:14px; line-height:1.3;">
        * BizNexus services (Digital B2B Networking, Website Consulting, and AI Group Meetings) are listed under <strong>Event Booking & Business Services</strong> via BookAnEvent.
    </p>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var originalAmountPaise = <?= $order['amount'] ?>;
var currentAmountPaise  = originalAmountPaise;
var appliedCoupon = '';

function applyCoupon() {
    var code = document.getElementById('couponCode').value.trim();
    if(!code) return;
    var msgEl = document.getElementById('couponMsg');
    msgEl.innerHTML = '<span style="color:#888;">Validating...</span>';

    fetch('/api/validate_coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'code=' + encodeURIComponent(code)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            appliedCoupon = code;
            var discount = 0;
            if(data.type === 'percentage') {
                discount = Math.round(originalAmountPaise * (data.value / 100));
            } else {
                discount = data.value * 100;
            }
            currentAmountPaise = originalAmountPaise - discount;
            if(currentAmountPaise < 100) currentAmountPaise = 100;

            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discountVal').innerText = '-₹' + (discount/100).toLocaleString();
            document.getElementById('totalDisplay').innerText = '₹' + (currentAmountPaise/100).toLocaleString();
            document.getElementById('btnAmount').innerText = '₹' + (currentAmountPaise/100).toLocaleString();
            msgEl.innerHTML = '<span style="color:#00e87a;">✅ ' + data.message + '</span>';
        } else {
            msgEl.innerHTML = '<span style="color:#ff4d6d;">❌ ' + data.error + '</span>';
        }
    });
}

document.getElementById('rzp-button1').onclick = function(e){
    e.preventDefault();
    var btn = this;
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Payment...';
    btn.disabled = true;

    // Fetch a FRESH Order ID with the applied coupon
    fetch('/api/create_payment_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'plan=<?= $selected_plan ?>&billing=<?= $billing_cycle ?>&coupon=' + encodeURIComponent(appliedCoupon)
    })
    .then(r => r.json())
    .then(data => {
        if(!data.success) {
            alert("Payment Error: " + data.error);
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }

        // SUCCESS: Redirect to the Secure Bridge on BookAnEvent
        var bridgeUrl = "https://www.bookanevent.in/pay_biznexus.php?";
        bridgeUrl += "order_id=" + data.order_id;
        bridgeUrl += "&amount=" + data.amount;
        bridgeUrl += "&user_id=<?= $uid ?>";
        bridgeUrl += "&plan=<?= $selected_plan ?>";
        bridgeUrl += "&billing=<?= $billing_cycle ?>";
        
        window.location.href = bridgeUrl;
    })
    .catch(err => {
        alert("System Error: Could not initiate payment.");
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
<?php endif; ?>

<!-- ── Plan Comparison Table ── -->
<div style="margin-top:36px;">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;color:#e8e8f5;font-size:1rem;margin-bottom:16px;text-align:center;">📊 Full Plan Comparison</h3>
    <div style="background:#13131a;border:1px solid #1e1e2e;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
        <thead>
            <tr style="background:#0d0d16;border-bottom:1px solid #2a2a3a;">
                <th style="padding:12px 16px;text-align:left;color:#666699;font-size:.68rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;width:30%;">Feature</th>
                <th style="padding:12px 10px;text-align:center;color:#666699;font-size:.72rem;">🆓 Free</th>
                <th style="padding:12px 10px;text-align:center;color:#c0c0c0;font-size:.72rem;">⚪ Silver</th>
                <th style="padding:12px 10px;text-align:center;color:#FFD700;font-size:.72rem;background:rgba(255,215,0,.04);">🥇 Gold</th>
                <th style="padding:12px 10px;text-align:center;color:#a259ff;font-size:.72rem;">💎 Platinum</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rows = [
            ['Lead Claims / Month',     '3',         '40',        '80',           '∞ Unlimited'],
            ['Marketplace Listings',    '1',         '5',         '20',           '∞ Unlimited'],
            ['CRM Dashboard',           '✅',         '✅',         '✅',            '✅'],
            ['Group Membership',        '✅',         '✅',         '✅',            '✅'],
            ['Meetings (Join/Host)',     'View only', '✅',         '✅',            '✅'],
            ['Analytics',               'Basic',     'Standard',  'Advanced',     'Full + Export'],
            ['Verified Badge',          '❌',         '❌',         '✅',            '✅'],
            ['Custom Branding',         '❌',         '❌',         '❌',            '✅'],
            ['API Access',              '❌',         '❌',         '❌',            '✅'],
            ['Account Manager',         '❌',         '❌',         '❌',            '✅'],
            ['Email Support',           '❌',         '✅',         '✅',            '✅ Priority'],
            ['Monthly Price',           'Free',      '₹1,200',    '₹2,400',       '₹5,000'],
            ['Yearly (per month)',       '—',         '₹999',      '₹1,999',       '₹3,999'],
            ['Bonus Coins on Join',     '0',         '200 🪙',    '500 🪙',       '1,000 🪙'],
        ];
        foreach ($rows as $i => [$feat, $free, $silver, $gold, $plat]):
            $bg = $i % 2 === 0 ? 'background:rgba(255,255,255,.01);' : '';
        ?>
        <tr style="border-bottom:1px solid rgba(42,42,58,.4);<?= $bg ?>">
            <td style="padding:10px 16px;color:#c0c0d8;font-weight:500;"><?= $feat ?></td>
            <td style="padding:10px;text-align:center;color:#666699;"><?= $free ?></td>
            <td style="padding:10px;text-align:center;color:#a0a0c0;"><?= $silver ?></td>
            <td style="padding:10px;text-align:center;color:#FFD700;background:rgba(255,215,0,.03);"><?= $gold ?></td>
            <td style="padding:10px;text-align:center;color:#a259ff;"><?= $plat ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p style="text-align:center;color:#555577;font-size:.72rem;margin-top:12px;">All plans include access to BizNexus core platform. Prices are per month. Yearly plans billed annually.</p>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
