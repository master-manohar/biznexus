<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$id]);
$email = $stmt->fetchColumn();
if ($email !== 'ceo@biznexus.in') {
    die("Access denied. Super Admin only.");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $new_cat = trim($_POST['new_category']);
        if (!empty($new_cat)) {
            $s = $pdo->prepare("INSERT IGNORE INTO product_categories (name) VALUES (?)");
            $s->execute([$new_cat]);
            $msg = "Category added successfully!";
        }
    } elseif (isset($_POST['del_cat'])) {
        $del_id = (int)$_POST['del_id'];
        $s = $pdo->prepare("DELETE FROM product_categories WHERE id = ?");
        $s->execute([$del_id]);
        $msg = "Category deleted successfully!";
    }
}

$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - God Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0a0a0f; color: #e0e0f0; font-family: 'Segoe UI', sans-serif; }
        .card { background: #13131a; border: 1px solid #2a2a3a; border-radius: 12px; }
        .table { color: #e0e0f0; }
        .table th { border-bottom: 1px solid #2a2a3a; color: #FFD700; font-weight: 600; }
        .table td { border-bottom: 1px solid #2a2a3a; vertical-align: middle; }
        .form-control { background: #0f0f18; border: 1px solid #2a2a3a; color: #fff; }
        .form-control:focus { background: #0f0f18; color: #fff; border-color: #FFD700; box-shadow: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 style="color:#FFD700;font-weight:800;margin:0">
            <i class="fas fa-tags me-2"></i>Product Categories
        </h2>
        <a href="/admin/superadmin.php" class="btn btn-outline-secondary">← Back to Command Center</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success bg-success text-white border-0"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="mb-3" style="color:#FFD700">Add New Category</h5>
                <form method="POST">
                    <input type="hidden" name="add_cat" value="1">
                    <input type="text" name="new_category" class="form-control mb-3" placeholder="e.g. Premium Consulting" required>
                    <button type="submit" class="btn w-100 fw-bold" style="background:var(--gold,#FFD700);color:#0a0a0f">
                        + Add Category
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4">
                <h5 class="mb-3" style="color:#00ff88">Active Categories (<?= count($categories) ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $c): ?>
                            <tr>
                                <td>#<?= $c['id'] ?></td>
                                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category globally?');">
                                        <input type="hidden" name="del_cat" value="1">
                                        <input type="hidden" name="del_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
