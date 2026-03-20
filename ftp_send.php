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
<?php 
$page_title = 'Send Referral - BizNexus';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between mb-4">
            <a href="/referrals/list.php" style="color:#FFD700;text-decoration:none;font-weight:700">← Back to Referrals</a>
            <h4 style="color:#FFD700;margin:0">🤝 Send a Referral</h4>
        </div>
        
        <p class="mb-4" style="color: #a0a0b0;">Help other members grow and earn <strong style="color: #FFD700">25 VooCoins</strong>.</p>

        <?php if ($successMessage): ?>
            <div class="alert alert-success" style="background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid #00ff88; border-radius: 12px;"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" style="background: rgba(255, 68, 68, 0.15); color: #ff4455; border: 1px solid rgba(255, 68, 68, 0.2); border-radius: 12px;"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <div style="background:#13131a;border:1px solid #2a2a3a;border-radius:20px;padding:32px;box-shadow: 0 10px 40px rgba(0,0,0,0.4);">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label" style="color: #888; font-size: 0.85rem;">Select Member to Refer To *</label>
                    <select name="receiver_id" class="form-select mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" required>
                        <option value="">-- Choose a Member --</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h5 class="mt-4 mb-3" style="color: var(--gold);">Referral Details</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="color: #888; font-size: 0.85rem;">Referred Person's Name *</label>
                        <input type="text" name="referred_name" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" placeholder="John Doe" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="color: #888; font-size: 0.85rem;">Referred Person's Phone *</label>
                        <input type="text" name="referred_phone" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" placeholder="e.g. 9876543210" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="color: #888; font-size: 0.85rem;">Referred Person's Email</label>
                        <input type="email" name="referred_email" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" placeholder="john@example.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="color: #888; font-size: 0.85rem;">Business Type/Category</label>
                        <input type="text" name="referred_business_type" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" placeholder="e.g. Web Development">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="color: #888; font-size: 0.85rem;">Estimated Deal Value (₹)</label>
                    <input type="number" name="estimated_value" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" placeholder="0" value="0">
                </div>

                <div class="mb-4">
                    <label class="form-label" style="color: #888; font-size: 0.85rem;">Notes for Receiver</label>
                    <textarea name="notes" class="form-control" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" rows="3" placeholder="Explain what the prospect is looking for..."></textarea>
                </div>

                <button type="submit" class="btn btn-warning w-100 fw-bold" style="padding: 12px; border-radius: 10px;">Send Referral & Earn 25 Coins</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
