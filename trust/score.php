<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: /auth/login.php'); exit; }
function getDB(){
    $c=[['localhost','u175452495_biznexus','u175452495_bizuser','Biz@9990'],['localhost','u175452495_biznexus','u175452495_voo_user','Vooschool@123'],['localhost','u175452495_biznexus','u175452495','Biz@9990']];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
$uid = (int)$_SESSION['user_id'];
$pdo = getDB();
if(!$pdo) die('DB Error');

// Ensure trust columns exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS trust_score INT DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS trust_badge VARCHAR(50) DEFAULT 'New'");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS trust_updated DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_verified TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS response_rate INT DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS avg_rating DECIMAL(3,2) DEFAULT 0.00");
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_ratings (id INT AUTO_INCREMENT PRIMARY KEY,lead_id INT,rated_user_id INT,rater_user_id INT,rating TINYINT NOT NULL,review TEXT,created_at DATETIME DEFAULT NOW())");
} catch(Exception $e){}

/**
 * TRUST SCORE ALGORITHM (0-100 points)
 * KYC Verified:     0 or 25 pts
 * Response Rate:    0-20 pts (% of leads responded within 2hr)
 * Avg Rating:       0-20 pts (1-5 stars mapped to 0-20)
 * Profile Complete: 0-20 pts (photo+bio+category+city+phone = 4pt each)
 * Activity Score:   0-10 pts (logins + posts + referrals in last 30 days)
 * Account Age:      0-5  pts (1pt per 60 days, max 5)
 */
function calculateTrustScore(int $user_id, $pdo): array {
    $score = 0; $breakdown = [];

    // Load user
    $u = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $u->execute([$user_id]);
    $user = $u->fetch();
    if(!$user) return ['score'=>0,'badge'=>'New','breakdown'=>[]];

    // 1. KYC (25 pts)
    $kyc = ($user['kyc_verified']??0) ? 25 : 0;
    $score += $kyc;
    $breakdown['kyc'] = ['label'=>'KYC Verified','points'=>$kyc,'max'=>25,'tip'=>$kyc?'✅ Verified':'Upload GST/Aadhar to earn 25 pts'];

    // 2. Response Rate (0-20 pts)
    $leads_assigned = $pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE member_id=?");
    $leads_assigned->execute([$user_id]);
    $total_leads = $leads_assigned->fetch()['c'] ?? 0;
    $leads_responded = 0;
    try {
        $lr = $pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE member_id=? AND status IN ('claimed','closed','contacted')");
        $lr->execute([$user_id]);
        $leads_responded = $lr->fetch()['c'] ?? 0;
    }catch(Exception $e){}
    $rr = $total_leads > 0 ? min(100, round($leads_responded/$total_leads*100)) : 0;
    $rr_pts = round($rr/100*20);
    $score += $rr_pts;
    $breakdown['response'] = ['label'=>'Response Rate','points'=>$rr_pts,'max'=>20,'tip'=>"{$rr}% response rate ({$leads_responded}/{$total_leads} leads)"];

    // 3. Average Rating (0-20 pts)
    $rq = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM lead_ratings WHERE rated_user_id=?");
    $rq->execute([$user_id]);
    $rd = $rq->fetch();
    $avg_rating = round($rd['avg']??0, 2);
    $rating_count = $rd['cnt']??0;
    $rating_pts = $avg_rating > 0 ? round(($avg_rating-1)/4*20) : 0;
    $score += $rating_pts;
    $breakdown['rating'] = ['label'=>'Member Ratings','points'=>$rating_pts,'max'=>20,'tip'=>$rating_count>0?"{$avg_rating}⭐ from {$rating_count} reviews":'No ratings yet'];

    // 4. Profile Completeness (0-20 pts, 4 pts each)
    $profile_pts = 0;
    $profile_items = [];
    $fields = ['photo'=>'Profile Photo','bio'=>'Business Bio','category'=>'Business Category','city'=>'City','phone'=>'Phone Number'];
    foreach($fields as $field=>$label){
        $val = $user[$field]??'';
        $done = !empty($val) && $val !== 'N/A';
        if($done) $profile_pts += 4;
        $profile_items[$field] = ['label'=>$label,'done'=>$done];
    }
    $score += $profile_pts;
    $breakdown['profile'] = ['label'=>'Profile Complete','points'=>$profile_pts,'max'=>20,'items'=>$profile_items];

    // 5. Activity (0-10 pts)
    $activity_pts = 0;
    try {
        // Referrals sent last 30 days (max 4 pts)
        $refs = $pdo->prepare("SELECT COUNT(*) as c FROM referrals WHERE sender_id=? AND created_at > DATE_SUB(NOW(),INTERVAL 30 DAY)");
        $refs->execute([$user_id]);
        $ref_count = $refs->fetch()['c']??0;
        $activity_pts += min(4, $ref_count);
        // Community posts (max 3 pts)
        $posts = $pdo->prepare("SELECT COUNT(*) as c FROM community_posts WHERE user_id=? AND created_at > DATE_SUB(NOW(),INTERVAL 30 DAY)");
        $posts->execute([$user_id]);
        $post_count = $posts->fetch()['c']??0;
        $activity_pts += min(3, $post_count);
        // Meetings (max 3 pts)
        $meets = $pdo->prepare("SELECT COUNT(*) as c FROM meetings WHERE (created_by=? OR attendee_id=?) AND created_at > DATE_SUB(NOW(),INTERVAL 30 DAY)");
        $meets->execute([$user_id,$user_id]);
        $meet_count = $meets->fetch()['c']??0;
        $activity_pts += min(3, $meet_count);
    }catch(Exception $e){}
    $score += $activity_pts;
    $breakdown['activity'] = ['label'=>'30-Day Activity','points'=>$activity_pts,'max'=>10,'tip'=>'Based on referrals, posts, meetings this month'];

    // 6. Account Age (0-5 pts)
    $days_old = (int)((time()-strtotime($user['created_at']??'now'))/86400);
    $age_pts = min(5, (int)($days_old/60));
    $score += $age_pts;
    $breakdown['age'] = ['label'=>'Account Age','points'=>$age_pts,'max'=>5,'tip'=>"Account is {$days_old} days old"];

    // Calculate badge
    $badge = match(true){
        $score >= 90 => 'Elite',
        $score >= 75 => 'Trusted',
        $score >= 55 => 'Rising',
        $score >= 30 => 'Active',
        default      => 'New'
    };

    // Save to DB
    $pdo->prepare("UPDATE users SET trust_score=?,trust_badge=?,trust_updated=NOW(),avg_rating=?,response_rate=? WHERE id=?")
        ->execute([$score,$badge,$avg_rating,$rr,$user_id]);

    return ['score'=>$score,'badge'=>$badge,'breakdown'=>$breakdown,'avg_rating'=>$avg_rating,'response_rate'=>$rr];
}

