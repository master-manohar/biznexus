<?php
// /dashboard/leads.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/email_config.php';

$uid = (int)$_SESSION['user_id'];
global $pdo;

// Fetch member info
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$uid]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userEmail = $user['email'];
$userName = $user['business_name'] ?: $user['name'];
$isVerified = $user['is_verified'] ?? 0;
$verifiedBadgeHtml = $isVerified ? "<span style='background:#FFD700;color:#000;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:bold;margin-left:10px;'>BizNexus Verified ✓</span>" : "";

// Handle CRM Actions
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = (int)$_POST['lead_id'];
    
    // Fetch Lead Details
    $lStmt = $pdo->prepare("SELECT * FROM public_leads WHERE id = ?");
    $lStmt->execute([$lead_id]);
    $lead = $lStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) die("Lead not found.");

    if (isset($_POST['send_email'])) {
        $custom_msg = htmlspecialchars($_POST['email_message']);
        $html = "
            <h3 style='color:#333;'>Message from $userName $verifiedBadgeHtml</h3>
            <p style='color:#555; font-size:15px; background:#f4f4f4; padding:15px; border-left:4px solid #FFD700;'>" . nl2br($custom_msg) . "</p>
            <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
            <p style='color:#888; font-size:12px;'><em>Reply directly to this email to contact the business. Handled via the BizNexus B2B Pipeline.</em></p>
        ";
        $subject = "BizNexus: $userName has replied to your requirement";
        // Send email with member's email as Reply-To
        if(sendEmail($lead['email'], $subject, $html, '', $userEmail)) {
            $msg = "<div class='alert text-success' style='border:1px solid #00ff88;'>Email sent directly to {$lead['name']}!</div>";
        } else {
            $msg = "<div class='alert text-danger' style='border:1px solid #ff4455;'>Failed to send email.</div>";
        }
    } elseif (isset($_POST['send_quote'])) {
        $amount = (int)$_POST['quote_amount'];
        $details = htmlspecialchars($_POST['quote_details']);
        $html = "
            <div style='background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #ddd; max-width:600px; margin:auto; font-family:sans-serif;'>
                <h2 style='color:#FFD700; margin-bottom:5px;'>Official Quote $verifiedBadgeHtml</h2>
                <h4 style='color:#333; margin-top:0;'>From: $userName</h4>
                <p>Hello {$lead['name']}, based on your requirement (<i>{$lead['query']}</i>), we are pleased to offer you the following quote:</p>
                <div style='background:#fff; padding:15px; border:1px solid #eee; margin-top:20px; border-left:4px solid #FFD700;'>
                    <p style='font-size:18px; color:#111;'><strong>Quote Amount:</strong> ₹" . number_format($amount) . "</p>
                    <p style='color:#555;'><strong>Details:</strong><br>" . nl2br($details) . "</p>
                </div>
                <p style='margin-top:30px; font-size:13px; color:#666;'>Please reply directly to this email to accept or negotiate this quote.</p>
            </div>
        ";
        $subject = "BizNexus Quote: ₹" . number_format($amount) . " from $userName";
        if(sendEmail($lead['email'], $subject, $html, '', $userEmail)) {
            $msg = "<div class='alert text-success' style='border:1px solid #00ff88;'>Smart Quote successfully emailed to {$lead['name']}!</div>";
        } else {
            $msg = "<div class='alert text-danger' style='border:1px solid #ff4455;'>Failed to send quote.</div>";
        }
    } elseif (isset($_POST['claim_lead'])) {
        // Enforce membership limits based on plan
        $plan = $user['plan'] ?? 'free';
        $limits = ['free' => 3, 'silver' => 40, 'gold' => 80, 'platinum' => 9999];
        $limit = $limits[$plan] ?? 3;

        // Count claims this month
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM lead_dispatches WHERE member_id = ? AND status IN ('claimed', 'contacted') AND notified_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmtCount->execute([$uid]);
        $currentMonthClaims = (int)$stmtCount->fetchColumn();

        if ($currentMonthClaims >= $limit) {
            $msg = "<div class='alert text-warning' style='border:1px solid #FFD700; background:rgba(255,215,0,0.05);'>
                        <strong>Monthly Limit Reached:</strong> You have used your $plan plan's limit of $limit lead claims. 
                        <a href='/membership/upgrade.php' class='btn btn-gold btn-sm ms-3'>Upgrade to Unlock More</a>
                    </div>";
        } else {
            // Handle Lead Locking 3-Claim Max
            $claimedCount = (int)($lead['claimed_count'] ?? 0);
            if ($claimedCount >= 3) {
                $msg = "<div class='alert text-danger' style='border:1px solid #ff4455;'>Sorry, this lead has already been claimed by 3 members and is now locked permanently.</div>";
            } else {
                // Check if already claimed
                $checkStmt = $pdo->prepare("SELECT status FROM lead_dispatches WHERE lead_id = ? AND member_id = ?");
                $checkStmt->execute([$lead_id, $uid]);
                $ldStatus = $checkStmt->fetchColumn();
                
                if ($ldStatus === 'claimed' || $ldStatus === 'contacted') {
                    $msg = "<div class='alert text-info'>You have already claimed this lead.</div>";
                } else {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("UPDATE lead_dispatches SET status = 'claimed' WHERE lead_id = ? AND member_id = ?")->execute([$lead_id, $uid]);
                        $newCount = $claimedCount + 1;
                        if ($newCount >= 3) {
                            $pdo->prepare("UPDATE public_leads SET claimed_count = ?, locked_at = NOW(), status = 'locked' WHERE id = ?")->execute([$newCount, $lead_id]);
                        } else {
                            $pdo->prepare("UPDATE public_leads SET claimed_count = ?, status = 'claimed' WHERE id = ?")->execute([$newCount, $lead_id]);
                        }
                        $pdo->commit();
                        $msg = "<div class='alert text-success' style='border:1px solid #00ff88;'>Lead Details Unlocked Successfully! ($currentMonthClaims/$limit used)</div>";
                    } catch(Exception $e) {
                        $pdo->rollBack();
                        $msg = "<div class='alert text-danger'>Database Error claiming lead.</div>";
                    }
                }
            }
        }
    }
}

