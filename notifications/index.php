<?php
$page_title = 'Notifications - BizNexus';
require_once '../includes/layout_start.php';

// Mark all read if requested
if (isset($_GET['markread'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user_id]);
    header('Location: /notifications/index.php');
    exit;
}

$page = max(1, intval($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$st = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$st->execute([$user_id]);
$notifs = $st->fetchAll();

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=?");
$total_stmt->execute([$user_id]);
$total = $total_stmt->fetchColumn();

// unread_notifs is already fetched in layout_start.php as $unread_notifs
?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">🔔 Notifications</h4>
            <p class="text-muted small mb-0"><?= $total ?> total • <?= $unread_notifs ?> unread</p>
        </div>
        <?php if ($unread_notifs > 0): ?>
            <a href="?markread=1" class="btn btn-warning btn-sm fw-bold rounded-pill px-3">Mark All Read</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
        <div class="text-center py-5">
            <div class="display-1 mb-3">🔔</div>
            <h5 class="text-muted">No notifications yet</h5>
            <p class="text-secondary small">Your activity notifications will appear here</p>
        </div>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifs as $n):
                $icons = [
                    'success' => '✅',
                    'info' => 'ℹ️',
                    'warning' => '⚠️',
                    'error' => '❌',
                    'lead' => '🎯',
                    'coin' => '🪙',
                    'referral' => '🤝',
                    'meeting' => '📅',
                    'news' => '📰',
                    'connection' => '🔗',
                    'coin_milestone' => '🏆',
                    'group_update' => '👥'
                ];
                $icon = $icons[$n['type']] ?? '🔔';
                $is_unread = !$n['is_read'];
            ?>
                <div class="card mb-2 border-0 shadow-sm <?= $is_unread ? 'border-start border-warning border-4' : '' ?>" style="background: var(--card-bg, #1a1a24);">
                    <div class="card-body d-flex gap-3 align-items-start p-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(255, 215, 0, 0.1);">
                            <?= $icon ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($n['title'] ?? '') ?></h6>
                                <span class="text-muted tiny"><?= timeAgo($n['created_at']) ?></span>
                            </div>
                            <p class="text-secondary small mb-0"><?= htmlspecialchars($n['message'] ?? '') ?></p>
                        </div>
                        <?php if ($is_unread): ?>
                            <div class="bg-warning rounded-circle" style="width: 8px; height: 8px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total > $limit): ?>
            <nav class="mt-4">
                <ul class="pagination pagination-sm justify-content-center">
                    <?php
                    $total_pages = ceil($total / $limit);
                    for ($i = 1; $i <= $total_pages; $i++):
                    ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/layout_end.php'; ?>
