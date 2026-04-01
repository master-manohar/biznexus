<?php
/**
 * auth/send_verification.php
 * Generates/refreshes a verification token and sends the verification email.
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/email_config.php';

$uid = $_SESSION['user_id'];

try {
    // 1. Fetch user email and current verification status
    $stmt = $pdo->prepare("SELECT email, name, email_verified, verify_token FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    if ($user['email_verified']) {
        echo json_encode(['success' => false, 'message' => 'Email is already verified.']);
        exit;
    }

    // 2. Generate or Update Token
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET verify_token = ? WHERE id = ?")->execute([$token, $uid]);

    // 3. Construct Verification Link
    // Note: We use the requested host or a default if not set.
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'biznexus.in';
    $verify_link = "{$protocol}://{$host}/auth/verify.php?token={$token}";

    // 4. Send Email
    $subject = "Verify your email - BizNexus AI Network";
    $content = "
        <h2>Verify Your Email</h2>
        <p>Hi " . htmlspecialchars($user['name']) . ",</p>
        <p>Thanks for being part of the BizNexus AI Network! To secure your account and unlock all features, please verify your email address by clicking the button below:</p>
        <p style='text-align: center;'>
            <a href='{$verify_link}' class='btn'>Verify My Email →</a>
        </p>
        <p>If the button doesn't work, copy and paste this link into your browser:</p>
        <p style='font-size: 12px; color: #888;'>{$verify_link}</p>
        <p>Verifying your email earns you <strong>+150 Security Points</strong> and <strong>50 VooCoins</strong>!</p>
    ";
    
    $body = emailTemplate("Email Verification", $content);
    
    if (sendEmail($user['email'], $subject, $body)) {
        echo json_encode(['success' => true, 'message' => 'Verification email sent successfully! Please check your inbox.']);
    } else {
        throw new Exception("Failed to send email. please try again later.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
