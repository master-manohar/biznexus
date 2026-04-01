<?php
/**
 * BizFeed/index.php
 * Premium B2B Social Feed for BizNexus
 */
$page_title = 'BizFeed — B2B Engagement';
require_once __DIR__ . '/includes/layout_start.php';
require_once __DIR__ . '/includes/auth_check.php';

$uid = (int)$_SESSION['user_id'];

// Fetch posts with user info and business logo/category
$stmt = $pdo->prepare("
    SELECT p.*, u.name, bp.category, bp.logo as profile_pic,
           (SELECT COUNT(*) FROM bizfeed_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM bizfeed_comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM bizfeed_likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM bizfeed_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmt->execute([$uid]);
$posts = $stmt->fetchAll();
?>

<style>
.bizfeed-container { max-width: 650px; margin: 0 auto; padding: 20px 0; }
.create-post-card { background: #13131a; border: 1px solid #2a2a3a; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
.post-input { background: #0d0d16; border: 1.5px solid #2a2a3a; border-radius: 14px; color: #fff; padding: 15px; width: 100%; transition: 0.3s; resize: none; font-size: 1rem; }
.post-input:focus { border-color: #FFD700; outline: none; box-shadow: 0 0 15px rgba(255, 215, 0, 0.1); }

.feed-item { background: #13131a; border: 1px solid #1e1e2e; border-radius: 20px; padding: 0; margin-bottom: 25px; overflow: hidden; transition: 0.2s; }
.feed-header { padding: 15px 20px; display: flex; align-items: center; gap: 12px; }
.author-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #FFD700; }
.author-info h6 { margin: 0; font-weight: 700; font-size: 0.95rem; color: #fff; }
.author-info span { font-size: 0.75rem; color: #8888aa; }

.post-content { padding: 0 20px 15px; font-size: 1rem; line-height: 1.6; color: #e8e8f5; }
.post-image { width: 100%; max-height: 500px; object-fit: cover; border-top: 1px solid #1e1e2e; border-bottom: 1px solid #1e1e2e; }

.post-type-badge { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 3px 10px; border-radius: 20px; margin-left: 8px; }
.type-update { background: rgba(0,212,255,0.1); color: #00d4ff; border: 1px solid rgba(0,212,255,0.3); }
.type-need   { background: rgba(255,75,110,0.1); color: #ff4b6e; border: 1px solid rgba(255,75,110,0.3); }
.type-offer  { background: rgba(0,232,122,0.1); color: #00e87a; border: 1px solid rgba(0,232,122,0.3); }

.post-actions { padding: 12px 20px; display: flex; align-items: center; gap: 20px; border-top: 1px solid #1e1e2e; }
.action-btn { background: none; border: none; color: #8888aa; font-size: 1.1rem; display: flex; align-items: center; gap: 7px; cursor: pointer; transition: 0.2s; padding: 8px 12px; border-radius: 10px; }
.action-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
.action-btn.active { color: #f91979; }
.action-btn.share-btn:hover { color: #25D366; }

.comment-section { background: #0d0d16; padding: 15px 20px; border-top: 1px solid #1e1e2e; }
</style>

<div class="bizfeed-container">
    <!-- Post Creation Box -->
    <div class="create-post-card">
        <form id="postForm" enctype="multipart/form-data">
            <textarea name="content" class="post-input" placeholder="Share a business update, Need or Offer..." rows="2" required></textarea>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="d-flex gap-2">
                    <select name="post_type" class="form-select form-select-sm bg-dark border-secondary text-light rounded-pill" style="font-size: 0.75rem;">
                        <option value="update">Update</option>
                        <option value="need">Need</option>
                        <option value="offer">Offer</option>
                    </select>
                    <label class="btn btn-outline-secondary btn-sm rounded-pill px-3" style="font-size: 0.75rem; border-color: #2a2a3a;">
                        <i class="fas fa-image me-1"></i> Photo
                        <input type="file" name="post_image" hidden accept="image/*" id="postImageInput">
                    </label>
                </div>
                <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4 btn-sm">Post</button>
            </div>
            <div id="imagePreview" class="mt-2 text-warning font-monospace" style="font-size: 0.7rem;"></div>
        </form>
    </div>

    <!-- The Feed -->
    <div id="mainFeed">
        <?php foreach ($posts as $p): ?>
            <div class="feed-item" id="post-<?= $p['id'] ?>">
                <div class="feed-header">
                    <img src="<?= $p['profile_pic'] ? '/'.$p['profile_pic'] : 'https://ui-avatars.com/api/?name='.urlencode($p['name']) ?>" class="author-avatar">
                    <div class="author-info flex-grow-1">
                        <div class="d-flex align-items-center">
                            <h6><?= htmlspecialchars($p['name']) ?></h6>
                            <span class="post-type-badge type-<?= $p['post_type'] ?>"><?= $p['post_type'] ?></span>
                        </div>
                        <span><?= htmlspecialchars($p['category'] ?? 'Business Member') ?> · <?= date('M d, H:i', strtotime($p['created_at'])) ?></span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link text-secondary p-0" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v" style="font-size:0.8rem;"></i></button>
                        <ul class="dropdown-menu dropdown-menu-dark" style="font-size:0.75rem;">
                            <?php if ($p['user_id'] == $uid): ?>
                                <li><a class="dropdown-item text-danger" href="javascript:deletePost(<?= $p['id'] ?>)">Delete Post</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#">Report</a></li>
                        </ul>
                    </div>
                </div>

                <div class="post-content">
                    <?= nl2br(htmlspecialchars($p['content'])) ?>
                </div>

                <?php if ($p['image_path']): ?>
                    <img src="/<?= $p['image_path'] ?>" class="post-image" onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="post-actions">
                    <button class="action-btn <?= $p['user_liked'] ? 'active' : '' ?>" onclick="toggleLike(<?= $p['id'] ?>)">
                        <i class="<?= $p['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                        <span id="like-count-<?= $p['id'] ?>"><?= $p['like_count'] ?></span>
                    </button>
                    <button class="action-btn" onclick="toggleComments(<?= $p['id'] ?>)">
                        <i class="far fa-comment"></i>
                        <span><?= $p['comment_count'] ?></span>
                    </button>
                    <button class="action-btn share-btn" onclick="shareToWhatsApp(<?= $p['id'] ?>, '<?= urlencode(substr($p['content'], 0, 100)) ?>')">
                        <i class="fas fa-share"></i>
                    </button>
                </div>

                <!-- Comment Section -->
                <div id="comments-<?= $p['id'] ?>" class="comment-section" style="display: none;">
                    <div id="comments-list-<?= $p['id'] ?>" class="mb-3"></div>
                    <div class="d-flex gap-2">
                        <input type="text" id="comment-input-<?= $p['id'] ?>" class="form-control form-control-sm bg-dark border-secondary text-white rounded-pill" placeholder="Write a comment...">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="submitComment(<?= $p['id'] ?>)">Send</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function deletePost(postId) {
    if(!confirm("Delete this post?")) return;
    fetch(`bizfeed_actions.php?action=delete_post&post_id=${postId}`)
    .then(r => r.json())
    .then(data => { if(data.success) document.getElementById(`post-${postId}`).remove(); });
}

function toggleLike(postId) {
    fetch('bizfeed_actions.php?action=like&post_id=' + postId)
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            const btn = document.querySelector(`#post-${postId} .action-btn i.fa-heart`).parentElement;
            const count = document.getElementById(`like-count-${postId}`);
            count.innerText = data.new_count;
            btn.classList.toggle('active', data.liked);
            btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
        }
    });
}

function shareToWhatsApp(postId, text) {
    const url = encodeURIComponent(`https://biznexus.in/bizfeed.php#post-${postId}`);
    const msg = text + "%0A%0ASee more on BizNexus: " + url;
    window.open(`https://wa.me/?text=${msg}`, '_blank');
}

function toggleComments(postId) {
    const section = document.getElementById(`comments-${postId}`);
    if (section.style.display === 'none') {
        section.style.display = 'block';
        loadComments(postId);
    } else {
        section.style.display = 'none';
    }
}

function loadComments(postId) {
    const list = document.getElementById(`comments-list-${postId}`);
    list.innerHTML = '<div style="font-size:0.75rem;color:#666;">Loading...</div>';
    fetch(`bizfeed_actions.php?action=get_comments&post_id=${postId}`)
    .then(r => r.json())
    .then(data => {
        list.innerHTML = '';
        if(data.comments.length === 0) list.innerHTML = '<div style="font-size:0.75rem;color:#666;">No comments yet.</div>';
        data.comments.forEach(c => {
            const avatar = c.profile_pic ? '/' + c.profile_pic : `https://ui-avatars.com/api/?name=${encodeURIComponent(c.name)}`;
            list.innerHTML += `
                <div class="d-flex gap-2 mb-2">
                    <img src="${avatar}" style="width:25px;height:25px;border-radius:50%;object-fit:cover;">
                    <div style="background:#1a1a28;padding:8px 12px;border-radius:12px;flex:1;">
                        <div style="font-size:0.75rem;font-weight:700;color:#fff;">${c.name}</div>
                        <div style="font-size:0.8rem;color:#ccc;">${c.comment_text}</div>
                    </div>
                </div>
            `;
        });
    });
}

function submitComment(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    const text = input.value.trim();
    if(!text) return;

    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('comment_text', text);

    fetch('bizfeed_actions.php?action=comment', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            input.value = '';
            loadComments(postId);
        }
    });
}

document.getElementById('postImageInput').onchange = function() {
    const file = this.files[0];
    if(file) document.getElementById('imagePreview').innerText = 'Selected: ' + file.name;
};

document.getElementById('postForm').onsubmit = function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch('bizfeed_actions.php?action=post', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.success) { window.location.reload(); }
        else { alert("Error: " + data.message); btn.innerHTML = 'Post'; btn.disabled = false; }
    });
}
</script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
