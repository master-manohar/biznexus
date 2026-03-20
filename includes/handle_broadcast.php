<?php
session_start();
require_once 'db.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $gid = (int)$_POST['group_id'];
    $msg = trim($_POST['message']);

    // Verify user is president of this group
    $stmt = $pdo->prepare("SELECT 1 FROM group_roles WHERE user_id = ? AND group_id = ? AND role = 'president'");
    $stmt->execute([$uid, $gid]);
    
    if ($stmt->fetch() && !empty($msg)) {
        // Send notification to all group members
        $stmt = $pdo->prepare("SELECT id FROM users WHERE group_id = ?");
        $stmt->execute([$gid]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $prep = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'groups', ?)");
        foreach ($members as $mid) {
            $prep->execute([$mid, "📢 Group Announcement from your President: " . $msg]);
        }
        
        header("Location: /dashboard/index.php?success=broadcast_sent");
    } else {
        header("Location: /dashboard/index.php?error=unauthorized");
    }
} else {
    header("Location: /dashboard/index.php");
}
?>
