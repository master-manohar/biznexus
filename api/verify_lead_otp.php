<?php
/**
 * api/verify_lead_otp.php
 * Verifies the OTP submitted by a customer before dispatching their lead.
 */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$otp   = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
    exit;
}

// Check OTP in DB
$stmt = $pdo->prepare("
    SELECT id FROM lead_otps 
    WHERE email = ? AND otp = ? AND verified = 0 AND expires_at > NOW()
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$email, $otp]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP. Please try again.']);
    exit;
}

// Mark as verified
$pdo->prepare("UPDATE lead_otps SET verified = 1 WHERE id = ?")->execute([$row['id']]);

echo json_encode(['success' => true, 'message' => 'Email verified! Your enquiry is being sent.', 'token' => md5($email . $otp . 'biznexus_lead_secure')]);
?>
