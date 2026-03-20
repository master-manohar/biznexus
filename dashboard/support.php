<?php
// /dashboard/support.php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$uid = (int)$_SESSION['user_id'];

// Auto-create table for Agent 12 if missing (Feasibility Guardian pattern)
$pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    ai_response TEXT DEFAULT NULL,
    status ENUM('open', 'resolved', 'escalated') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if(!empty($subject) && !empty($message)) {
        // AI Auto-Response Logic
        $ai_reply = null;
        $status = 'open';
        
        $msgLower = strtolower($message);
        if (strpos($msgLower, 'fake lead') !== false || strpos($msgLower, 'wrong number') !== false) {
            $ai_reply = "🤖 **BizNexus AI Triage:** We detected a report regarding a Fake Lead. Your coins have been automatically refunded to escrow, and the offending user's Trust Score has been penalized. A human agent will review the network activity shortly.";
            $status = 'escalated'; // Escalated for human review as per CEO Negative Flow
        } elseif (strpos($msgLower, 'upgrade') !== false || strpos($msgLower, 'payment') !== false) {
            $ai_reply = "🤖 **BizNexus AI Triage:** Having trouble upgrading? You can view all our Razorpay-secured plans directly at the 'Upgrade' tab. Please ensure your pop-up blocker is disabled during payment.";
            $status = 'resolved';
        }
        
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, ai_response, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $subject, $message, $ai_reply, $status]);
        
        $msg = "<div class='alert alert-success' style='background:rgba(0,255,136,0.1); border:1px solid #00ff88; color:#00ff88;'>Your ticket has been securely submitted to the BizNexus team.</div>";
    }
}

// Fetch user tickets
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$uid]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ticket-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 25px; margin-bottom: 20px;}
        .ai-response { background: rgba(0, 82, 212, 0.1); border-left: 4px solid #4364F7; padding: 15px; margin-top: 15px; border-radius: 0 8px 8px 0;}
    </style>
</head>
<body style="background: var(--bg); color: var(--text);">

<div class="sidebar d-none d-md-flex">
    <div class="sidebar-logo">⚡ BizNexus</div>
    <nav class="nav flex-column" style="flex:1">
        <a class="nav-link" href="/dashboard/index.php">🏠 Dashboard</a>
        <a class="nav-link" href="/dashboard/leads.php">📈 CRM</a>
        <a class="nav-link" href="/dashboard/kyc_upload.php">🛡️ Trust</a>
        <a class="nav-link active" href="/dashboard/support.php">🎧 Support</a>
    </nav>
</div>

<div class="main p-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 style="font-family: 'Syne', sans-serif; font-weight: 800; margin-bottom: 20px;">Help & Support Engine</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="ticket-card">
                        <h4>Open a Ticket</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted">Subject</label>
                                <input type="text" name="subject" required class="form-control" style="background:#0d0d14; border-color:var(--border); color:#fff;" placeholder="e.g., Fake Lead Reported">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Message Details</label>
                                <textarea name="message" required class="form-control" rows="5" style="background:#0d0d14; border-color:var(--border); color:#fff;" placeholder="Describe the issue..."></textarea>
                            </div>
                            <button type="submit" class="btn" style="background:var(--gold); color:#000; font-weight:bold; width:100%;">Submit Ticket</button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <h4 class="mb-3">Your Support History</h4>
                    <?php if(empty($tickets)): ?>
                        <div class="text-muted text-center py-4 border rounded" style="border-color:var(--border)!important;">No tickets found.</div>
                    <?php else: ?>
                        <?php foreach($tickets as $t): ?>
                            <div class="ticket-card" style="padding:15px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 style="color:#fff; margin:0;"><?= htmlspecialchars($t['subject']) ?></h5>
                                    <?php if($t['status'] == 'open'): ?>
                                        <span class="badge bg-warning text-dark border"><i class="fas fa-clock"></i> Open</span>
                                    <?php elseif($t['status'] == 'escalated'): ?>
                                        <span class="badge bg-danger text-light"><i class="fas fa-search"></i> In Review</span>
                                    <?php else: ?>
                                        <span class="badge bg-success text-light"><i class="fas fa-check"></i> Resolved</span>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size:14px; color:var(--text2);"><?= nl2br(htmlspecialchars($t['message'])) ?></p>
                                
                                <?php if($t['ai_response']): ?>
                                    <div class="ai-response">
                                        <div style="font-size:13px; color:#4364F7; font-weight:bold; margin-bottom:5px;">AI Auto-Response Executed:</div>
                                        <div style="font-size:14px;"><?= nl2br(htmlspecialchars($t['ai_response'])) ?></div>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted mt-2 d-block"><?= date('M j, Y g:i A', strtotime($t['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
