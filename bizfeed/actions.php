<?php
/**
 * BizFeed/actions.php
 * Logic for posting, liking, and commenting
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes_functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uid = (int)($_SESSION['user_id'] ?? 0);
if (!$uid) die(json_encode(['success' => false, 'message' => 'Unauthorized']));

$action = $_GET['action'] ?? '';

if ($action === 'post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $type    = $_POST['post_type'] ?? 'update';
    $img_path = null;

    if (empty($content)) die(json_encode(['id' => 0, 'success' => false, 'message' => 'Content required']));

    // Handle Image Upload
    if (!empty($_FILES['post_image']['name'])) {
        $target_dir = __DIR__ . "/uploads/bizfeed/";
        if (!is_dir($target_dir)) @mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
        $file_name = "post_" . time() . "_" . $uid . "." . $ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
            $img_path = "uploads/bizfeed/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO bizfeed_posts (user_id, content, image_path, post_type) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$uid, $content, $img_path, $type])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
}

if ($action === 'delete_post') {
    $post_id = (int)$_GET['post_id'];
    $stmt = $pdo->prepare("UPDATE bizfeed_posts SET status = 'deleted' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$post_id, $uid])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

if ($action === 'like') {
    $post_id = (int)$_GET['post_id'];
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM bizfeed_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $uid]);
    $liked = $stmt->fetch();

    if ($liked) {
        $pdo->prepare("DELETE FROM bizfeed_likes WHERE id = ?")->execute([$liked['id']]);
        $is_liked = false;
    } else {
        $pdo->prepare("INSERT INTO bizfeed_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $uid]);
        $is_liked = true;
    }

    // Get new count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bizfeed_likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'liked' => $is_liked, 'new_count' => (int)$count]);
}

if ($action === 'comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = (int)$_POST['post_id'];
    $comment = trim($_POST['comment_text'] ?? '');

    if (empty($comment)) die(json_encode(['success' => false, 'message' => 'Comment cannot be empty']));

    $stmt = $pdo->prepare("INSERT INTO bizfeed_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
    if ($stmt->execute([$post_id, $uid, $comment])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
}

if ($action === 'get_comments') {
    $post_id = (int)$_GET['post_id'];
    $stmt = $pdo->prepare("
        SELECT c.*, u.name, bp.logo as profile_pic
        FROM bizfeed_comments c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN business_profiles bp ON u.id = bp.user_id
        WHERE c.post_id = ? AND c.status = 'active'
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'comments' => $comments]);
}
?>
