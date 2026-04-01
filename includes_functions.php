<?php
// /includes_functions.php

if (!function_exists('awardCoins')) {
function awardCoins($pdo, $user_id, $amount, $description) {
    try {
        // 1. Update Users table (legacy/backup)
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")->execute([(int)$amount, (int)$user_id]);
        
        // 2. Fetch new balance from users table (Source of Truth)
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->execute([(int)$user_id]);
        $new_balance = (int)$stmt->fetchColumn() ?: 0;

        $type = ($amount >= 0) ? 'earn' : 'spend';

        // 3. Sync voocoin_balances table (UI Source)
        $chk = $pdo->prepare("SELECT id FROM voocoin_balances WHERE user_id = ?");
        $chk->execute([(int)$user_id]);
        if ($chk->fetch()) {
            if ($amount >= 0) {
                $upd = $pdo->prepare("UPDATE voocoin_balances SET balance = ?, total_earned = total_earned + ?, updated_at = NOW() WHERE user_id = ?");
                $upd->execute([$new_balance, (int)$amount, (int)$user_id]);
            } else {
                $upd = $pdo->prepare("UPDATE voocoin_balances SET balance = ?, total_spent = total_spent + ?, updated_at = NOW() WHERE user_id = ?");
                $upd->execute([$new_balance, (int)abs($amount), (int)$user_id]);
            }
        } else {
            $te = ($amount >= 0) ? (int)$amount : 0;
            $ts = ($amount < 0) ? (int)abs($amount) : 0;
            $pdo->prepare("INSERT INTO voocoin_balances (user_id, balance, total_earned, total_spent, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())")
                ->execute([(int)$user_id, $new_balance, $te, $ts]);
        }
        
        // Final sanity check - update balance one more time to be absolutely sure
        $pdo->prepare("UPDATE voocoin_balances SET balance = ? WHERE user_id = ?")->execute([$new_balance, (int)$user_id]);

        // 4. Log Transaction with balance_after
        $log = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, balance_after, type, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $log->execute([(int)$user_id, (int)$amount, $new_balance, $type, $description]);
        
        return $new_balance;
    } catch (PDOException $e) { return false; }
}
}

if (!function_exists('sendNotification')) {
function sendNotification($pdo, $user_id, $title, $message, $type = 'info') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$user_id, $title, $message, $type]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) { return false; }
}
}

if (!function_exists('getUnreadCount')) {
function getUnreadCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) { return 0; }
}
}

if (!function_exists('timeAgo')) {
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y'=>'year','m'=>'month','w'=>'week','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second');
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
        else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
}

if (!function_exists('sanitize')) {
function sanitize($str) {
    if (is_array($str)) return array_map('sanitize', $str);
    return htmlspecialchars(stripslashes(trim($str)), ENT_QUOTES, 'UTF-8');
}
}

if (!function_exists('formatDate')) {
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00') return 'N/A';
    try { $dt = new DateTime($date); return $dt->format('d M Y, h:i A'); } 
    catch (Exception $e) { return 'Invalid Date'; }
}
}

if (!function_exists('generateInvoiceNumber')) {
function generateInvoiceNumber($pdo) {
    try {
        $prefix = 'BNX-' . date('Ym') . '-';
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE invoice_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = $stmt->fetchColumn() ?: 0;
        return $prefix . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    } catch (PDOException $e) { return 'BNX-' . date('YmdHis') . '-' . rand(1000, 9999); }
}
}

if (!function_exists('calculateTrustScore')) {
function calculateTrustScore($pdo, $uid) {
    try {
        $u = $pdo->prepare("SELECT email_verified, kyc_verified, two_factor, profile_complete, is_verified FROM users WHERE id = ?");
        $u->execute([$uid]);
        $user = $u->fetch();
        
        $bp = $pdo->prepare("SELECT gst_verified, is_verified FROM business_profiles WHERE user_id = ?");
        $bp->execute([$uid]);
        $profile = $bp->fetch();

        $score = 10; // Base score
        if ($user['email_verified'])    $score += 150;
        if ($user['kyc_verified'])      $score += 300;
        if ($user['two_factor'])        $score += 100;
        if ($user['profile_complete'])  $score += 100;
        if ($user['is_verified'])       $score += 100; // Global manual verification

        if ($profile && $profile['gst_verified']) $score += 200;
        if ($profile && $profile['is_verified'])  $score += 40;

        // Cap at 1000
        $final = min(1000, $score);
        
        // Update DB
        $pdo->prepare("UPDATE users SET trust_score = ?, trust_updated = NOW() WHERE id = ?")->execute([$final, $uid]);
        
        return $final;
    } catch (Exception $e) { return 10; }
}
}

if (!function_exists('getTrustLevel')) {
function getTrustLevel($score) {
    if ($score >= 800) return ['label' => 'Elite Partner (Highly Secure)', 'badge' => '🏆', 'color' => '#FFD700', 'status' => 'Excellent'];
    if ($score >= 500) return ['label' => 'Verified Expert (Secure)', 'badge' => '🛡️', 'color' => '#00e87a', 'status' => 'Good'];
    if ($score >= 200) return ['label' => 'Trusted Member', 'badge' => '✅', 'color' => '#4488ff', 'status' => 'Fair'];
    return ['label' => 'Probationary (Needs Setup)', 'badge' => '🌱', 'color' => '#8888aa', 'status' => 'Low'];
}
}

if (!function_exists('getDistance')) {
function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 9999;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 60 * 1.1515 * 1.609344;
}
}

