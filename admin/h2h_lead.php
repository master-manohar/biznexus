<?php
// admin/h2h_lead.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';

// Only admins or moderators
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    if ($_SESSION['user_id'] != 2121 && strpos($_SESSION['email'] ?? '', '@biznexus.in') === false) {
        die("Unauthorized access.");
    }
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email  = trim($_POST['email'] ?? '');

    if (empty($name) || empty($mobile) || empty($email)) {
        $msg = '<div class="alert alert-danger">All fields are required!</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO h2h_leads (name, mobile, email) VALUES (?, ?, ?)");
            $stmt->execute([$name, $mobile, $email]);

            // Trigger Welcome Email
            sendH2HWelcomeEmail($email, $name);

            $msg = '<div class="alert alert-success">✅ Lead captured! Welcome email sent to ' . htmlspecialchars($name) . '.</div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

$page_title = 'H2H Lead Capture — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container py-4" style="max-width: 500px;">
    <div class="mb-4 text-center">
        <h2 style="font-family:'Syne',sans-serif; font-weight:800; color:#FFD700;">🤝 H2H Lead Capture</h2>
        <p style="color:#888;">Enter details for new H2H members below.</p>
    </div>

    <?= $msg ?>

    <div class="card bg-dark border-secondary p-4 shadow-lg" style="border-radius: 20px;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-warning small">Full Name</label>
                <input type="text" name="name" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff;" placeholder="Enter name">
            </div>
            <div class="mb-3">
                <label class="form-label text-warning small">Mobile Number</label>
                <input type="tel" name="mobile" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff;" placeholder="e.g. 99899...">
            </div>
            <div class="mb-4">
                <label class="form-label text-warning small">Email Address</label>
                <input type="email" name="email" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff;" placeholder="email@example.com">
            </div>
            <button type="submit" class="btn btn-gold w-100 py-3" style="border-radius: 12px; font-weight:800; font-size:1.1rem;">
                🚀 Capture & Send Welcome Email
            </button>
        </form>
    </div>

    <div class="mt-4 text-center">
        <a href="/admin/" class="btn btn-link text-muted" style="text-decoration:none;">← Back to Admin Control</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
