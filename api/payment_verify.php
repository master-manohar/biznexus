<?php
// /api/payment_verify.php — Razorpay payment verification & plan upgrade
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/razorpay_config.php';

header('Content-Type: application/json');
$return_json = false; // flip to true for API-only calls

$uid = (int)$_SESSION['user_id'];

$razorpay_payment_id = trim($_REQUEST['razorpay_payment_id'] ?? '');
$razorpay_order_id   = trim($_REQUEST['razorpay_order_id'] ?? '');
$razorpay_signature  = trim($_REQUEST['razorpay_signature'] ?? '');
$plan                = trim($_REQUEST['plan'] ?? '');
$billing             = trim($_REQUEST['billing'] ?? 'monthly');
$duration            = (int)($_REQUEST['duration'] ?? 30);

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$plan) {
    echo json_encode(['success' => false, 'error' => 'Missing payment data']);
    exit;
}

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

if (!hash_equals($generated_signature, $razorpay_signature)) {
    echo json_encode(['success' => false, 'error' => 'Payment signature mismatch — possible fraud']);
    exit;
}

// Valid plan?
$plans = PLAN_PRICES;
if (!isset($plans[$plan])) {
    echo json_encode(['success' => false, 'error' => 'Invalid plan']);
    exit;
}

try {
    $coupon_code = trim($_POST['coupon'] ?? '');

    // Upgrade user plan — duration based on billing cycle
    $days    = ($billing === 'yearly') ? 365 : 30;
    $expires = date('Y-m-d H:i:s', strtotime("+$days days"));
    $pdo->prepare("UPDATE users SET plan=?, plan_expires_at=?, membership=? WHERE id=?")
        ->execute([$plan, $expires, $plan, $uid]);

    // Handle Coupon usage tracking
    if ($coupon_code) {
        $pdo->prepare("UPDATE coupons SET uses = uses + 1 WHERE code = ?")->execute([$coupon_code]);
    }

    // Log the transaction
    $desc = "Upgraded to $plan plan via Razorpay. Payment: $razorpay_payment_id";
    if ($coupon_code) $desc .= " (Coupon: $coupon_code applied)";

    $pdo->prepare("INSERT INTO coin_transactions(user_id, amount, type, description, created_at)
                   VALUES(?, 0, 'plan_upgrade', ?, NOW())")
        ->execute([$uid, $desc]);

    // Award coins — higher for yearly
    $coinAward = $billing === 'yearly'
        ? ['silver'=>500,'gold'=>1500,'platinum'=>3000][$plan] ?? 0
        : ['silver'=>200,'gold'=>500, 'platinum'=>1000][$plan] ?? 0;
    if ($coinAward) {
        $pdo->prepare("INSERT INTO coin_transactions(user_id, amount, type, description, created_at) VALUES(?,?,'credit',?,NOW())")
            ->execute([$uid, $coinAward, "Bonus coins for $plan plan upgrade"]);
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")
            ->execute([$coinAward, $uid]);
    }

    // Send notification
    try {
        $pdo->prepare("INSERT INTO notifications(user_id, title, message, type, is_read, created_at)
                       VALUES(?, ?, ?, 'success', 0, NOW())")
            ->execute([$uid,
                "🎉 Plan Upgraded to " . ucfirst($plan) . "!",
                "Your BizNexus account has been upgraded to " . ucfirst($plan) . " plan. Expires: " . date('d M Y', strtotime($expires)) . ". You earned $coinAward bonus coins!"
            ]);
    } catch(Exception $e){}

    // Redirect to success page
    header('Location: /membership/payment_success.php?billing='.$billing.'&coins='.$coinAward);
    exit;

} catch (Exception $e) {
    header('Location: /membership/upgrade.php?plan='.$plan.'&err='.urlencode($e->getMessage()));
    exit;
}
