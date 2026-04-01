<?php
/**
 * /api/website_contact.php
 * Handles contact form submissions from AI-generated mini-sites.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

$biz_id = $_POST['business_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$msg = trim($_POST['message'] ?? '');

if (!$biz_id || !$name || (!$email && !$phone)) {
    echo json_encode(['success' => false, 'error' => 'Incomplete data.']);
    exit;
}

// 1. Store as a public lead
try {
    $stmt = $pdo->prepare("INSERT INTO public_leads (business_id, name, email, phone, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
    $stmt->execute([$biz_id, $name, $email, $phone, $msg]);
    
    // 2. Potentially notify business owner (optional)
    
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
