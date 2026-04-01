<?php
// api/capture_public_lead.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['whatsapp'] ?? '';
    $cat = $_POST['category'] ?? 'General';
    $city = $_POST['city'] ?? 'India';
    $url = $_POST['url'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!empty($name) && !empty($phone)) {
        $stmt = $pdo->prepare("INSERT INTO public_leads (name, phone, category, city, source_url, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $cat, $city, $url, $ip]);

        session_start();
        $_SESSION['lead_submitted'] = true;

        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Missing data']);
