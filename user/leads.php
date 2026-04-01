<?php
// user/leads.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
require_once __DIR__ . '/../includes/db.php';

$member_id = $_SESSION['user_id'];

// Get all leads dispatched to this member
$stmt = $pdo->prepare("
    SELECT ld.*, pl.name as user_name, pl.phone as user_phone, pl.email as user_email, pl.query, pl.created_at
    FROM lead_dispatches ld
    JOIN public_leads pl ON ld.lead_id = pl.id
    WHERE ld.member_id = ?
    ORDER BY ld.notified_at DESC
");
$stmt->execute([$member_id]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My Leads CRM | BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold text-white"><i class="fas fa-bolt text-warning"></i> My CRM Leads</h2>
            <p class="text-muted">Manage the direct leads generated for your business.</p>
        </div>
        <div>
            <span class="badge bg-dark border-secondary p-2"><i class="fas fa-chart-line text-success"></i> <?= count($leads) ?> Total Leads</span>
        </div>
    </div>

    <div class="card bg-dark border-secondary" style="border-radius:15px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0" style="background: transparent;">
                <thead class="bg-black text-muted" style="border-bottom: 2px solid #2a2a3a;">
                    <tr>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Match Cat.</th>
                        <th class="py-3 px-4">Lead Details</th>
                        <th class="py-3 px-4">Inquiry / Need</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($leads)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No leads matched yet. Ensure your profile category and city are highly optimized!</td></tr>
                    <?php else: ?>
                        <?php foreach($leads as $lead): ?>
                            <tr style="border-bottom: 1px solid #1a1a2a;">
                                <td class="py-3 px-4 text-muted align-middle" style="white-space: nowrap;">
                                    <?= date('d M Y', strtotime($lead['created_at'])) ?><br>
                                    <small><?= date('h:i A', strtotime($lead['created_at'])) ?></small>
                                </td>
                                <td class="py-3 px-4 align-middle">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($lead['category']) ?></span><br>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt text-primary"></i> <?= htmlspecialchars($lead['city']) ?></small>
                                </td>
                                <td class="py-3 px-4 align-middle">
                                    <div class="font-weight-bold text-white mb-1"><i class="fas fa-user text-muted mr-1"></i> <?= htmlspecialchars($lead['user_name']) ?></div>
                                    <div class="text-primary font-weight-bold"><i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($lead['user_phone']) ?></div>
                                    <?php if($lead['user_email']): ?>
                                        <div class="text-muted small"><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($lead['user_email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 align-middle text-light" style="max-width:300px;">
                                    "<?= htmlspecialchars($lead['query']) ?>"
                                </td>
                                <td class="py-3 px-4 align-middle text-center">
                                    <?php if($lead['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> New Lead</span>
                                    <?php elseif($lead['status'] === 'contacted'): ?>
                                        <span class="badge bg-info"><i class="fas fa-check"></i> Contacted</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-trophy"></i> Converted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 align-middle text-center">
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['user_phone']) ?>?text=<?= urlencode("Hi " . $lead['user_name'] . ", I received your inquiry on BizNexus regarding " . $lead['category'] . ".") ?>" target="_blank" class="btn btn-sm btn-success shadow-sm rounded-pill px-3">
                                        <i class="fab fa-whatsapp"></i> Chat
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
