<?php
// leads/add_contact.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

$uid = (int)$_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email  = trim($_POST['email'] ?? '');

    if (empty($name) || empty($mobile) || empty($email)) {
        $msg = '<div class="alert alert-danger" style="background:rgba(255,77,109,0.1); border-color:rgba(255,77,109,0.3); color:#ff4d6d;">All fields are required!</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO h2h_leads (user_id, name, mobile, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uid, $name, $mobile, $email]);

            // Trigger Welcome Email (using the high-contrast template)
            sendH2HWelcomeEmail($email, $name);

            $wa_msg = urlencode("Hi $name, it was great meeting you! I've added you to my BizNexus network. Check your email for details or visit https://biznexus.in");
            $wa_url = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $mobile) . "?text=$wa_msg";

            $msg = '
            <div class="alert alert-success" style="background:rgba(0,232,122,0.1); border-color:rgba(0,232,122,0.3); color:#00e87a;">
                <h5 class="mb-2">✅ Contact Added Successfully!</h5>
                <p class="small mb-3">Welcome email sent to ' . htmlspecialchars($name) . '.</p>
                <a href="' . $wa_url . '" target="_blank" class="btn btn-sm btn-success w-100 py-2" style="font-weight:700;">
                    <i class="fab fa-whatsapp"></i> Send WhatsApp Message Now
                </a>
            </div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger" style="background:rgba(255,77,109,0.1); border-color:rgba(255,77,109,0.3); color:#ff4d6d;">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

$page_title = 'Add New Contact — BizNexus CRM';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container py-4" style="max-width: 500px;">
    <div class="mb-4 text-center">
        <h2 style="font-family:\'Syne\',sans-serif; font-weight:800; color:#FFD700;">📇 Add New Contact</h2>
        <p style="color:#888;">Instantly save and welcome your new connections.</p>
    </div>

    <?= $msg ?>

    <div class="card bg-dark border-secondary p-4 shadow-lg" style="border-radius: 20px; background: #13131a !important; border: 1px solid #1e1e2e !important;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-warning small fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff; padding: 12px;" placeholder="e.g. Rahul Sharma">
            </div>
            <div class="mb-3">
                <label class="form-label text-warning small fw-bold">Mobile Number</label>
                <input type="tel" name="mobile" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff; padding: 12px;" placeholder="10-digit mobile">
            </div>
            <div class="mb-4">
                <label class="form-label text-warning small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff; padding: 12px;" placeholder="name@company.com">
            </div>
            <button type="submit" class="btn btn-gold w-100 py-3 mt-2" style="border-radius: 12px; font-weight:800; font-size:1.1rem; background:linear-gradient(135deg,#FFD700,#ff8c00); border:none; color:#000;">
                💾 Save & Send Welcome
            </button>
        </form>
    </div>

    <div class="mt-4 text-center">
        <a href="/leads/list.php" class="btn btn-link text-muted text-decoration-none">← Back to CRM Pipeline</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
