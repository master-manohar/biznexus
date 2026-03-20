<?php
// /referrals/send.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$uid = (int)$_SESSION['user_id'];
$uname = $_SESSION['name'] ?? 'Member';

global $pdo;
if (!$pdo) {
    die("Database connection failed. Please contact administrator.");
}

// Fetch member list for the "Receiver ID" dropdown (exclude self)
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id != ? AND status = 'active' ORDER BY name ASC");
$stmt->execute([$uid]);
$members = $stmt->fetchAll();

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = (int)$_POST['receiver_id'];
    $referred_name = trim($_POST['referred_name']);
    $referred_phone = trim($_POST['referred_phone']);
    $referred_email = trim($_POST['referred_email']);
    $referred_business_type = trim($_POST['referred_business_type']);
    $notes = trim($_POST['notes']);
    $estimated_value = (int)$_POST['estimated_value'];

    if (empty($receiver_id) || empty($referred_name) || empty($referred_phone)) {
        $errorMessage = "Receiver, Referred Name, and Referred Phone are required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO referrals (sender_id, receiver_id, referred_name, phone, email, referred_business_type, notes, estimated_value, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
            $stmt->execute([
                $uid,
                $receiver_id,
                $referred_name,
                $referred_phone,
                $referred_email,
                $referred_business_type,
                $notes,
                $estimated_value
            ]);

            // Award 25 VooCoins for sending a referral
            awardCoins($pdo, $uid, 25, "Sent a new referral to member #$receiver_id");
            
            // Output notification to receiver app
            $senderName = $uname;
            sendNotification($pdo, $receiver_id, "New Referral Received", "$senderName has sent you a new business referral!", 'referral');

            // Send Email if config is available
            if (file_exists('../includes/email_config.php')) {
                require_once '../includes/email_config.php';
                if (file_exists('../includes/emails/referral.php')) {
                    require_once '../includes/emails/referral.php';
                    
                    // fetch receiver email and name
                    $rcvStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                    $rcvStmt->execute([$receiver_id]);
                    $rcv = $rcvStmt->fetch();
                    
                    if ($rcv && function_exists('sendReferralEmail')) {
                        sendReferralEmail(
                            $rcv['email'], 
                            $rcv['name'], 
                            $senderName, 
                            $referred_name, 
                            $referred_business_type, 
                            $estimated_value
                        );
                    }
                }
            }

            $successMessage = "Referral sent successfully! You earned 25 VooCoins.";
        } catch (Exception $e) {
            $errorMessage = "Failed to send referral: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Send Referral - BizNexus</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap' rel='stylesheet'>
    <link href='/assets/css/biznexus.css' rel='stylesheet'>
    <style>
        .form-control, .form-select {
            background: var(--bg);
            border: 1.5px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            padding: 13px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            background: var(--bg);
            color: var(--text);
            box-shadow: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--gold), #e6a800);
            color: #000;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            padding: 12px 24px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }
    </style>
</head>
<body>

<div class='sidebar'>
    <div class='sidebar-logo'>⚡ BizNexus</div>
    <nav class='nav flex-column' style='flex:1'>
        <a class='nav-link' href='/dashboard/index.php'>🏠 Dashboard</a>
        <a class='nav-link' href='/profile/edit.php'>👤 My Profile</a>
        <a class='nav-link active' href='/referrals/send.php'>🤝 Referrals</a>
        <a class='nav-link' href='/meetings/book.php'>📅 Meetings</a>
        <a class='nav-link' href='/marketplace/index.php'>🏪 Marketplace</a>
        <a class='nav-link' href='/crm/index.php'>📊 CRM</a>
        <a class='nav-link' href='/invoices/create.php'>🧾 Invoices</a>
        <a class='nav-link' href='/coins/balance.php'>🪙 VooCoins</a>
        <a class='nav-link' href='/community/index.php'>💬 Community</a>
        <a class='nav-link' href='/advisor/index.php'>🤖 AI Advisor</a>
        <a class='nav-link' href='/notifications/index.php'>🔔 Notifications</a>
    </nav>
    <a href='/auth/logout.php' style='color:#ff4455;padding:16px 20px; text-decoration: none;'>🚪 Logout</a>
</div>

<div class='main p-4'>
    <h2 class="mb-4" style="font-family: 'Syne', sans-serif; font-weight: 800;">Send a Referral</h2>
    <p class="mb-4" style="color: var(--text2);">Help other members grow and earn <strong style="color: var(--gold)">25 VooCoins</strong> for yourself.</p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success" style="background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid #00ff88;"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger" style="background: rgba(255, 68, 68, 0.15); color: #ff4455; border: 1px solid rgba(255, 68, 68, 0.2);"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px;">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label" style="color: var(--text2);">Select Member to Refer To *</label>
                <select name="receiver_id" class="form-select" required>
                    <option value="">-- Choose a Member --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h5 class="mt-4 mb-3" style="color: var(--gold);">Referral Details</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Referred Person's Name *</label>
                    <input type="text" name="referred_name" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Referred Person's Phone *</label>
                    <input type="text" name="referred_phone" class="form-control" placeholder="e.g. 9876543210" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Referred Person's Email</label>
                    <input type="email" name="referred_email" class="form-control" placeholder="john@example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Business Type/Category</label>
                    <input type="text" name="referred_business_type" class="form-control" placeholder="e.g. Web Development">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" style="color: var(--text2);">Estimated Deal Value (₹)</label>
                <input type="number" name="estimated_value" class="form-control" placeholder="0" value="0">
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: var(--text2);">Notes for Receiver</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Explain what the prospect is looking for..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Send Referral & Earn 25 Coins</button>
        </form>
    </div>
</div>

</body>
</html>
