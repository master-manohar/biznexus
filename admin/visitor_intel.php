<?php
// admin/visitor_intel.php
require_once __DIR__ . '/../includes/db.php';

// Auth Check (Admin Only)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'member') !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 100;
$offset = ($page - 1) * $perPage;

// CSV Export Logic
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="biznexus_leads_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Name', 'Phone', 'Category', 'City', 'Source URL', 'IP', 'Date']);
    $stmt = $pdo->query("SELECT * FROM public_leads ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$row['id'], $row['name'], $row['phone'], $row['category'], $row['city'], $row['source_url'], $row['ip_address'], $row['created_at']]);
    }
    fclose($out);
    exit;
}

$filter = $_GET['source'] ?? 'all';
$where = ($filter === 'all') ? "1=1" : "source = :source";

$total_leads = $pdo->prepare("SELECT COUNT(*) FROM public_leads WHERE $where");
if ($filter !== 'all') $total_leads->bindValue(':source', $filter);
$total_leads->execute();
$total_count = $total_leads->fetchColumn();

$leads = $pdo->prepare("SELECT * FROM public_leads WHERE $where ORDER BY id DESC LIMIT :limit OFFSET :offset");
if ($filter !== 'all') $leads->bindValue(':source', $filter);
$leads->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$leads->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$leads->execute();
$leads_data = $leads->fetchAll(PDO::FETCH_ASSOC);

$total_pages = ceil($total_leads / $perPage);

include __DIR__ . '/../includes/layout_start.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-white mb-0">🕵️‍♂️ Visitor Intelligence</h2>
            <p class="text-muted">Tracking real-time intent from your 10,000 SEO pages.</p>
        </div>
        <a href="?export=1" class="btn btn-warning fw-bold">
            <i class="fas fa-download"></i> DOWNLOAD LEADS (CSV)
        </a>
    </div>

    <div class="card border-0 shadow-lg bg-dark">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead style="background: #1e293b; color: #fbbf24;">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Source</th>
                            <th>Name</th>
                            <th>Mobile / WhatsApp</th>
                            <th>Requirement</th>
                            <th>City</th>
                            <th class="pe-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads_data)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No leads captured yet. Keep the Turbo Engine running!</td></tr>
                        <?php endif; ?>
                        <?php foreach ($leads_data as $l): ?>
                        <tr>
                            <td class="ps-4 text-muted" style="font-size: 0.8rem;"><?= date('d M, H:i', strtotime($l['created_at'])) ?></td>
                            <td>
                                <?php if (strpos($l['source'], 'AI_SCOUT') !== false): ?>
                                    <span class="badge bg-success">🤖 AI Scout</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">🌐 Visitor</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($l['name']) ?></td>
                            <td>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $l['phone']) ?>" target="_blank" class="text-success text-decoration-none">
                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($l['phone']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($l['category']) ?></span></td>
                            <td><?= htmlspecialchars($l['city']) ?></td>
                            <td class="pe-4 text-muted small"><?= $l['ip_address'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-dark border-0 p-3">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page-1 ?>">Previous</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link bg-dark border-secondary text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                    </li>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page+1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout_end.php'; ?>