if (!function_exists('getConsultantTips')) {
/**
 * Generates personalized AI business tips based on profile status.
 */
function getConsultantTips($pdo, $uid) {
    try {
        $tips = [];
        
        // Fetch user and profile
        $st = $pdo->prepare("SELECT u.trust_score, u.coins, u.whatsapp, bp.category, bp.description FROM users u LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
        $st->execute([$uid]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        
        if (!$u) return [];

        // 1. Trust Score Tips
        if (($u['trust_score'] ?? 0) < 300) {
            $tips[] = ["icon" => "🛡️", "text" => "Your Security Rating is Low. Enable 2FA or Verify Email to win more customer trust.", "link" => "/profile/edit.php", "points" => "+150+"];
        }
        
        // 2. Profile Quality Tips
        if (empty($u['category'])) {
            $tips[] = ["icon" => "🔍", "text" => "No category set! You are invisible to SEO searches. Set your category now.", "link" => "/profile/edit.php", "points" => "Critical"];
        }
        
        if (empty($u['description']) || strlen(trim($u['description'])) < 50) {
            $tips[] = ["icon" => "✍️", "text" => "Your business description is short. Add more details to help our AI rank you higher.", "link" => "/profile/edit.php", "points" => "+100"];
        }

        if (empty($u['whatsapp'])) {
            $tips[] = ["icon" => "💬", "text" => "WhatsApp is missing. Customers can't reach you from search results.", "link" => "/profile/edit.php", "points" => "Leads"];
        }

        // 3. Growth Tips
        if (($u['coins'] ?? 0) < 50) {
            $tips[] = ["icon" => "🪙", "text" => "Low on VooCoins? Complete your Profil to unlock more platform reach.", "link" => "/profile/edit.php", "points" => "+100"];
        }

        return $tips;
    } catch (Exception $e) { return []; }
}
}

/**
 * publishToInstagram
 * Publishes a single image post to Instagram via the Facebook Graph API.
 */
function publishToInstagram($image_url, $caption, $access_token, $ig_user_id) {
    if (!$access_token || !$ig_user_id) return ['success' => false, 'error' => 'Missing Credentials'];
    
    // 1. Create Media Container
    $container_url = "https://graph.facebook.com/v18.0/$ig_user_id/media";
    $params = [
        'image_url' => $image_url,
        'caption' => $caption,
        'access_token' => $access_token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $container_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if (empty($data['id'])) {
        return ['success' => false, 'error' => $response];
    }
    
    $creation_id = $data['id'];
    
    // 2. Publish Media Container
    $publish_url = "https://graph.facebook.com/v18.0/$ig_user_id/media_publish";
    $publish_params = [
        'creation_id' => $creation_id,
        'access_token' => $access_token
    ];
    
    curl_setopt($ch, CURLOPT_URL, $publish_url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($publish_params));
    $pub_response = curl_exec($ch);
    $pub_data = json_decode($pub_response, true);
    curl_close($ch);
    
    if (empty($pub_data['id'])) {
        return ['success' => false, 'error' => 'Publication failed: ' . $pub_response];
    }
    
    return ['success' => true, 'id' => $pub_data['id']];
}

/**
 * publishReelToInstagram
 * Publishes a Video Reel to Instagram with status polling for processing.
 */
function publishReelToInstagram($video_url, $caption, $access_token, $ig_user_id) {
    if (!$access_token || !$ig_user_id) return ['success' => false, 'error' => 'Missing Credentials'];
    
    // 1. Create Reels Container
    $container_url = "https://graph.facebook.com/v18.0/$ig_user_id/media";
    $params = [
        'media_type' => 'REELS',
        'video_url' => $video_url,
        'caption' => $caption,
        'access_token' => $access_token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $container_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if (empty($data['id'])) {
        return ['success' => false, 'error' => 'Container creation failed: ' . $response];
    }
    
    $creation_id = $data['id'];
    
    // 2. Poll for Processing Status
    $status_url = "https://graph.facebook.com/v18.0/$creation_id?fields=status_code&access_token=$access_token";
    $max_attempts = 12; // Wait up to 2 minutes
    $attempts = 0;
    $is_ready = false;

    while ($attempts < $max_attempts) {
        $attempts++;
        sleep(10); 
        
        $ch_st = curl_init();
        curl_setopt($ch_st, CURLOPT_URL, $status_url);
        curl_setopt($ch_st, CURLOPT_RETURNTRANSFER, true);
        $st_response = curl_exec($ch_st);
        $st_data = json_decode($st_response, true);
        curl_close($ch_st);
        
        if (($st_data['status_code'] ?? '') === 'FINISHED') {
            $is_ready = true;
            break;
        }
        
        if (($st_data['status_code'] ?? '') === 'ERROR') {
            return ['success' => false, 'error' => 'Video processing error: ' . $st_response];
        }
    }

    if (!$is_ready) {
        return ['success' => false, 'error' => 'Video processing timed out.'];
    }

    // 3. Publish Reels
    $publish_url = "https://graph.facebook.com/v18.0/$ig_user_id/media_publish";
    $publish_params = [
        'creation_id' => $creation_id,
        'access_token' => $access_token
    ];
    
    $ch_pub = curl_init();
    curl_setopt($ch_pub, CURLOPT_URL, $publish_url);
    curl_setopt($ch_pub, CURLOPT_POST, true);
    curl_setopt($ch_pub, CURLOPT_POSTFIELDS, http_build_query($publish_params));
    curl_setopt($ch_pub, CURLOPT_RETURNTRANSFER, true);
    $pub_response = curl_exec($ch_pub);
    $pub_data = json_decode($pub_response, true);
    curl_close($ch_pub);
    
    if (empty($pub_data['id'])) {
        return ['success' => false, 'error' => 'Publication failed: ' . $pub_response];
    }
    
    return ['success' => true, 'id' => $pub_data['id']];
}