<?php
session_start();
header('Content-Type: application/json');

// This API handles support requests from the global help widget and help page.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if (empty($subject) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Subject and message are required']);
    exit;
}

// In a real application, this would save to a `support_tickets` table
// For now, we simulate success and perhaps log it or send an admin email

// Log the request (optional)
// error_log("Support Request: User $user_id | Subject: $subject | Message: $message");

echo json_encode([
    'status' => 'success', 
    'message' => 'Your support request has been received. Our team will contact you soon.',
    'ticket_id' => 'BN-' . rand(1000, 9999)
]);
