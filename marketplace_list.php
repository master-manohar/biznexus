<?php
$page_title = 'Marketplace - BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';

$uid = $_SESSION['user_id'];
$cat = trim($_GET['cat'] ?? '');
$type = trim($_GET['type'] ?? '');
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT m.*, u.name as seller_name, u.business_name as seller_biz, u.city as seller_city 
        FROM marketplace m JOIN users u ON m.user_id = u.id WHERE m.status = "active"';
$params = [];
if ($cat) { $sql .= ' AND m.category = ?'; $params[] = $cat; }
if ($type) { $sql .= ' AND m.type = ?'; $params[] = $type; }
if ($search) { $sql .= ' AND (m.title LIKE ? OR m.description LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
$sql .= ' ORDER BY m.created_at DESC LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>

<style>
.marketplace-card { background: #13131a; border: 1px solid #2a2a3a; border-radius: 16px; padding: 20px; height: 100%; display: flex; flex-direction: column; transition: 0.3s; }
.marketplace-card:hover { border-color: #FFD700; transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.4); }
.marketplace-type { font-size: 0.7rem; font-weight: 800; color: #FFD700; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; }
.marketplace-title { margin-bottom: 12px; font-size: 1.05rem; font-weight: 700; color: #e8e8f0; flex: 1; }
.marketplace-price { font-size: 1.6rem; font-weight: 900; color: #FFD700; margin-bottom: 12px; text-shadow: 0 0 15px rgba(255, 215, 0, 0.2); }
.marketplace-desc { color: #888; font-size: 0.85rem; margin-bottom: 16px; line-height: 1.6; }
.marketplace-footer { color: #666; font-size: 0.78rem; margin-top: auto; padding-top: 12px; border-top: 1px solid #1a1a24; }
.search-bar { background: #13131a; border: 1px solid #2a2a3a; border-radius: 14px; padding: 20px; margin-bottom: 30px; }
.form-control, .form-select { background: #0f0f18 !important; border: 1.5px solid #2a2a3a !important; color: #e8e8f0 !important; border-radius: 10px !important; padding: 11px 15px !important; }
.form-control:focus, .form-select:focus { border-color: #FFD700 !important; box-shadow: none !important; }
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">🛒 Marketplace</h1>
        <div class="page-subtitle">Discover premium products and services from the network.</div>
    </div>
    <a href="/marketplace/add.php" class="btn btn-warning fw-bold rounded-pill px-4">+ Add Listing</a>
</div>

<div class="search-bar">
    <form method="GET" class="row g-3">
        <div class="col-md-5">
            <input class="form-control" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search keywords...">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="type">
                <option value="">All Categories</option>
                <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Products</option>
                <option value="service" <?= $type === 'service' ? 'selected' : '' ?>>Services</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-warning w-100 fw-bold rounded-3">Search</button>
        </div>
        <?php if ($cat || $type || $search): ?>
        <div class="col-md-2">
            <a href="/marketplace/list.php" class="btn btn-outline-secondary w-100 rounded-3">Clear</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($listings)): ?>
    <div class="text-center py-5" style="color: #555;">
        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">🛒</div>
        <p>No listings found matching your criteria.</p>
        <a href="/marketplace/add.php" class="text-warning text-decoration-none">List your business here →</a>
    </div>
<?php else: ?>
    <div class="row g-4">
    <?php foreach ($listings as $l): ?>
        <div class="col-md-6 col-xl-3">
            <div class="marketplace-card">
                <div class="marketplace-type"><?= htmlspecialchars(strtoupper($l['type'])) ?></div>
                <h5 class="marketplace-title"><?= htmlspecialchars($l['title']) ?></h5>
                
                <?php if ($l['price'] > 0): ?>
                    <div class="marketplace-price">₹<?= number_format($l['price']) ?></div>
                <?php endif; ?>
                
                <?php if ($l['description']): ?>
                    <p class="marketplace-desc">
                        <?= htmlspecialchars(substr($l['description'], 0, 85)) ?>...
                    </p>
                <?php endif; ?>
                
                <div class="marketplace-footer">
                    <div class="mb-1"><i class="fas fa-building me-1" style="color: #FFD700; width: 14px;"></i> <?= htmlspecialchars($l['seller_biz'] ?? $l['seller_name'] ?? '') ?></div>
                    <?php if ($l['seller_city']): ?>
                        <div><i class="fas fa-map-marker-alt me-1" style="color: #FFD700; width: 14px;"></i> <?= htmlspecialchars($l['seller_city']) ?></div>
                    <?php endif; ?>
                </div>
                
                <a href="/profile/view.php?id=<?= $l['user_id'] ?>" class="btn btn-outline-light btn-sm w-100 mt-3 rounded-pill" style="border-color: #2a2a3a; background: #0f0f18;">View Profile</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>