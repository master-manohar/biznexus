<?php
// admin/index.php
session_start();

// Strict Access Control
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
require_once __DIR__ . '/../includes/db.php';

// Verify Admin Status
$stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || ($admin['id'] != 2121 && strpos($admin['email'], '@biznexus.in') === false && $admin['email'] !== 'manohar.nch@gmail.com')) {
    die("<h1 style='color:red; text-align:center; padding-top: 50px;'>⛔ 403 Forbidden. Super Admin Access Only.</h1>");
}
*/
require_once __DIR__ . '/../includes/db.php';

/*
// Fetch Core Metrics
$metrics = [];
$metrics['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
...
try {
    $prStats['sent'] = $pdo->query("SELECT COUNT(*) FROM media_outreach WHERE status='sent'")->fetchColumn();
    $prStats['pending'] = $pdo->query("SELECT COUNT(*) FROM media_outreach WHERE status='pending'")->fetchColumn();
} catch (Exception $e) {
    $prStats['sent'] = 0; $prStats['pending'] = 0;
}
*/
$metrics = ['total_users'=>0, 'total_businesses'=>0, 'total_leads'=>0, 'total_marketplace'=>0];
$prStats = ['sent'=>0, 'pending'=>0];

$page_title = 'Super Admin | Mission Control';
// require_once __DIR__ . '/../includes/layout_start.php';
echo "<h1>Layout Disabled - Debug Mode</h1>";
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="font-weight-bold text-white"><i class="fas fa-rocket text-warning"></i> Mission Control</h2>
        <span class="badge bg-success p-2">v2.0 Active</span>
    </div>

    <!-- KPI Dashboard -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100 p-4 text-center" style="border-radius:15px;">
                <h1 class="display-4 font-weight-bold text-primary"><?= number_format($metrics['total_users']) ?></h1>
                <p class="text-muted text-uppercase tracking-wider mb-0 text-sm">Total Members</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100 p-4 text-center" style="border-radius:15px;">
                <h1 class="display-4 font-weight-bold text-success"><?= number_format($metrics['total_businesses']) ?></h1>
                <p class="text-muted text-uppercase tracking-wider mb-0 text-sm">Websites Generated</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100 p-4 text-center" style="border-radius:15px;">
                <h1 class="display-4 font-weight-bold text-warning"><?= number_format($metrics['total_leads']) ?></h1>
                <p class="text-muted text-uppercase tracking-wider mb-0 text-sm">Leads Captured</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100 p-4 text-center" style="border-radius:15px;">
                <h1 class="display-4 font-weight-bold text-info"><?= number_format($metrics['total_marketplace']) ?></h1>
                <p class="text-muted text-uppercase tracking-wider mb-0 text-sm">Marketplace Items</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Automation Engines -->
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary" style="border-radius:15px;">
                <div class="card-header border-secondary bg-transparent py-3">
                    <h5 class="mb-0 text-warning"><i class="fas fa-cogs"></i> AI & Automation Engines</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary text-info">
                        <div>
                            <h6 class="text-white mb-1 font-weight-bold">SEO Dominance Engine (AIO) <span class="badge bg-danger ms-1">LIVE</span></h6>
                            <p class="text-muted small mb-0">Generates 1,000+ local landing pages with AIO Schema.</p>
                        </div>
                        <a href="/admin/seo_dashboard.php" class="btn btn-warning btn-sm fw-bold"><i class="fas fa-chart-line"></i> View Dominance</a>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
                        <div>
                            <h6 class="text-white mb-1">Mass Website Generator (PHP Engine)</h6>
                            <p class="text-muted small mb-0">Instantly builds SEO sites for users missing websites.</p>
                        </div>
                        <a href="/agent/mass_bulk_sites.php" target="_blank" class="btn btn-outline-success btn-sm"><i class="fas fa-play"></i> Run Engine</a>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
                        <div>
                            <h6 class="text-white mb-1">PR Media Outreach Agent</h6>
                            <p class="text-muted small mb-0">Dispatches 10 emails to journalists (<?= $prStats['pending'] ?> pending).</p>
                        </div>
                        <div>
                            <a href="/agent/media_pr_agent.php?key=BizCron2024&run=preview" target="_blank" class="btn btn-sm btn-outline-info me-2">Preview</a>
                            <a href="/agent/media_pr_agent.php?key=BizCron2024&run=send" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane"></i> Dispatch</a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Lead Distribution Health</h6>
                            <p class="text-muted small mb-0">Check how leads are mapping to tiers.</p>
                        </div>
                        <span class="badge bg-success">Healthy</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary h-100" style="border-radius:15px;">
                <div class="card-header border-secondary bg-transparent py-3">
                    <h5 class="mb-0 text-info"><i class="fas fa-link"></i> Launch Quick Links</h5>
                </div>
                <div class="card-body p-4">
                    <div class="list-group list-group-flush bg-transparent">
                        <a href="/pages/terms.php" target="_blank" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0"><i class="fas fa-file-contract text-muted mr-2"></i> View Terms of Service</a>
                        <a href="/pages/privacy.php" target="_blank" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0"><i class="fas fa-user-secret text-muted mr-2"></i> View Privacy Policy</a>
                        <a href="/find.php" target="_blank" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0"><i class="fas fa-search text-muted mr-2"></i> Test Public AI Matchmaker</a>
                        <a href="/" target="_blank" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0"><i class="fas fa-home text-muted mr-2"></i> View Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
