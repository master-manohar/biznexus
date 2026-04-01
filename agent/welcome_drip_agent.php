<?php
/**
 * agent/welcome_drip_agent.php
 * Sends a 3-part welcome email sequence to new members over 7 days.
 * Day 1: Welcome & profile tips
 * Day 3: Lead activity in their category
 * Day 7: Upgrade nudge
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;
$BCC_ADMIN = 'manohar.nch@gmail.com';
$sent = 0;

function wdLog($pdo, $taskId, $action, $detail) {
    if (!$taskId) return;
    $pdo->prepare("INSERT INTO agent_logs (task_id, agent_name, action, detail) VALUES (?, 'Welcome Drip', ?, ?)")->execute([$taskId, $action, $detail]);
}

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);

// DAY 1: New members (0-1 days old) who haven't received welcome email
$day1 = $pdo->query("
    SELECT u.id, u.name, u.email, u.created_at, bp.business_name, bp.category, bp.city
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    LEFT JOIN drip_emails de ON u.id = de.user_id AND de.drip_step = 1
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND de.id IS NULL
      AND u.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($day1 as $u) {
    $name = $u['name'] ?? 'there';
    $biz = $u['business_name'] ?? 'your business';
    $cat = $u['category'] ?? 'your industry';
    $city = $u['city'] ?? 'your city';
    $subject = "🎉 Welcome to BizNexus, $name! Here's how to get started";
    $body = emailTemplate($subject, "
        <h2>Welcome aboard, $name! 🚀</h2>
        <p>You've just joined <strong>BizNexus</strong> — India's AI-powered business networking platform.</p>
        <p><strong>3 things to do right now:</strong></p>
        <ul>
            <li>✅ Complete your business profile (get more leads)</li>
            <li>✅ Add your WhatsApp number (leads contact you directly)</li>
            <li>✅ Browse leads in <strong>$cat</strong> near <strong>$city</strong></li>
        </ul>
        <div style='text-align:center; margin:25px 0;'>
            <a href='https://biznexus.in/profile/edit.php' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>Complete Your Profile →</a>
        </div>
        <p style='color:#888; font-size:12px;'>You'll receive helpful tips from us over the next 7 days. Stay tuned!</p>
    ");
    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        $pdo->prepare("INSERT INTO drip_emails (user_id, drip_step, sent_at) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE sent_at=NOW()")->execute([$u['id']]);
        wdLog($pdo, $taskId, 'day1_sent', "Welcome email sent to {$u['email']}");
        $sent++;
    }
}

// DAY 3: Members 3 days old, not yet received step 2
$day3 = $pdo->query("
    SELECT u.id, u.name, u.email, bp.business_name, bp.category, bp.city
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    JOIN drip_emails de1 ON u.id = de1.user_id AND de1.drip_step = 1
    LEFT JOIN drip_emails de2 ON u.id = de2.user_id AND de2.drip_step = 2
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND de2.id IS NULL
      AND de1.sent_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($day3 as $u) {
    $cat = $u['category'] ?? 'your industry';
    $city = $u['city'] ?? 'your area';
    // Count leads in their category
    $leadCount = $pdo->prepare("SELECT COUNT(*) FROM public_leads WHERE category = ?")->execute([$cat]) ? 
        $pdo->query("SELECT COUNT(*) FROM public_leads WHERE category = " . $pdo->quote($cat))->fetchColumn() : 0;
    $subject = "⚡ $leadCount active leads in $cat are waiting for you";
    $body = emailTemplate($subject, "
        <h2>Hey {$u['name']}, leads are ready for you! 🎯</h2>
        <p>We've found <strong>$leadCount potential customers</strong> searching for <strong>$cat</strong> services in <strong>$city</strong>.</p>
        <p>Your BizNexus profile is live, but make sure your category and WhatsApp are set correctly so leads reach you instantly.</p>
        <div style='text-align:center; margin:25px 0;'>
            <a href='https://biznexus.in/auth/login.php' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>View Live Leads →</a>
        </div>
    ");
    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        $pdo->prepare("INSERT INTO drip_emails (user_id, drip_step, sent_at) VALUES (?, 2, NOW()) ON DUPLICATE KEY UPDATE sent_at=NOW()")->execute([$u['id']]);
        wdLog($pdo, $taskId, 'day3_sent', "Day 3 email sent to {$u['email']}");
        $sent++;
    }
}

// DAY 7: Members 7 days old on free plan, step 3 not sent
$day7 = $pdo->query("
    SELECT u.id, u.name, u.email, bp.business_name, bp.category
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    JOIN drip_emails de1 ON u.id = de1.user_id AND de1.drip_step = 1
    LEFT JOIN drip_emails de3 ON u.id = de3.user_id AND de3.drip_step = 3
    WHERE u.status = 'active' AND u.plan = 'free'
      AND u.email NOT LIKE '%@example.com'
      AND de3.id IS NULL
      AND de1.sent_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($day7 as $u) {
    $subject = "🔓 Unlock unlimited leads — Your 7-day free trial review";
    $body = emailTemplate($subject, "
        <h2>You've been with us 7 days, {$u['name']}! 🙌</h2>
        <p>You're currently on the <strong>Free Plan</strong>. Here's what you're missing:</p>
        <ul>
            <li>🔒 Unlimited lead notifications (Free: 2/day)</li>
            <li>🔒 Priority listing in search results</li>
            <li>🔒 Verified business badge</li>
            <li>🔒 Direct WhatsApp lead alerts</li>
        </ul>
        <p>Upgrade today and start winning more <strong>{$u['category']}</strong> customers.</p>
        <div style='text-align:center; margin:25px 0;'>
            <a href='https://biznexus.in/membership/upgrade.php?plan=silver&billing=monthly' style='background:#FFD700; color:#000; padding:14px 28px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block;'>Upgrade for ₹299/mo →</a>
        </div>
    ");
    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        $pdo->prepare("INSERT INTO drip_emails (user_id, drip_step, sent_at) VALUES (?, 3, NOW()) ON DUPLICATE KEY UPDATE sent_at=NOW()")->execute([$u['id']]);
        wdLog($pdo, $taskId, 'day7_sent', "Day 7 upgrade nudge sent to {$u['email']}");
        $sent++;
    }
}

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")->execute(["Drip emails sent: $sent", $taskId]);
echo "Welcome Drip Agent done. Emails sent: $sent\n";
?>
