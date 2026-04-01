<?php
// admin/roadmap.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

// Auth Check (Admins only)
if (($_SESSION['role'] ?? '') !== 'admin' && $_SESSION['user_id'] != 2121) {
    die("Access denied.");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mid    = (int)($_POST['mid'] ?? 0);

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'planned';
        $notes  = $_POST['testing_notes'] ?? '';
        $stmt = $pdo->prepare("UPDATE roadmap_modules SET status = ?, testing_notes = ? WHERE id = ?");
        $stmt->execute([$status, $notes, $mid]);
        $msg = '<div class="alert alert-success py-2 px-3 small">✅ Status Updated.</div>';
    } 
    elseif ($action === 'run_agent') {
        $stmt = $pdo->prepare("UPDATE roadmap_modules SET run_request = 1, status = 'wip' WHERE id = ?");
        $stmt->execute([$mid]);
        $msg = '<div class="alert alert-warning py-2 px-3 small">🚀 Deployment Agent Triggered! I will begin work on this module immediately.</div>';
    }
}

$modules = $pdo->query("SELECT * FROM roadmap_modules ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$status_colors = [
    'planned'   => '#555577',
    'wip'       => '#a259ff',
    'testing'   => '#4488ff',
    'completed' => '#00e87a',
    'live'      => '#FFD700'
];

$page_title = 'Mission Roadmap — BizNexus Control';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="font-family:'Syne',sans-serif; font-weight:800; color:#FFD700; margin:0;">🛰️ Mission Roadmap</h2>
            <p style="color:#888; margin:5px 0 0; font-size:.85rem;">Track, Test, and Deploy BizNexus 2.0 Modules.</p>
        </div>
        <div>
            <span class="badge bg-dark border border-secondary text-secondary">v2.0 Beta Control</span>
        </div>
    </div>

    <?= $msg ?>

    <div class="card bg-dark border-secondary shadow-lg overflow-hidden" style="border-radius:16px; background:#13131a !important; border:1px solid #1e1e2e !important;">
        <div class="table-responsive">
            <table class="table table-dark mb-0 align-middle" style="background:transparent;">
                <thead>
                    <tr style="background:#0d0d16; border-bottom:1px solid #2a2a3a;">
                        <th class="ps-4 py-3 text-uppercase small ls-1 text-muted">Module / Functionality</th>
                        <th class="py-3 text-uppercase small ls-1 text-muted">Status</th>
                        <th class="py-3 text-uppercase small ls-1 text-muted">Testing Notes</th>
                        <th class="pe-4 py-3 text-end text-uppercase small ls-1 text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $m): ?>
                    <tr style="border-bottom:1px solid rgba(42,42,58,.4);">
                        <td class="ps-4 py-4">
                            <div class="fw-bold text-white mb-1"><?= htmlspecialchars($m['name']) ?></div>
                            <div class="small text-muted" style="max-width:350px;"><?= htmlspecialchars($m['description']) ?></div>
                        </td>
                        <td>
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="mid" value="<?= $m['id'] ?>">
                                <select name="status" class="form-select form-select-sm bg-dark border-secondary text-white" 
                                        style="width:120px; font-size:.75rem; border-color:<?= $status_colors[$m['status']] ?>33 !important;" 
                                        onchange="this.form.submit()">
                                    <?php foreach ($status_colors as $s => $c): ?>
                                    <option value="<?= $s ?>" <?= $m['status']==$s?'selected':'' ?>><?= strtoupper($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="mid" value="<?= $m['id'] ?>">
                                <input type="hidden" name="status" value="<?= $m['status'] ?>">
                                <textarea name="testing_notes" class="form-control form-control-sm bg-transparent border-0 text-muted small" 
                                          style="resize:none; padding:0;" placeholder="Add notes..." onblur="this.form.submit()"><?= htmlspecialchars($m['testing_notes']) ?></textarea>
                            </form>
                        </td>
                        <td class="pe-4 text-end">
                            <?php if ($m['run_request']): ?>
                                <button class="btn btn-sm opacity-50 disabled" style="background:#1a1a24; color:#555; border:1px solid #1e1e2e; font-size:.7rem; font-weight:800; padding:8px 16px;">
                                    ⌛ AGENT RUNNING...
                                </button>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="run_agent">
                                    <input type="hidden" name="mid" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-gold" style="font-size:.75rem; font-weight:800; padding:8px 20px; border-radius:8px; background:linear-gradient(135deg,#FFD700,#ff8c00); border:none; color:#000;">
                                        🚀 RUN AGENT
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 text-center">
        <p class="small text-muted">
            <i class="fas fa-info-circle"></i> Clicking <strong>"RUN AGENT"</strong> signals the AI Development Agent to start coding this module in the background.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
