<?php
function awardCoins($pdo, $user_id, $amount, $description) {
    try {
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) return false;

        $new_balance = $user['coins'] + $amount;

        $update = $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?");
        $update->execute([$new_balance, $user_id]);

        $log = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, balance_after, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        $log->execute([$user_id, $amount, $new_balance, $description]);

        return $new_balance;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Send a notification to a specific user.
 */
function sendNotification($pdo, $user_id, $title, $message, $type = 'info') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$user_id, $title, $message, $type]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("sendNotification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get count of unread notifications for a user.
 */
function getUnreadCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Format a timestamp into a human-readable "time ago" string.
 */
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function sanitize($str) {
    if (is_array($str)) {
        return array_map('sanitize', $str);
    }
    $str = trim($str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return $str;
}

function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
        return 'N/A';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format('d M Y, h:i A');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

function generateInvoiceNumber($pdo) {
    try {
        $year = date('Y');
        $month = date('m');
        $prefix = 'BNX-' . $year . $month . '-';

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE invoice_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        $row = $stmt->fetch();
        $count = $row ? (int)$row['count'] : 0;

        $sequence = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        $invoice_number = $prefix . $sequence;

        $check = $pdo->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
        $check->execute([$invoice_number]);
        if ($check->fetch()) {
            $sequence = str_pad($count + 2, 5, '0', STR_PAD_LEFT);
            $invoice_number = $prefix . $sequence;
        }

        return $invoice_number;
    } catch (PDOException $e) {
        return 'BNX-' . date('YmdHis') . '-' . rand(1000, 9999);
    }
}
?>