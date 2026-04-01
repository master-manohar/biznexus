<?php
// includes/visitor_logger.php
// Passive tracking for anonymous users

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$url = $_SERVER['REQUEST_URI'] ?? '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Skip search engines & admins
if (stripos($ua, 'bot') !== false || stripos($ua, 'spider') !== false) return;
if (isset($_SESSION['user_id']) && $url == '/admin/') return; 

try {
    // Check if this IP has visited this URL today
    $stmt = $pdo->prepare("SELECT id, visit_count FROM visitor_logs WHERE ip_address = ? AND page_url = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$ip, $url]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE visitor_logs SET visit_count = visit_count + 1 WHERE id = ?")->execute([$existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO visitor_logs (ip_address, page_url, referrer, user_agent) VALUES (?, ?, ?, ?)")
            ->execute([$ip, $url, $ref, $ua]);
    }
} catch (Exception $e) {
    // Fail silently to not break page load
}
