<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;

// Filters
$search     = trim($_GET['search'] ?? '');
$category   = trim($_GET['category'] ?? '');
$min_price  = trim($_GET['min_price'] ?? '');
$max_price  = trim($_GET['max_price'] ?? '');
$sort       = $_GET['sort'] ?? 'newest';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 12;
$offset     = ($page - 1) * $per_page;

// Build query
$conditions = ["m.status = 'active'"];
$params     = [];

if ($search !== '') {
    $conditions[] = "(m.title LIKE :search OR m.description LIKE :search2)";
    $params[':search']  = "%{$search}%";
    $params[':search2'] = "%{$search}%";
}
if ($category !== '') {
    $conditions[] = "m.category = :category";
    $params[':category'] = $category;
}
if ($min_price !== '') {
    $conditions[] = "m.price >= :min_price";
    $params[':min_price'] = (float)$min_price;
}
if ($max_price !== '') {
    $conditions[] = "m.price <= :max_price";
    $params[':max_price'] = (float)$max_price;
}

$where_sql = implode(' AND ', $conditions);

$order_sql = match($sort) {
    'price_asc'  => 'm.price ASC',
    'price_desc' => 'm.price DESC',
    'oldest'     => 'm.created_at ASC',
    default      => 'm.created_at DESC',
};

// Count
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM marketplace m WHERE {$where_sql}");
    $count_stmt->execute($params);
    $total_listings = (int)$count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_listings = 0;
}

$total_pages = max(1, ceil($total_listings / $per_page));

// Listings
$listings = [];
try {
    $sql = "SELECT m.*, u.name AS seller_name, u.avatar AS seller_avatar
            FROM marketplace m
            LEFT JOIN users u ON u.id = m.user_id
            WHERE {$where_sql}
            ORDER BY {$order_sql}
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $listings = [];
}