$result = calculateTrustScore($uid, $pdo);
$badge_colors = ['New'=>'#555','Active'=>'#00d4ff','Rising'=>'#FFD700','Trusted'=>'#00ff88','Elite'=>'#ff6b6b'];
$badge_color = $badge_colors[$result['badge']] ?? '#555';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trust Score – BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#06060a;color:#e0e0f0;font-family:'DM Sans',sans-serif}
.sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0a0a12;border-right:1px solid #1e1e2e;display:flex;flex-direction:column;z-index:100}
.sidebar-logo{padding:22px 20px;font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:900;color:#FFD700;border-bottom:1px solid #1e1e2e}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#777;font-size:.85rem;font-weight:500;transition:.15s;text-decoration:none}
.nav-link:hover,.nav-link.active{color:#FFD700;background:rgba(255,215,0,.06)}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1e1e2e;margin-top:auto}
.main{margin-left:220px;padding:32px}
.score-circle{width:160px;height:160px;border-radius:50%;background:conic-gradient(<?=$badge_color?> <?=$result['score']?>%,#1e1e2e 0%);display:flex;align-items:center;justify-content:center;position:relative;margin:0 auto}
.score-inner{width:130px;height:130px;border-radius:50%;background:#06060a;display:flex;flex-direction:column;align-items:center;justify-content:center}
.score-num{font-family:'Syne',sans-serif;font-size:2.4rem;font-weight:900;color:<?=$badge_color?>;line-height:1}
.score-max{font-size:.75rem;color:#555}
.badge-tag{display:inline-flex;align-items:center;gap:6px;background:<?=$badge_color?>22;border:1px solid <?=$badge_color?>66;border-radius:50px;padding:6px 16px;font-size:.85rem;font-weight:700;color:<?=$badge_color?>}
.factor-card{background:#0e0e16;border:1px solid #1e1e2e;border-radius:14px;padding:20px;margin-bottom:12px}
.factor-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.factor-name{font-weight:700;font-size:.9rem}
.factor-pts{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;color:#FFD700}
.progress-track{height:6px;background:#1e1e2e;border-radius:6px;overflow:hidden}
.progress-fill{height:100%;border-radius:6px;background:linear-gradient(90deg,<?=$badge_color?>,#00ff88);transition:width 1s ease}
.tip-text{font-size:.78rem;color:#555;margin-top:6px}
.kyc-cta{background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:14px;padding:20px;text-align:center;margin-bottom:20px}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav style="flex:1;padding:12px 0">
    <a href="/dashboard/index.php" class="nav-link">🏠 Dashboard</a>
    <a href="/profile/edit.php" class="nav-link">👤 My Profile</a>
    <a href="/trust/score.php" class="nav-link active">🛡️ Trust Score</a>
    <a href="/kyc/upload.php" class="nav-link">📋 KYC</a>
    <a href="/referrals/send.php" class="nav-link">🤝 Referrals</a>
    <a href="/meetings/book.php" class="nav-link">📅 Meetings</a>
    <a href="/marketplace/index.php" class="nav-link">🏪 Marketplace</a>
    <a href="/coins/balance.php" class="nav-link">🪙 VooCoins</a>
    <a href="/groups/index.php" class="nav-link">👥 Groups</a>
    <a href="/settings/index.php" class="nav-link">⚙️ Settings</a>
  </nav>
  <div class="sidebar-footer"><a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a></div>
</div>

<div class="main">
  <div class="mb-4">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px">Trust Score 🛡️</h1>
    <p style="color:#777;font-size:.88rem">Your reputation on BizNexus — higher score = more leads dispatched to you first</p>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div style="background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:32px;text-align:center">
        <div class="score-circle" style="margin-bottom:20px">
          <div class="score-inner">
            <div class="score-num"><?=$result['score']?></div>
            <div class="score-max">/ 100</div>
          </div>
        </div>
        <div class="badge-tag" style="margin:0 auto 12px;width:fit-content">
          <?= match($result['badge']){'Elite'=>'🔥','Trusted'=>'✅','Rising'=>'📈','Active'=>'💼',default=>'🆕'} ?>
          <?=$result['badge']?> Member
        </div>
        <div style="font-size:.8rem;color:#555">Updated just now</div>
        <div style="margin-top:16px;font-size:.83rem;color:#888">Refresh this page anytime to recalculate your score</div>
      </div>
    </div>
    <div class="col-md-8">
      <?php if(!($result['breakdown']['kyc']['points']??0)): ?>
      <div class="kyc-cta">
        <div style="font-size:1.5rem;margin-bottom:8px">📋</div>
        <div style="font-weight:700;color:#FFD700;margin-bottom:4px">Unlock 25 Points!</div>
        <div style="font-size:.83rem;color:#888;margin-bottom:14px">Complete KYC verification to get your biggest single trust boost</div>
        <a href="/kyc/upload.php" style="background:#FFD700;color:#000;font-weight:800;border-radius:50px;padding:10px 24px;text-decoration:none;font-size:.85rem">Start KYC Now →</a>
      </div>
      <?php endif; ?>

      <?php foreach($result['breakdown'] as $key=>$item): $pct=$item['max']>0?round($item['points']/$item['max']*100):0; ?>
      <div class="factor-card">
        <div class="factor-top">
          <div class="factor-name"><?=$item['label']?></div>
          <div class="factor-pts"><?=$item['points']?> <span style="font-size:.75rem;color:#555;font-weight:400">/ <?=$item['max']?></span></div>
        </div>
        <div class="progress-track"><div class="progress-fill" style="width:<?=$pct?>%"></div></div>
        <?php if(isset($item['tip'])): ?><div class="tip-text"><?=htmlspecialchars($item['tip'])?></div><?php endif; ?>
        <?php if(isset($item['items'])): ?>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
          <?php foreach($item['items'] as $fi): ?>
          <span style="font-size:.72rem;padding:3px 10px;border-radius:50px;background:<?=$fi['done']?'rgba(0,255,136,.08)':'rgba(255,80,80,.06)'?>;border:1px solid <?=$fi['done']?'rgba(0,255,136,.2)':'rgba(255,80,80,.15)'?>;color:<?=$fi['done']?'#00ff88':'#ff8080'?>"><?=$fi['done']?'✓':'✗'?> <?=$fi['label']?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</body>
</html>
