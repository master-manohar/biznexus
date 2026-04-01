<?php
/**
 * agent/followup_agent.php
 * Re-engages members who haven't logged in for 7+ days.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;
$BCC_ADMIN = 'manohar.nch@gmail.com';
$sent = 0;

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);

// Find members inactive for 7+ days, not emailed in last 7 days
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.last_login, bp.business_name, bp.category, bp.city,
           (SELECT COUNT(*) FROM public_leads WHERE category = bp.category AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_leads
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    LEFT JOIN followup_emails fe ON u.id = fe.user_id AND fe.sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 7 DAY))
      AND fe.id IS NULL
    LIMIT 30
");
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($targets as $u) {
    $name  = $u['name'] ?? 'there';
    $cat   = $u['category'] ?? 'your industry';
    $city  = $u['city'] ?? 'your area';
    $leads = (int)$u['new_leads'];
    $lastSeen = $u['last_login'] ? date('d M', strtotime($u['last_login'])) : 'a while ago';

    $subject = "👋 $name, you have $leads new leads waiting in $cat";
    $body = emailTemplate($subject, "
        <h2>We miss you, $name! 👋</h2>
        <p>We noticed you haven't visited BizNexus since <strong>$lastSeen</strong>.</p>
        " . ($leads > 0 ? "<p>In the meantime, <strong>$leads new customers</strong> have been searching for <strong>$cat</strong> services in <strong>$city</strong>. Your competitors may have claimed these leads.</p>" : "<p>Your profile is live and customers in <strong>$city</strong> are discovering your services. Log in to respond to enquiries faster.</p>") . "
        <div style='text-align:center; margin:25px 0;'>
            <a href='https://biznexus.in/auth/login.php' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>Log In & Claim Leads →</a>
        </div>
        <p style='color:#888; font-size:12px;'>You're receiving this because you're an active BizNexus member.</p>
    ");

    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        // Track that we sent a follow-up (create table if needed)
        try {
            $pdo->prepare("INSERT INTO followup_emails (user_id, sent_at) VALUES (?, NOW())")->execute([$u['id']]);
        } catch (Exception $e) {
            // Table may not exist yet, create it
            $pdo->exec("CREATE TABLE IF NOT EXISTS followup_emails (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, sent_at DATETIME, KEY(user_id, sent_at))");
            $pdo->prepare("INSERT INTO followup_emails (user_id, sent_at) VALUES (?, NOW())")->execute([$u['id']]);
        }
        $sent++;
        echo "Sent follow-up to {$u['email']}\n";
    }
}

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")->execute(["Follow-ups sent: $sent", $taskId]);
echo "Follow-Up Agent done. Sent: $sent\n";
?>
