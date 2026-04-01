<?php
// /api/create_payment_order.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/razorpay_config.php';

header('Content-Type: application/json');

$uid = (int)$_SESSION['user_id'];
$plan = trim($_POST['plan'] ?? '');
$billing = trim($_POST['billing'] ?? 'monthly');
$coupon_code = trim($_POST['coupon'] ?? '');

if (!isset(PLAN_PRICES[$plan])) {
    echo json_encode(['success' => false, 'error' => 'Invalid plan']);
    exit;
}

$plan_data = PLAN_PRICES[$plan];
$original_amount = ($billing === 'yearly') ? $plan_data['yearly_amount'] : $plan_data['monthly_amount'];
$final_amount = $original_amount;

// Apply Coupon if exists
if ($coupon_code) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {
            // Validate Expiry & Uses
            if (!($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) && 
                !($coupon['max_uses'] > 0 && $coupon['uses'] >= $coupon['max_uses'])) {
                
                $discount = 0;
                if ($coupon['type'] === 'percentage') {
                    $discount = round($original_amount * ($coupon['value'] / 100));
                } else {
                    $discount = $coupon['value'] * 100;
                }
                $final_amount = $original_amount - $discount;
            }
        }
    } catch (Exception $e) {}
}

// Minimum ₹1.00 for Razorpay
if ($final_amount < 100) $final_amount = 100;

// Create Razorpay order
try {
    $payload = json_encode([
        'amount'   => (int)$final_amount,
        'currency' => RAZORPAY_CURRENCY,
        'receipt'  => 'bnx_'.$uid.'_'.time(),
        'notes'    => ['user_id'=>$uid,'plan'=>$plan,'billing'=>$billing,'coupon'=>$coupon_code]
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

    if ($code !== 200 || empty($order['id'])) {
        echo json_encode(['success' => false, 'error' => $order['error']['description'] ?? 'Order creation failed']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $order['amount'],
        'currency' => $order['currency']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