// Fetch Dispatched Leads for this user
$stmtLeads = $pdo->prepare("
    SELECT ld.*, pl.name as lead_name, pl.phone as lead_phone, pl.email as lead_email, pl.query, 
           pl.category as lead_category, pl.city as lead_city, pl.created_at as lead_date, 
           pl.claimed_count, pl.locked_at
    FROM lead_dispatches ld
    JOIN public_leads pl ON ld.lead_id = pl.id
    WHERE ld.member_id = ?
    ORDER BY ld.notified_at DESC
");
$stmtLeads->execute([$uid]);
$leads = $stmtLeads->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2B CRM Pipeline - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <style>
        .crm-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px; }
        .crm-header { background: rgba(255, 215, 0, 0.05); padding: 15px 20px; border-bottom: 1px solid var(--border); border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .crm-body { padding: 20px; }
        .lead-detail { font-size: 0.9rem; color: var(--text2); margin-bottom: 5px; }
        .btn-wa { background: #25D366; color: #fff; border: none; font-weight: 600; }
        .btn-wa:hover { background: #128C7E; color: #fff; }
        .btn-gold { background: linear-gradient(135deg, #FFD700, #ff8c00); color: #000; font-weight: 600; border: none; }
        .modal-content { background: var(--card2); color: var(--text); border: 1px solid var(--border); }
        .modal-header { border-bottom: 1px solid var(--border); }
        .modal-footer { border-top: 1px solid var(--border); }
        .form-control { background: var(--bg); color: #fff; border: 1px solid var(--border); }
        .form-control:focus { background: var(--bg); color: #fff; border-color: var(--gold); box-shadow: none; }
    </style>
</head>
<body style="background: var(--bg); color: var(--text);">

<div class="container py-5">
    <div class="d-flex align-items-center mb-4">
        <a href="/dashboard/" class="btn btn-outline-light me-3">← Back</a>
        <h2 style="font-family: 'Syne', sans-serif; font-weight: 800; margin: 0;">CRM Pipeline</h2>
    </div>

    <?= $msg ?>

    <?php if(count($leads) === 0): ?>
        <div class="card p-5 text-center crm-card">
            <h4 style="color: var(--text2);">Your pipeline is empty</h4>
            <p>Wait for the AI Engine to match you with new buyers, or ensure your profile categories are accurate.</p>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach($leads as $idx => $l): ?>
            <div class="col-md-6">
                <div class="crm-card">
                    <div class="crm-header">
                        <h5 style="margin:0; color:var(--gold); font-family:'Syne',sans-serif;"><?= htmlspecialchars($l['lead_name']) ?></h5>
                        <span class="badge" style="background:rgba(0,255,136,0.1); color:#00ff88;">New Lead</span>
                    </div>
                    <div class="crm-body">
                        <p class="lead-detail"><i class="fas fa-tag"></i> <strong>Requirement:</strong> <?= htmlspecialchars($l['query']) ?></p>
                        <p class="lead-detail"><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?= htmlspecialchars($l['lead_city']) ?></p>
                        <p class="lead-detail"><i class="fas fa-clock"></i> <strong>Received:</strong> <?= date('d M, Y h:i A', strtotime($l['notified_at'])) ?></p>
                        
                        <?php 
                            $isClaimed = ($l['status'] === 'claimed' || $l['status'] === 'contacted'); 
                            $isLocked = ((int)$l['claimed_count'] >= 3);
                        ?>
                        
                        <?php if ($isClaimed): ?>
                            <div class="mt-3 p-3" style="background: var(--bg); border-radius: 8px; border: 1px solid #00ff88;">
                                <p class="mb-1 text-success"><i class="fas fa-check-circle"></i> Profile Unlocked</p>
                                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($l['lead_phone']) ?></p>
                                <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($l['lead_email'] ?: 'Not provided') ?></p>
                            </div>

                            <div class="d-flex gap-2 mt-4 flex-wrap">
                                <!-- WhatsApp Launcher -->
                                <?php 
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $l['lead_phone']);
                                    if(strlen($cleanPhone) == 10) $cleanPhone = "91" . $cleanPhone;
                                    $waText = urlencode("Hello " . $l['lead_name'] . ", I am connecting with you from BizNexus regarding your requirement: " . $l['query']);
                                ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waText ?>" target="_blank" class="btn btn-wa flex-fill text-center">
                                    Chat on WhatsApp
                                </a>
                                
                                <?php if(!empty($l['lead_email'])): ?>
                                    <!-- Email Lead Modal Trigger -->
                                    <button type="button" class="btn btn-outline-light flex-fill" data-bs-toggle="modal" data-bs-target="#emailModal<?= $l['id'] ?>">
                                        Send Email
                                    </button>
                                    
                                    <!-- Quote Modal Trigger -->
                                    <button type="button" class="btn btn-gold flex-fill" data-bs-toggle="modal" data-bs-target="#quoteModal<?= $l['id'] ?>">
                                        Create Quote
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($isLocked): ?>
                            <div class="mt-3 p-3" style="background: var(--bg); border-radius: 8px; border: 1px solid #ff4455;">
                                <p class="text-danger mb-0"><i class="fas fa-lock"></i> Lead Locked (Max 3 claims reached)</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-3 p-3" style="background: var(--bg); border-radius: 8px; border: 1px solid var(--border);">
                                <p class="mb-1 text-muted"><strong>Phone:</strong> +91 XXXXX XXXXX</p>
                                <p class="mb-0 text-muted"><strong>Email:</strong> xxxx@xxxx.com</p>
                            </div>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="lead_id" value="<?= $l['lead_id'] ?>">
                                <button type="submit" name="claim_lead" class="btn btn-gold w-100"><i class="fas fa-key"></i> Unlock Contact Details</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($l['lead_email'])): ?>
                <!-- Email Modal -->
                <div class="modal fade" id="emailModal<?= $l['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Email <?= htmlspecialchars($l['lead_name']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="lead_id" value="<?= $l['lead_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea name="email_message" class="form-control" rows="4" required placeholder="Hello! We can fulfill your requirement..."></textarea>
                                        <small class="text-muted mt-1 d-block">Replies will go straight to your email (<?= $userEmail ?>).</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="send_email" class="btn btn-gold">Send via BizNexus API</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quote Modal -->
                <div class="modal fade" id="quoteModal<?= $l['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Generate Smart Quote</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="lead_id" value="<?= $l['lead_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Total Amount (₹)</label>
                                        <input type="number" name="quote_amount" class="form-control" required placeholder="e.g. 15000">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quote Details / Breakdown</label>
                                        <textarea name="quote_details" class="form-control" rows="3" required placeholder="Item 1: 5000\nItem 2: 10000"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="send_quote" class="btn btn-gold">Generate & Send Pitch</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
