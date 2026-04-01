<?php
/**
 * agent/review_collector_agent.php
 * Asks members who've been on the platform 30+ days for a testimonial.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;
$BCC_ADMIN = 'manohar.nch@gmail.com';
$sent = 0;

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);

// Members 30+ days old, never asked for review, not on free plan (paid members more likely to respond)
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, bp.business_name, bp.category, bp.city, u.plan
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    LEFT JOIN review_requests rr ON u.id = rr.user_id
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND u.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND rr.id IS NULL
    LIMIT 20
");
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($targets as $u) {
    $name = $u['name'] ?? 'there';
    $biz  = $u['business_name'] ?? 'your business';
    $cat  = $u['category'] ?? 'your sector';
    // Unique review token
    $token = md5($u['id'] . 'biznexus_review' . date('Y-m'));
    $reviewLink = "https://biznexus.in/review/submit.php?token=$token&uid={$u['id']}";

    $subject = "⭐ Share your BizNexus experience, $name!";
    $body = emailTemplate($subject, "
        <h2>You've been with BizNexus for 30 days 🎉</h2>
        <p>Hi $name, <strong>$biz</strong> has been part of our network for a month now!</p>
        <p>We'd love to hear how BizNexus has helped your <strong>$cat</strong> business grow. Your feedback helps us improve and helps other businesses trust us.</p>
        <p>It takes just <strong>60 seconds</strong> to share your experience.</p>
        <div style='text-align:center; margin:25px 0;'>
            <a href='$reviewLink' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>⭐ Leave a Review →</a>
        </div>
        <p>As a thank-you, you'll receive <strong>+100 VooCoins</strong> after submitting your review!</p>
        <p style='color:#888; font-size:12px;'>This is a one-time request. We respect your time.</p>
    ");

    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        try {
            $pdo->prepare("INSERT INTO review_requests (user_id, token, sent_at) VALUES (?, ?, NOW())")->execute([$u['id'], $token]);
        } catch (Exception $e) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS review_requests (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, token VARCHAR(64), sent_at DATETIME, KEY(user_id))");
            $pdo->prepare("INSERT INTO review_requests (user_id, token, sent_at) VALUES (?, ?, NOW())")->execute([$u['id'], $token]);
        }
        $sent++;
        echo "Review request sent to {$u['email']}\n";
    }
}

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")->execute(["Review requests sent: $sent", $taskId]);
echo "Review Collector Agent done. Sent: $sent\n";
?>
