<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$uid = getCurrentUserId();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('INSERT INTO marketplace (user_id,title,description,price,category,type,status,created_at) VALUES (?,?,?,?,?,?,?,NOW())')
    ->execute([$uid,$_POST['title']??'',$_POST['description']??'',$_POST['price']??0,$_POST['category']??'',$_POST['type']??'product','active']);
    header('Location: /marketplace/list.php'); exit;
}
$cats = $pdo->query("SELECT name FROM product_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$page_title = 'Add Listing - BizNexus';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-7">
<div class="d-flex justify-content-between mb-4"><a href="/marketplace/list.php" style="color:#FFD700;text-decoration:none;font-weight:700">← Marketplace</a><h4 style="color:#FFD700;margin:0">+ Add Listing</h4></div>
<div class="card-dark">
<form method="POST">
<div class="row g-3">
<div class="col-12"><label style="color:#888;font-size:.82rem">Title *</label><input class="form-control mt-1" name="title" required placeholder="What are you selling?"></div>
<div class="col-md-6"><label style="color:#888;font-size:.82rem">Type</label><select class="form-control mt-1" name="type"><option value="product">Product</option><option value="service">Service</option></select></div>
<div class="col-md-6"><label style="color:#888;font-size:.82rem">Price (₹)</label><input class="form-control mt-1" name="price" type="number" value="0"></div>
<div class="col-12"><label style="color:#888;font-size:.82rem">Category</label><select class="form-control mt-1" name="category"><option value="">Select</option><?php foreach($cats as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?></select></div>
<div class="col-12"><label style="color:#888;font-size:.82rem">Description</label><textarea class="form-control mt-1" name="description" rows="4" placeholder="Describe what you offer..."></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-gold w-100 fw-bold">Publish Listing →</button></div>
</div>
</form>
</div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>