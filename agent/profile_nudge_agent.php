<?php
/**
 * agent/profile_nudge_agent.php
 * Sends follow-up emails to members who have NOT completed their business profile.
 * Targets: Missing business_name OR category OR phone OR city OR description.
 * Triggers: Called by runner.php via task_type = 'profile_nudge'
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$taskId  = $_GET['task_id'] ?? $argv[1] ?? null;
$BCC_ADMIN = 'manohar.nch@gmail.com';
$sent    = 0;
$skipped = 0;

if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);

// Create tracking table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile_nudge_emails (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sent_at DATETIME NOT NULL,
        KEY(user_id, sent_at)
    )");
} catch (Exception $e) {}

// Find members with incomplete profiles — NOT emailed in last 3 days
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email,
           bp.business_name, bp.category, bp.city, bp.phone, bp.description,
           u.created_at
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    LEFT JOIN profile_nudge_emails pne ON u.id = pne.user_id AND pne.sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND u.role IN ('member', 'user')
      AND pne.id IS NULL
      AND (
          bp.business_name IS NULL OR bp.business_name = ''
          OR bp.category    IS NULL OR bp.category    = ''
          OR bp.phone       IS NULL OR bp.phone       = ''
          OR bp.city        IS NULL OR bp.city        = ''
          OR bp.description IS NULL OR LENGTH(bp.description) < 30
      )
    ORDER BY u.created_at DESC
    LIMIT 40
");
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($targets as $u) {
    $name     = $u['name'] ?: 'there';
    $bizName  = $u['business_name'] ?: '';
    $category = $u['category'] ?: '';
    $city     = $u['city'] ?: '';

    // Identify exactly what is missing for a personalised nudge
    $missing = [];
    if (empty($u['business_name']))                          $missing[] = 'Business Name';
    if (empty($u['category']))                               $missing[] = 'Business Category';
    if (empty($u['phone']))                                  $missing[] = 'Phone / WhatsApp';
    if (empty($u['city']))                                   $missing[] = 'City / Location';
    if (empty($u['description']) || strlen($u['description']) < 30) $missing[] = 'Business Description';

    if (empty($missing)) { $skipped++; continue; }

    $missingList = implode(', ', $missing);
    $completedPercent = (int)(((5 - count($missing)) / 5) * 100);

    $subject = "⚠️ $name, your BizNexus profile is $completedPercent% complete — finish it to get leads!";

    $missingItems = '';
    foreach ($missing as $item) {
        $missingItems .= "<li style='color:#fff; margin-bottom:6px;'>❌ <strong>$item</strong> — missing</li>";
    }

    $body = emailTemplate($subject, "
        <h2 style='color:#FFD700;'>Your Profile Needs Attention, $name! ⚠️</h2>
        <p>Your BizNexus profile is only <strong style='color:#FFD700; font-size:1.3rem;'>$completedPercent% complete</strong>.</p>
        <p>An incomplete profile means:</p>
        <ul style='color:#ccc; margin:10px 0 20px 20px;'>
            <li>You appear lower in search results</li>
            <li>Customers can't find you easily</li>
            <li>You miss AI-matched leads every day</li>
        </ul>
        <p><strong style='color:#FFD700;'>Missing Details:</strong></p>
        <ul style='margin:10px 0 20px 20px; list-style:none; padding:0;'>
            $missingItems
        </ul>
        <p>It takes less than <strong>2 minutes</strong> to complete your profile and start receiving verified business leads!</p>
        <div style='text-align:center; margin:25px 0;'>
            <a href='https://biznexus.in/profile/edit.php' style='background:#FFD700; color:#000; padding:16px 32px; text-decoration:none; font-weight:bold; border-radius:8px; display:inline-block; font-size:1rem;'>Complete My Profile Now →</a>
        </div>
        <p style='color:#888; font-size:12px;'>You are receiving this because your BizNexus profile is incomplete. <a href='https://biznexus.in' style='color:#FFD700;'>BizNexus</a> — India's AI Business Network.</p>
    ", "Complete My Profile Now →", "https://biznexus.in/profile/edit.php");

    if (sendEmail($u['email'], $subject, $body, $BCC_ADMIN)) {
        $pdo->prepare("INSERT INTO profile_nudge_emails (user_id, sent_at) VALUES (?, NOW())")->execute([$u['id']]);
        $sent++;
        echo "Profile nudge sent to {$u['email']} (missing: $missingList)\n";
    } else {
        echo "FAILED to send to {$u['email']}\n";
    }
}

$result = "Profile nudge emails sent: $sent. Skipped (complete): $skipped.";
if ($taskId) $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")->execute([$result, $taskId]);
echo "Profile Nudge Agent done. $result\n";
?>
