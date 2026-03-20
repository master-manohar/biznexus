<?php
// /dashboard/kyc_upload.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$uid = (int)$_SESSION['user_id'];
$msg = '';

$stmt = $pdo->prepare("SELECT kyc_status, trust_score, trust_badge, is_verified FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['kyc_doc']) && $_FILES['kyc_doc']['size'] > 0) {
        $docName = basename($_FILES['kyc_doc']['name']);
        // Simulating upload for phase 3, just mark as verified instantly for the simulation
        $pdo->prepare("UPDATE users SET kyc_status = 'verified', is_verified = 1 WHERE id = ?")->execute([$uid]);
        $user['kyc_status'] = 'verified';
        $user['is_verified'] = 1;
        
        // Trigger Trust Engine calculation immediately for this user via a background exec or just inline for the demo
        $msg = "<div class='alert text-success' style='border:1px solid #00ff88;'><i class='fas fa-check-circle'></i> Document $docName uploaded and verified successfully! Your Trust Score will naturally adjust during the next cron cycle.</div>";
    } else {
        $msg = "<div class='alert text-danger' style='border:1px solid #ff4455;'>Please select a valid document to upload.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .trust-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 30px; margin-bottom: 20px;}
        .kyc-status-pending { color: #f39c12; font-weight: bold; }
        .kyc-status-verified { color: #00ff88; font-weight: bold; }
        .kyc-status-none { color: #ff4455; font-weight: bold; }
        .score-circle { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 20px; background: rgba(255, 215, 0, 0.1); color: var(--gold); border: 2px solid var(--gold); }
    </style>
</head>
<body style="background: var(--bg); color: var(--text);">

<div class="sidebar d-none d-md-flex">
    <div class="sidebar-logo">⚡ BizNexus</div>
    <nav class="nav flex-column" style="flex:1">
        <a class="nav-link" href="/dashboard/index.php">🏠 Dashboard</a>
        <a class="nav-link" href="/profile/edit.php">👤 My Profile</a>
        <a class="nav-link" href="/referrals/send.php">🤝 Referrals</a>
        <a class="nav-link" href="/dashboard/leads.php">📈 CRM</a>
        <a class="nav-link active" href="/dashboard/kyc_upload.php">🛡️ Trust & Safety</a>
    </nav>
</div>

<div class="main p-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <h2 style="font-family: 'Syne', sans-serif; font-weight: 800; margin-bottom: 20px;">Trust & Safety Center</h2>
            
            <div class="trust-card text-center mb-4">
                <div class="score-circle">
                    <?= (int)$user['trust_score'] ?>
                </div>
                <h4>Trust Score</h4>
                <p style="color:var(--text2);">Earn above 80 points to unlock the <strong>Diamond Trust Badge</strong>.</p>
                
                <?php if($user['trust_badge']): ?>
                    <span class="badge px-3 py-2" style="background:linear-gradient(45deg,#0052D4,#4364F7); font-size:14px;">
                        <i class="fas fa-shield-alt me-1"></i> Tier: <?= htmlspecialchars(ucwords($user['trust_badge'])) ?> Trusted
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary px-3 py-2">No Trust Badge</span>
                <?php endif; ?>
            </div>

            <div class="trust-card">
                <h4>Upload KYC Documents</h4>
                <p style="color:var(--text2);">Upload your GST Certificate or Aadhar Card (for freelancers) to instantly gain +25 Trust Score Points and unlock the "Verified User" label.</p>
                
                <p>Status: <span class="kyc-status-<?= strtolower($user['kyc_status']) ?>"><i class="fas fa-info-circle"></i> <?= strtoupper($user['kyc_status'] ?? 'NONE') ?></span></p>

                <?= $msg ?>

                <?php if (!in_array($user['kyc_status'], ['verified', 'pending'])): ?>
                <form method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Document Type</label>
                        <select name="doc_type" class="form-select" style="background:var(--bg);color:#fff;border-color:var(--border);">
                            <option value="gst">GST Registration Certificate</option>
                            <option value="aadhar">Aadhar Card</option>
                            <option value="pan">Company PAN Card</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-weight:600;">Upload File (PDF/Image)</label>
                        <input type="file" name="kyc_doc" accept=".pdf,image/*" required class="form-control" style="background:var(--bg);color:#fff;border-color:var(--border);">
                    </div>
                    <button type="submit" class="btn" style="background:var(--gold);color:#000;font-weight:bold; width:100%;">Securely Submit for Verification</button>
                </form>
                <?php elseif ($user['kyc_status'] === 'pending'): ?>
                    <div class="alert mt-3" style="background:rgba(243,156,18,0.1); border:1px solid #f39c12; color:#f39c12;">Your documents are under manual review by the BizNexus Trust & Safety team. This usually takes 24 hours.</div>
                <?php elseif ($user['kyc_status'] === 'verified'): ?>
                    <div class="alert mt-3" style="background:rgba(0,255,136,0.1); border:1px solid #00ff88; color:#00ff88;"><i class="fas fa-check-circle"></i> Identity Verified! You are officially recognized globally on BizNexus.</div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
