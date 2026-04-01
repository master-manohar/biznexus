<?php
// /admin/manage_coupons.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Unauthorized access.");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_coupon'])) {
        $code    = strtoupper(trim($_POST['code']));
        $type    = $_POST['type']; // percentage or fixed
        $value   = (float)$_POST['value'];
        $max     = (int)$_POST['max_uses'];
        $expires = $_POST['expires_at'] ? $_POST['expires_at'] : null;

        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, max_uses, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $max, $expires]);
            $msg = "<div class='alert alert-success'>Coupon '$code' created successfully!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    if (isset($_POST['delete_coupon'])) {
        $cid = (int)$_POST['coupon_id'];
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$cid]);
        $msg = "<div class='alert alert-info'>Coupon deleted.</div>";
    }
}

$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Manage Coupons — BizNexus Admin';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="font-family:'Syne',sans-serif;font-weight:800;color:#e8e8f5;">🎟️ Coupon Management</h2>
        <a href="/admin/" class="btn btn-outline-light btn-sm">← Back to Admin</a>
    </div>

    <?= $msg ?>

    <div class="row">
        <!-- Create Form -->
        <div class="col-md-4">
            <div class="stat-card" style="background:#13131a; border:1px solid #1e1e2e;">
                <h5 style="color:#FFD700;margin-bottom:20px;">Create New Coupon</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small">Code (e.g. FLASH50)</label>
                        <input type="text" name="code" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Type</label>
                        <select name="type" class="form-control" style="background:#0d0d16; border-color:#2a2a3a; color:#fff;">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (₹)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Discount Value</label>
                        <input type="number" step="0.01" name="value" class="form-control" required style="background:#0d0d16; border-color:#2a2a3a; color:#fff;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Max Uses (0 for unlimited)</label>
                        <input type="number" name="max_uses" class="form-control" value="0" style="background:#0d0d16; border-color:#2a2a3a; color:#fff;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Expiry Date (Optional)</label>
                        <input type="date" name="expires_at" class="form-control" style="background:#0d0d16; border-color:#2a2a3a; color:#fff;">
                    </div>
                    <button type="submit" name="create_coupon" class="btn btn-gold w-100">Generate Coupon</button>
                </form>
            </div>
        </div>

        <!-- List Coupons -->
        <div class="col-md-8">
            <div class="stat-card" style="background:#13131a; border:1px solid #1e1e2e; padding:0; overflow:hidden;">
                <table class="table table-dark table-hover mb-0" style="font-size:.85rem;">
                    <thead style="background:#0d0d16;">
                        <tr>
                            <th class="ps-3">Code</th>
                            <th>Value</th>
                            <th>Uses</th>
                            <th>Expiry</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($coupons as $c): 
                            $isExpired = $c['expires_at'] && strtotime($c['expires_at']) < time();
                        ?>
                        <tr style="<?= $isExpired ? 'opacity:0.5;' : '' ?>">
                            <td class="ps-3"><strong style="color:#FFD700;"><?= $c['code'] ?></strong></td>
                            <td><?= $c['type']==='percentage' ? $c['value'].'%' : '₹'.number_format($c['value']) ?></td>
                            <td><?= $c['uses'] ?> / <?= $c['max_uses'] ?: '∞' ?></td>
                            <td class="<?= $isExpired?'text-danger':'' ?>"><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : 'Never' ?></td>
                            <td class="text-end pe-3">
                                <form method="POST" onsubmit="return confirm('Delete this coupon?');" style="display:inline;">
                                    <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="delete_coupon" class="btn btn-link text-danger p-0">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($coupons)): ?><tr><td colspan="5" class="text-center p-4">No coupons generated yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
