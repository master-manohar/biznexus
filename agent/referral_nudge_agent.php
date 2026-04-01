<?php
/**
 * agent/referral_nudge_agent.php
 * Reminds active members to share their referral link every 3 days.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;
$BCC_ADMIN = 'manohar.nch@gmail.com';
$sent = 0;

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);

// Active members not nudged in 3 days, with refer_code set
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.refer_code, u.coins,
           (SELECT COUNT(*) FROM referrals WHERE sender_id = u.id) as ref_count
    FROM users u
    LEFT JOIN referral_nudges rn ON u.id = rn.user_id AND rn.sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND u.refer_code IS NOT NULL
      AND rn.id IS NULL
    LIMIT 30
");
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($targets as $u) {
    $name    = $u['name'] ?? 'there';
    $code    = $u['refer_code'];
    $refLink = "https://biznexus.in/register_business.php?ref=$code";
    $refCount = (int)$u['ref_count'];
    $coins   = (int)$u['coins'];
    $potential = ($refCount + 5) * 50; // Show what they could earn

    $subject = "💰 Share BizNexus & Earn VooCoins — Your Link Inside";
    $body = emailTemplate($subject, "
        <h2>Hey $name, here's your referral link 🎁</h2>
        <p>Share BizNexus with your network and earn <strong>50 VooCoins</strong> for every business that joins through your link!</p>
        <div style='background:#0a0a0f; border:2px solid #FFD700; border-radius:12px; padding:16px; text-align:center; margin:20px 0;'>
            <p style='color:#FFD700; font-size:12px; margin:0 0 6px;'>YOUR REFERRAL LINK</p>
            <p style='color:#fff; font-size:14px; word-break:break-all; margin:0;'>$refLink</p>
        </div>
        <p>You've already referred <strong>$refCount businesses</strong>. You currently have <strong>$coins VooCoins</strong>.</p>
        <p>If you refer just 5 more businesses, you could earn <strong>₹" . number_format($potential) . " worth of coins</strong> this month!</p>
        <div style='text-align:center; margin:25px 0;'>
            <a href='$refLink' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>Share Your Link Now →</a>
        </div>
        <p style='color:#888; font-size:12px;'>Share on WhatsApp, Instagram, or email — every referral counts!</p>
    ");

    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        try {
            $pdo->prepare("INSERT INTO referral_nudges (user_id, sent_at) VALUES (?, NOW())")->execute([$u['id']]);
        } catch (Exception $e) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS referral_nudges (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, sent_at DATETIME, KEY(user_id, sent_at))");
            $pdo->prepare("INSERT INTO referral_nudges (user_id, sent_at) VALUES (?, NOW())")->execute([$u['id']]);
        }
        $sent++;
        echo "Referral nudge sent to {$u['email']}\n";
    }
}

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")->execute(["Referral nudges sent: $sent", $taskId]);
echo "Referral Nudge Agent done. Sent: $sent\n";
?>
