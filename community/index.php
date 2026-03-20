<?php
require_once '../includes/layout_start.php';
global $pdo;
$uid = (int)$_SESSION['user_id'];

// Handle New Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    $type = $_POST['post_type'] ?? 'general';
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, content, type, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->execute([$uid, $content, $type]);
        header("Location: index.php?success=1");
        exit;
    }
}

// Handle Like
if (isset($_GET['like'])) {
    $pid = (int)$_GET['like'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO community_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$pid, $uid]);
    // Update count
    $pdo->prepare("UPDATE community_posts SET likes = (SELECT COUNT(*) FROM community_likes WHERE post_id = ?) WHERE id = ?")->execute([$pid, $pid]);
    header("Location: index.php");
    exit;
}

// Fetch Posts
$stmt = $pdo->query("
    SELECT p.*, u.name as user_name, u.avatar, bp.business_name,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id AND user_id = $uid) as has_liked
    FROM community_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 30
");
$posts = $stmt->fetchAll();
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2 class="fw-bold mb-4">Feed 🌐</h2>

            <!-- Post Creation Card -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <textarea name="post_content" class="form-control bg-black border-secondary text-white" rows="3" placeholder="What's happening in your business? Share a win, an offer, or a requirement..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <select name="post_type" class="form-select form-select-sm bg-black border-secondary text-white w-auto">
                                <option value="general">📢 General Update</option>
                                <option value="win">🏆 Big Win</option>
                                <option value="offer">💰 Special Offer</option>
                                <option value="looking_for">🔍 Looking For</option>
                                <option value="question">❓ Question</option>
                            </select>
                            <button type="submit" class="btn btn-gold px-4">Post</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Feed -->
            <?php foreach($posts as $p): ?>
            <div class="card bg-dark border-secondary mb-3 shadow-sm post-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-gold-subtle text-gold d-flex align-items-center justify-content-center fw-bold me-3" style="width:45px; height:45px; border-radius:50%; font-size:1.2rem;">
                            <?= strtoupper(substr($p['user_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-white"><?= htmlspecialchars($p['user_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($p['business_name'] ?: 'Member') ?> • <?= timeAgo($p['created_at']) ?></div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-secondary-subtle text-secondary small text-uppercase"><?= str_replace('_', ' ', $p['type']) ?></span>
                        </div>
                    </div>
                    <p class="text-light mb-4" style="font-size: 1.05rem; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($p['content'])) ?>
                    </p>
                    <div class="d-flex gap-4 border-top border-secondary pt-3">
                        <a href="?like=<?= $p['id'] ?>" class="text-decoration-none <?= $p['has_liked'] ? 'text-gold' : 'text-muted' ?> hover-light d-flex align-items-center gap-1">
                            <span><?= $p['has_liked'] ? '❤️' : '🤍' ?></span>
                            <span class="small fw-bold"><?= $p['like_count'] ?> Likes</span>
                        </a>
                        <a href="#" class="text-decoration-none text-muted hover-light d-flex align-items-center gap-1">
                            <span>💬</span>
                            <span class="small fw-bold"><?= $p['comment_count'] ?> Comments</span>
                        </a>
                        <a href="#" class="text-decoration-none text-muted hover-light d-flex align-items-center gap-1 ms-auto">
                            <span>🔗</span>
                            <span class="small fw-bold">Share</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($posts)): ?>
                <div class="text-center py-5">
                    <div class="display-1 opacity-25">🌪️</div>
                    <h4 class="text-muted">Silence in the community...</h4>
                    <p class="text-muted">Be the first to break the ice and post something above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.post-card { transition: transform 0.2s; }
.post-card:hover { transform: translateY(-2px); border-color: var(--gold) !important; }
.hover-light:hover { color: #fff !important; }
</style>

<?php require_once '../includes/layout_end.php'; ?>
