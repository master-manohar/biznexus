<?php
require_once __DIR__ . '/includes/db.php';

try {
    $code = 'H2H50';
    $stmt = $pdo->prepare("INSERT IGNORE INTO coupons (code, type, value, max_uses, expires_at, status) VALUES (?, 'percentage', 50, 0, DATE_ADD(NOW(), INTERVAL 7 DAY), 'active')");
    $stmt->execute([$code]);
    echo "Coupon '$code' (50% Off) created successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
