<?php
// /api/validate_coupon.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$code = trim($_POST['code'] ?? '');
$plan = trim($_POST['plan'] ?? '');
$billing = trim($_POST['billing'] ?? 'monthly');

if (!$code) {
    echo json_encode(['success' => false, 'error' => 'No coupon code provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(['success' => false, 'error' => 'Invalid or inactive coupon code']);
        exit;
    }

    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['success' => false, 'error' => 'This coupon has expired']);
        exit;
    }

    if ($coupon['max_uses'] > 0 && $coupon['uses'] >= $coupon['max_uses']) {
        echo json_encode(['success' => false, 'error' => 'This coupon reached its usage limit']);
        exit;
    }

    // Success
    echo json_encode([
        'success' => true,
        'type' => $coupon['type'],
        'value' => (float)$coupon['value'],
        'message' => 'Coupon applied successfully!'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
