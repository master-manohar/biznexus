<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
// Security: Check if admin
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    // die("Unauthorized access."); 
}

// Stats
$total_pages = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
$cat_count = $pdo->query("SELECT COUNT(DISTINCT category) FROM seo_pages")->fetchColumn();
$city_count = $pdo->query("SELECT COUNT(DISTINCT city) FROM seo_pages")->fetchColumn();

// Latest Pages
$latest_pages = $pdo->query("SELECT * FROM seo_pages ORDER BY id DESC LIMIT 50")->fetchAll();

// Distribution
$cat_dist = $pdo->query("SELECT category, COUNT(*) as count FROM seo_pages GROUP BY category ORDER BY count DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SEO Dominance Dashboard | BizNexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --glass: rgba(255, 255, 255, 0.05); }
        body { background: #0f172a; color: #e2e8f0; font-family: 'Inter', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; margin-bottom: 20px; }
        .stat-card { padding: 25px; text-align: center; }
        .stat-val { font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #94a3b8; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        .table { color: #e2e8f0; }
        .badge-live { background: #ef4444; animation: pulse 2s infinite; font-size: 0.7rem; vertical-align: middle; }
        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }
        .btn-gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e293b; font-weight: 600; border: none; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-rocket text-warning"></i> SEO Power Engine <span class="badge badge-live">LIVE</span></h1>
            <a href="https://biznexus.in/agent/seo_power_agent.php?key=BizCron2024" target="_blank" class="btn btn-gold btn-sm"><i class="fas fa-sync"></i> Trigger Next Batch (Limit: 25)</a>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-val"><?= number_format($total_pages) ?></div>
                    <div class="stat-label">Total Landing Pages</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-val"><?= $cat_count ?></div>
                    <div class="stat-label">Active Industries</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-val"><?= $city_count ?></div>
                    <div class="stat-label">Target Districts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-val"><?php $goal=10000; echo round(($total_pages/$goal)*100, 1); ?>%</div>
                    <div class="stat-label">Goal Completion (10k)</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card p-4 mb-5 border-0" style="background: rgba(30, 41, 59, 0.5);">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-warning fw-bold"><i class="fas fa-flag-checkered"></i> 10,000 Page Sprint</span>
                <span class="text-muted"><?= number_format($total_pages) ?> / 10,000 Live</span>
            </div>
            <div class="progress bg-dark" style="height: 12px; border-radius: 10px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" 
                     style="width: <?= ($total_pages/$goal*100) ?>%"></div>
            </div>
            <div class="mt-3 text-center">
                <a href="/agent/bulk_seo_agent.php?key=BizCron2024&batch=10" class="btn btn-warning fw-bold px-4">
                    <i class="fas fa-bolt"></i> LAUNCH TURBO ENGINE (10/batch)
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header border-0 bg-transparent py-3">
                        <h5 class="mb-0"><i class="fas fa-list text-info"></i> Latest Local Gateways (Real-time Feed)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Industry</th>
                                        <th>City</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($latest_pages as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['category']) ?></td>
                                        <td><?= htmlspecialchars($p['city']) ?></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td><a href="/services/<?= $p['slug'] ?>" target="_blank" class="text-warning"><i class="fas fa-external-link-alt"></i> View</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header border-0 bg-transparent py-3">
                        <h5 class="mb-0"><i class="fas fa-chart-pie text-warning"></i> Category Diversity</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($cat_dist as $cd): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= htmlspecialchars($cd['category']) ?></span>
                                <span class="text-warning"><?= $cd['count'] ?> pages</span>
                            </div>
                            <div class="progress bg-dark" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: <?= ($cd['count'] / $total_pages * 100) ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
