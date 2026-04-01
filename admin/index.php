<?php
// admin/index.php
session_start();

// Strict Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/db.php';

// Verify Admin Status
$stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || ($admin['id'] != 2121 && strpos($admin['email'], '@biznexus.in') === false && $admin['email'] !== 'manohar.nch@gmail.com')) {
    die("⛔ 403 Forbidden. Super Admin Access Only.");
}

$page_title = 'Admin | Dashboard';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white"><i class="fas fa-rocket text-warning"></i> Admin Center</h2>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-transparent"><h5 class="text-warning">Management</h5></div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-transparent">
                        <a href="/admin/manage_coupons.php" class="list-group-item list-group-item-action bg-transparent text-white border-secondary">
                            <i class="fas fa-ticket-alt text-warning mr-2"></i> Manage Coupons & Discounts
                        </a>
                        <a href="/superadmin.php" class="list-group-item list-group-item-action bg-transparent text-white border-secondary">
                            <i class="fas fa-users-cog text-info mr-2"></i> Superadmin Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
