<?php
// /dashboard/upgrade.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$uid = (int)$_SESSION['user_id'];
global $pdo;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$currentPlan = strtolower($user['plan'] ?? 'free');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Membership - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .pricing-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            height: 100%;
            transition: transform 0.3s, border-color 0.3s;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
        }
        .pricing-card.popular {
            border: 2px solid var(--gold);
            position: relative;
        }
        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gold);
            color: #000;
            padding: 4px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin: 20px 0;
        }
        .price span {
            font-size: 1rem;
            color: var(--text2);
            font-weight: normal;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
            text-align: left;
        }
        .feature-list li {
            margin-bottom: 15px;
            color: var(--text);
        }
        .feature-list i {
            color: var(--gold);
            margin-right: 10px;
        }
        .rzp-button-custom {
            background: linear-gradient(135deg, var(--gold), #e6a800);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 12px 0;
            font-weight: 700;
            width: 100%;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .rzp-button-custom:hover { opacity: 0.9; }
        .current-plan-btn {
            background: #1e1e2e;
            color: #888;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 12px 0;
            font-weight: 700;
            width: 100%;
            font-size: 1.1rem;
            pointer-events: none;
        }
    </style>
</head>
<body style="background: var(--bg); color: var(--text);">

<div class="sidebar">
    <div class="sidebar-logo"><img src="/assets/img/logo-icon.png" alt="BizNexus" style="height:24px; vertical-align:middle; margin-right:8px; filter: drop-shadow(0 0 5px rgba(255,215,0,0.5));">BizNexus</div>
    <nav class="nav flex-column" style="flex:1">
        <a class="nav-link" href="/dashboard/index.php">🏠 Dashboard</a>
        <a class="nav-link" href="/profile/edit.php">👤 My Profile</a>
        <a class="nav-link" href="/referrals/send.php">🤝 Referrals</a>
        <a class="nav-link" href="/dashboard/leads.php">📈 CRM</a>
        <a class="nav-link active" href="/dashboard/upgrade.php">⭐ Upgrade Plan</a>
    </nav>
</div>

<div class="main p-4">
    <div class="d-flex align-items-center mb-4">
        <h2 style="font-family: 'Syne', sans-serif; font-weight: 800; margin: 0;">Upgrade Your Business</h2>
    </div>
    <p style="color: var(--text2);">Unlock more AI leads, top-tier routing slots, and the exclusive Verified Trust Badge.</p>

    <div class="row g-4 mt-2 max-w-1000">
        
        <!-- Silver Plan -->
        <div class="col-md-4">
            <div class="pricing-card">
                <h3 style="color: #c0c0c0; font-family: 'Syne', sans-serif;">Silver</h3>
                <div class="price">₹5,000<span>/yr</span></div>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Secondary AI Lead Routing</li>
                    <li><i class="fas fa-check"></i> Basic CRM Pipeline</li>
                    <li><i class="fas fa-check"></i> 5 Lead Claims / Month</li>
                    <li><i class="fas fa-check"></i> Standard Support</li>
                </ul>
                
                <?php if ($currentPlan === 'silver'): ?>
                    <button class="current-plan-btn">Current Plan</button>
                <?php else: ?>
                    <form action="payment_process.php" method="POST">
                        <input type="hidden" name="plan" value="silver">
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="rzp_test_YOUR_KEY_HERE"
                            data-amount="500000"
                            data-currency="INR"
                            data-name="BizNexus"
                            data-description="Silver Annual Membership"
                            data-image="/assets/logo.png"
                            data-prefill.name="<?= htmlspecialchars($user['name']) ?>"
                            data-prefill.email="<?= htmlspecialchars($user['email']) ?>"
                            data-theme.color="#c0c0c0"
                            data-buttontext="Upgrade to Silver"
                        ></script>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gold Plan -->
        <div class="col-md-4">
            <div class="pricing-card popular">
                <div class="popular-badge">MOST POPULAR</div>
                <h3 style="color: var(--gold); font-family: 'Syne', sans-serif;">Gold</h3>
                <div class="price">₹15,000<span>/yr</span></div>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Priority AI Lead Routing (Slot 2)</li>
                    <li><i class="fas fa-check"></i> Verified Trust Badge ✓</li>
                    <li><i class="fas fa-check"></i> 20 Lead Claims / Month</li>
                    <li><i class="fas fa-check"></i> Smart Quotes Generator</li>
                </ul>
                
                <?php if ($currentPlan === 'gold'): ?>
                    <button class="current-plan-btn">Current Plan</button>
                <?php else: ?>
                    <form action="payment_process.php" method="POST">
                        <input type="hidden" name="plan" value="gold">
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="rzp_test_YOUR_KEY_HERE"
                            data-amount="1500000"
                            data-currency="INR"
                            data-name="BizNexus"
                            data-description="Gold Annual Membership"
                            data-prefill.name="<?= htmlspecialchars($user['name']) ?>"
                            data-prefill.email="<?= htmlspecialchars($user['email']) ?>"
                            data-theme.color="#FFD700"
                            data-buttontext="Upgrade to Gold"
                        ></script>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Platinum Plan -->
        <div class="col-md-4">
            <div class="pricing-card" style="border-color: #e5e4e2;">
                <h3 style="color: #e5e4e2; font-family: 'Syne', sans-serif;">Platinum</h3>
                <div class="price">₹50,000<span>/yr</span></div>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Ultimate Lead Routing (Slot 1)</li>
                    <li><i class="fas fa-check"></i> Unlimited Lead Claims</li>
                    <li><i class="fas fa-check"></i> Dedicated Account Manager</li>
                    <li><i class="fas fa-check"></i> Custom API Access</li>
                </ul>
                
                <?php if ($currentPlan === 'platinum'): ?>
                    <button class="current-plan-btn">Current Plan</button>
                <?php else: ?>
                    <form action="payment_process.php" method="POST">
                        <input type="hidden" name="plan" value="platinum">
                        <script
                            src="https://checkout.razorpay.com/v1/checkout.js"
                            data-key="rzp_test_YOUR_KEY_HERE"
                            data-amount="5000000"
                            data-currency="INR"
                            data-name="BizNexus"
                            data-description="Platinum Annual Membership"
                            data-prefill.name="<?= htmlspecialchars($user['name']) ?>"
                            data-prefill.email="<?= htmlspecialchars($user['email']) ?>"
                            data-theme.color="#e5e4e2"
                            data-buttontext="Upgrade to Platinum"
                        ></script>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Make Razorpay buttons match BizNexus theme -->
<script>
window.onload = function() {
    var btns = document.querySelectorAll('.razorpay-payment-button');
    btns.forEach(function(btn) {
        btn.className = 'rzp-button-custom';
    });
};
</script>
</body>
</html>