// Categories
$categories = [];
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM marketplace WHERE status='active' AND category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// Build pagination URL helper
function pagUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Marketplace – BizNexus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--gold:#FFD700;--green:#00ff88;--bg:#0a0a0f;--card:#13131a;--border:#1e1e2e;}
*{box-sizing:border-box;}
body{background:var(--bg);color:#e0e0e0;font-family:'Segoe UI',sans-serif;min-height:100vh;}

/* NAV */
.top-nav{background:#0d0d14;border-bottom:1px solid var(--border);padding:14px 0;}
.top-nav .brand{font-size:1.4rem;font-weight:700;color:var(--gold);text-decoration:none;letter-spacing:1px;}
.top-nav .brand span{color:var(--green);}
.nav-links a{color:#aaa;text-decoration:none;margin-left:20px;font-size:.9rem;transition:color .2s;}
.nav-links a:hover,.nav-links a.active{color:var(--gold);}
.btn-post{background:linear-gradient(135deg,var(--gold),#e6c200);color:#000;font-weight:700;border:none;
          padding:7px 18px;border-radius:8px;font-size:.85rem;text-decoration:none;transition:opacity .2s;}
.btn-post:hover{opacity:.85;color:#000;}

/* HERO */
.mkt-hero{background:linear-gradient(135deg,#0d0d14 0%,#1a1a2e 100%);
          border-bottom:1px solid var(--border);padding:40px 0 30px;}
.mkt-hero h1{font-size:2rem;font-weight:700;color:#fff;margin-bottom:6px;}
.mkt-hero h1 span{color:var(--gold);}
.mkt-hero p{color:#888;font-size:.95rem;}
.stat-badge{background:var(--card);border:1px solid var(--border);border-radius:10px;
            padding:10px 20px;text-align:center;display:inline-block;}
.stat-badge .num{font-size:1.4rem;font-weight:700;color:var(--gold);}
.stat-badge .lbl{font-size:.75rem;color:#777;display:block;}

/* SEARCH BAR */
.search-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;}
.search-input-group{position:relative;}
.search-input-group .form-control{background:#0d0d14;border:1px solid var(--border);color:#fff;
                                   border-radius:10px 0 0 10px;padding:12px 18px;font-size:1rem;}
.search-input-group .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 2px rgba(255,215,0,.15);outline:none;}
.search-input-group .btn-search{background:var(--gold);color:#000;font-weight:700;border:none;
                                 border-radius:0 10px 10px 0;padding:12px 24px;cursor:pointer;}
.search-input-group .btn-search:hover{background:#e6c200;}

/* FILTERS */
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;}
.filter-bar label{font-size:.78rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;}
.filter-select,.filter-input{background:#0d0d14;border:1px solid var(--border);color:#e0e0e0;
                              border-radius:8px;padding:8px 12px;width:100%;font-size:.88rem;}
.filter-select:focus,.filter-input:focus{border-color:var(--gold);outline:none;box-shadow:none;}
.filter-select option{background:#0d0d14;}
.btn-filter{background:var(--green);color:#000;font-weight:700;border:none;border-radius:8px;
            padding:9px 20px;cursor:pointer;font-size:.88rem;transition:opacity .2s;}
.btn-filter:hover{opacity:.85;}
.btn-reset{background:transparent;border:1px solid #444;color:#aaa;border-radius:8px;
           padding:9px 16px;cursor:pointer;font-size:.88rem;text-decoration:none;transition:all .2s;}
.btn-reset:hover{border-color:#777;color:#fff;}
.active-filter-tag{background:#1e1e2e;border:1px solid var(--gold);color:var(--gold);
                   border-radius:20px;padding:3px 12px;font-size:.78rem;display:inline-flex;
                   align-items:center;gap:6px;margin:2px;}
.active-filter-tag a{color:var(--gold);text-decoration:none;font-size:.85rem;}

/* LISTING CARDS */
.listings-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.listings-header .total-lbl{color:#888;font-size:.88rem;}
.listings-header .total-lbl strong{color:#fff;}
.sort-select{background:#0d0d14;border:1px solid var(--border);color:#e0e0e0;
             border-radius:8px;padding:6px 12px;font-size:.85rem;cursor:pointer;}
.sort-select:focus{outline:none;border-color:var(--gold);}

.listing-card{background:var(--card);border:1px solid var(--border);border-radius:14px;
              overflow:hidden;transition:transform .25s,border-color .25s,box-shadow .25s;height:100%;}
.listing-card:hover{transform:translateY(-4px);border-color:var(--gold);
                    box-shadow:0 8px 30px rgba(255,215,0,.1);}
.card-img-wrap{position:relative;overflow:hidden;height:180px;background:#0d0d14;}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .35s;}
.listing-card:hover .card-img-wrap img{transform:scale(1.05);}
.card-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                       font-size:3rem;color:#333;}
.card-badge{position:absolute;top:10px;left:10px;background:rgba(0,0,0,.75);
            border:1px solid rgba(255,215,0,.3);color:var(--gold);
            border-radius:6px;padding:3px 10px;font-size:.72rem;font-weight:600;backdrop-filter:blur(4px);}
.card-badge.featured{background:rgba(255,215,0,.15);border-color:var(--gold);color:var(--gold);}
.card-body-inner{padding:16px;}
.card-title-text{font-size:.95rem;font-weight:600;color:#fff;margin-bottom:6px;
                 display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.card-desc{font-size:.8rem;color:#888;margin-bottom:10px;
           display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.card-price{font-size:1.2rem;font-weight:700;color:var(--gold);margin-bottom:10px;}
.card-price .price-label{font-size:.7rem;color:#777;font-weight:400;margin-left:4px;}
.card-meta{display:flex;align-items:center;gap:8px;font-size:.75rem;color:#777;margin-bottom:12px;}
.card-meta .avatar-sm{width:22px;height:22px;border-radius:50%;object-fit:cover;border:1px solid #333;}
.card-meta .avatar-placeholder{width:22px;height:22px;border-radius:50%;background:#1e1e2e;
                                display:inline-flex;align-items:center;justify-content:center;
                                font-size:.6rem;color:#777;border:1px solid #333;}
.card-footer-inner{padding:12px 16px;border-top:1px solid var(--border);
                   display:flex;gap:8px;background:rgba(0,0,0,.2);}
.btn-view{flex:1;background:transparent;border:1px solid var(--gold);color:var(--gold);
          border-radius:8px;padding:7px;font-size:.8rem;font-weight:600;text-align:center;
          text-decoration:none;transition:all .2s;}
.btn-view:hover{background:var(--gold);color:#000;}
.btn-contact{flex:1;background:var(--green);border:none;color:#000;
             border-radius:8px;padding:7px;font-size:.8rem;font-weight:700;
             text-align:center;text-decoration:none;transition:opacity .2s;}
.btn-contact:hover{opacity:.85;color:#000;}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 20px;}
.empty-state .icon{font-size:4rem;color:#2a2a3e;margin-bottom:16px;}
.empty-state h4{color:#666;font-weight:500;}
.empty-state p{color:#444;font-size:.9rem;}

/* PAGINATION */
.pagination-wrap{display:flex;justify-content:center;gap:6px;flex-wrap:wrap;margin-top:30px;}
.page-btn{background:var(--card);border:1px solid var(--border);color:#aaa;
          border-radius:8px;padding:8px 14px;text-decoration:none;font-size:.85rem;transition:all .2s;}
.page-btn:hover{border-color:var(--gold);color:var(--gold);}
.page-btn.active{background:var(--gold);border-color:var(--gold);color:#000;font-weight:700;}
.page-btn.disabled{opacity:.3;pointer-events:none;}

/* SIDEBAR */
.sidebar-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:20px;}
.sidebar-card h6{font-size:.8rem;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;}
.cat-link{display:flex;align-items:center;justify-content:space-between;
          padding:8px 12px;border-radius:8px;text-decoration:none;color:#bbb;
          font-size:.85rem;margin-bottom:4px;transition:all .2s;}
.cat-link:hover,.cat-link.active{background:#1e1e2e;color:var(--gold);}
.cat-count{background:#1e1e2e;color:#777;border-radius:10px;padding:1px 8px;font-size:.72rem;}
.cat-link.active .cat-count{background:rgba(255,215,0,.15);color:var(--gold);}

/* RESPONSIVE */
@media(max-width:768px){
  .mkt-hero h1{font-size:1.5rem;}
  .listings-header{flex-direction:column;align-items:flex-start;gap:10px;}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="top-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="/index.php" class="brand">Biz<span>Nexus</span></a>
    <div class="nav-links d-flex align-items-center">
      <a href="/dashboard.php">Dashboard</a>
      <a href="/marketplace/index.php" class="active">Marketplace</a>
      <a href="/network/index.php">Network</a>
      <a href="/marketplace/add.php" class="btn-post ms-3"><i class="fa fa-plus me-1"></i>Post Listing</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="mkt-hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-7">
        <h1>Business <span>Marketplace</span></h1>
        <p>Discover products, services, partnerships & investment opportunities</p>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div class="stat-badge me-2">
          <span class="num"><?= number_format($total_listings) ?></span>
          <span class="lbl">Active Listings</span>
        </div>
        <?php
        try {
            $seller_count = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM marketplace WHERE status='active'")->fetchColumn();
        } catch(PDOException $e){ $seller_count = 0; }
        ?>
        <div class="stat-badge">
          <span class="num"><?= number_format((int)$seller_count) ?></span>
          <span class="lbl">Sellers</span>
        </div>
      </div>
    </div>

    <!-- SEARCH -->
    <div class="search-wrap mt-4">
      <form method="GET" action="">
        <div class="row g-2">
          <div class="col-12 col-md-8">
            <div class="input-group search-input-group">
              <input type="text" name="search" class="form-control"
                     placeholder="Search listings, products, services..."
                     value="<?= htmlspecialchars($search) ?>"/>
              <button type="submit" class="btn-search"><i class="fa fa-search me-1"></i>Search</button>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="category" class="filter-select" style="height:48px;" onchange="this.form.submit()">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $category===$cat?'selected':'' ?>>
                  <?= htmlspecialchars(ucwords($cat)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="sort" class="filter-select" style="height:48px;" onchange="this.form.submit()">
              <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Newest First</option>
              <option value="oldest"     <?= $sort==='oldest'    ?'selected':''?>>Oldest First</option>
              <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Price: Low–High</option>
              <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Price: High–Low</option>
            </select>
          </div>
          <!-- hidden carry-overs -->
          <?php if($min_price!==''): ?><input type="hidden" name="min_price" value="<?= htmlspecialchars($min_price)?>"/><?php endif; ?>
          <?php if($max_price!==''): ?><input type="hidden" name="max_price" value="<?= htmlspecialchars($max_price)?>"/><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="container py-4">
  <div class="row g-4">

    <!-- SIDEBAR -->
    <div class="col-lg-3 d-none d-lg-block">

      <!-- Price Filter -->
      <div class="sidebar-card">
        <h6><i class="fa fa-sliders me-2"></i>Price Range</h6>
        <form method="GET" action="">
          <?php if($search!==''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search)?>"/><?php endif; ?>
          <?php if($category!==''): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category)?>"/><?php endif; ?>
          <input type="hidden" name="sort" value="<?= htmlspecialchars($sort)?>"/>
          <div class="mb-3">
            <label>Min Price (₹)</label>
            <input type="number" name="min_price" class="filter-input" placeholder="0"
                   value="<?= htmlspecialchars($min_price) ?>"/>
          </div>
          <div class="mb-3">
            <label>Max Price (₹)</label>
            <input type="number" name="max_price" class="filter-input" placeholder="Any"
                   value="<?= htmlspecialchars($max_price) ?>"/>
          </div>
          <button type="submit" class="btn-filter w-100">Apply</button>
          <?php if($min_price!==''||$max_price!==''): ?>
            <a href="?<?= http_build_query(array_diff_key($_GET,['min_price'=>'','max_price'=>''])) ?>"
               class="btn-reset w-100 mt-2 d-block text-center">Clear Price</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Category Filter -->
      <?php if (!empty($categories)): ?>
      <div class="sidebar-card">
        <h6><i class="fa fa-tag me-2"></i>Categories</h6>
        <?php
        // count per category
        $cat_counts = [];
        try {
            $cc = $pdo->query("SELECT category, COUNT(*) as cnt FROM marketplace WHERE status='active' GROUP BY category");
            foreach ($cc->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cat_counts[$row['category']] = $row['cnt'];
            }
        } catch(PDOException $e){}
        ?>
        <a href="?<?= http_build_query(array_merge($_GET,['category'=>'','page'=>1])) ?>"
           class="cat-link <?= $category===''?'active':'' ?>">
          <span><i class="fa fa-th me-2"></i>All Categories</span>
          <span class="cat-count"><?= number_format($total_listings) ?></span>
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['category'=>$cat,'page'=>1])) ?>"
           class="cat-link <?= $category===$cat?'active':'' ?>">
          <span><i class="fa fa-chevron-right me-2" style="font-size:.65rem;"></i><?= htmlspecialchars(ucwords($cat)) ?></span>
          <span class="cat-count"><?= $cat_counts[$cat] ?? 0 ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- My Listings shortcut -->
      <?php if ($user_id): ?>
      <div class="sidebar-card" style="background:linear-gradient(135deg,#1a1a0a,#13131a);border-color:rgba(255,215,0,.2);">
        <h6 style="color:var(--gold);"><i class="fa fa-store me-2"></i>My Store</h6>
        <a href="/marketplace/list.php" class="d-block text-center mb-2" style="color:var(--gold);text-decoration:none;font-size:.85rem;">
          <i class="fa fa-list me-1"></i>My Listings
        </a>
        <a href="/marketplace/add.php" class="btn-post d-block text-center" style="border-radius:8px;padding:8px;">
          <i class="fa fa-plus me-1"></i>Add Listing
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- LISTINGS GRID -->
    <div class="col-lg-9">

      <!-- Active Filters -->
      <?php $has_filters = ($search!==''||$category!==''||$min_price!==''||$max_price!==''); ?>
      <?php if ($has_filters): ?>
      <div class="mb-3">
        <span style="font-size:.8rem;color:#777;margin-right:8px;">Active filters:</span>
        <?php if($search!==''): ?>
          <span class="active-filter-tag">
            Search: "<?= htmlspecialchars(mb_strimwidth($search,0,20,'…')) ?>"
            <a href="?<?= http_build_query(array_merge($_GET,['search'=>'','page'=>1])) ?>">×</a>
          </span>
        <?php endif; ?>
        <?php if($category!==''): ?>
          <span class="active-filter-tag">
            <?= htmlspecialchars(ucwords($category)) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['category'=>'','page'=>1])) ?>">×</a>
          </span>
        <?php endif; ?>
        <?php if($min_price!==''): ?>
          <span class="active-filter-tag">
            Min ₹<?= number_format((float)$min_price) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['min_price'=>'','page'=>1])) ?>">×</a>
          </span>
        <?php endif; ?>
        <?php if($max_price!==''): ?>
          <span class="active-filter-tag">
            Max ₹<?= number_format((float)$max_price) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['max_price'=>'','page'=>1])) ?>">×</a>
          </span>
        <?php endif; ?>
        <a href="/marketplace/index.php" class="btn-reset ms-1">Clear All</a>
      </div>
      <?php endif; ?>

      <!-- Header -->
      <div class="listings-header">
        <span class="total-lbl">
          Showing <strong><?= count($listings) ?></strong> of <strong><?= number_format($total_listings) ?></strong> listings
          <?= $page > 1 ? "· Page {$page} of {$total_pages}" : '' ?>
        </span>
        <div class="d-flex gap-2">
          <!-- Mobile sort -->
          <select class="sort-select d-lg-none" onchange="window.location='?'+updateParam('sort',this.value)">
            <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Newest</option>
            <option value="oldest"     <?= $sort==='oldest'    ?'selected':''?>>Oldest</option>
            <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Price ↑</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Price ↓</option>
          </select>
          <?php if($user_id): ?>
          <a href="/marketplace/add.php" class="btn-post d-lg-none"><i class="fa fa-plus me-1"></i>Post</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cards -->
      <?php if (empty($listings)): ?>
        <div class="empty-state">
          <div class="icon"><i class="fa fa-store-slash"></i></div>
          <h4>No listings found</h4>
          <p>Try adjusting your search or filters</p>
          <?php if ($user_id): ?>
          <a href="/marketplace/add.php" class="btn-post mt-3 d-inline-block">
            <i class="fa fa-plus me-1"></i>Be the first to post!
          </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($listings as $l): ?>
          <div class="col-sm-6 col-xl-4">
            <div class="listing-card">
              <!-- Image -->
              <div class="card-img-wrap">
                <?php if (!empty($l['image'])): ?>
                  <img src="<?= htmlspecialchars($l['image']) ?>" alt="<?= htmlspecialchars($l['title']) ?>"/>
                <?php else: ?>
                  <div class="card-img-placeholder">
                    <?php
                    $icons = ['product'=>'📦','service'=>'🛠','partnership'=>'🤝','investment'=>'💰','other'=>'💼'];
                    echo $icons[$l['category'] ?? 'other'] ?? '💼';
                    ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($l['category'])): ?>
                  <span class="card-badge"><?= htmlspecialchars(ucwords($l['category'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($l['featured'])): ?>
                  <span class="card-badge featured" style="right:10px;left:auto;">⭐ Featured</span>
                <?php endif; ?>
              </div>

              <!-- Body -->
              <div class="card-body-inner">
                <div class="card-title-text"><?= htmlspecialchars($l['title']) ?></div>
                <div class="card-desc"><?= htmlspecialchars($l['description'] ?? '') ?></div>
                <div class="card-price">
                  <?php if (!empty($l['price']) && $l['price'] > 0): ?>
                    ₹<?= number_format((float)$l['price'],2) ?>
                    <?php if(!empty($l['price_type'])): ?>
                      <span class="price-label">/<?