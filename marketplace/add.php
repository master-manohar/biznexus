<?php
// /marketplace/add.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO marketplace (user_id, title, description, price, category, type, status, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $uid,
        $_POST['title'] ?? '',
        $_POST['description'] ?? '',
        $_POST['price'] ?? 0,
        $_POST['category'] ?? '',
        $_POST['type'] ?? 'product',
        'active'
    ]);
    header('Location: /marketplace/list.php'); 
    exit;
}

// Fetch categories from product_categories if it exists
try {
    $cats = $pdo->query("SELECT name FROM product_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $cats = ['Products', 'Services', 'Partnerships', 'Investments'];
}

$page_title = 'Add Listing - BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="d-flex justify-content-between mb-4">
            <a href="/marketplace/index.php" style="color:#FFD700;text-decoration:none;font-weight:700">← Marketplace</a>
            <h4 style="color:#FFD700;margin:0">+ Add Listing</h4>
        </div>
        <div class="card p-4" style="background:#13131a; border:1px solid #2a2a3a; border-radius:16px;">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted small">Title *</label>
                        <input class="form-control" name="title" required placeholder="What are you selling?">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Type</label>
                        <select class="form-control" name="type">
                            <option value="product">Product</option>
                            <option value="service">Service</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Price (₹)</label>
                        <input class="form-control" name="price" type="number" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">Category</label>
                        <select class="form-control" name="category">
                            <option value="">Select</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">Description</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Describe what you offer..."></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill">Publish Listing →</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
