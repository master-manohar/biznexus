<?php
/**
 * api/send_lead_otp.php
 * Sends a 6-digit OTP to a customer's email before their lead is dispatched.
 * Called via AJAX from the public lead form.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Rate limit: max 3 OTPs per email per hour
$recent = $pdo->prepare("SELECT COUNT(*) FROM lead_otps WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$recent->execute([$email]);
if ($recent->fetchColumn() >= 3) {
    echo json_encode(['success' => false, 'message' => 'Too many OTP requests. Please wait 1 hour.']);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in DB
try {
    $pdo->prepare("CREATE TABLE IF NOT EXISTS lead_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255),
        phone VARCHAR(20),
        otp VARCHAR(6),
        verified TINYINT(1) DEFAULT 0,
        created_at DATETIME,
        expires_at DATETIME,
        KEY(email, otp)
    )")->execute();
} catch (Exception $e) {}

$pdo->prepare("INSERT INTO lead_otps (email, phone, otp, verified, created_at, expires_at) VALUES (?, ?, ?, 0, NOW(), ?)")
    ->execute([$email, $phone, $otp, $expiresAt]);

// Send OTP email
$subject = "🔐 Your BizNexus Verification Code: $otp";
$body = emailTemplate($subject, "
    <h2 style='color:#FFD700;'>Your Verification Code</h2>
    <p>Use this code to verify your enquiry on BizNexus:</p>
    <div style='text-align:center; margin:30px 0;'>
        <span style='font-size:2.5rem; font-weight:900; letter-spacing:12px; color:#FFD700; background:#0a0a0f; padding:16px 32px; border-radius:12px; border:2px solid #FFD700;'>$otp</span>
    </div>
    <p style='color:#888;'>This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>
    <p style='color:#555; font-size:12px;'>If you did not request this, please ignore this email.</p>
");

if (sendEmail($email, $subject, $body)) {
    echo json_encode(['success' => true, 'message' => "OTP sent to $email. Check your inbox."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
}
?>
