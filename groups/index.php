<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: /auth/login.php'); exit; }
function getDB(){
    $configs=[['localhost','u175452495_biznexus','u175452495_bizuser','Biz@9990'],['localhost','u175452495_biznexus','u175452495_voo_user','Vooschool@123'],['localhost','u175452495_biznexus','u175452495','Biz@9990']];
    foreach($configs as $c){ try{ return new PDO("mysql:host={$c[0]};dbname={$c[1]};charset=utf8mb4",$c[2],$c[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
$uid = (int)$_SESSION['user_id'];
$pdo = getDB();

// Create groups tables if not exist
if($pdo){
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS bizgroups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description TEXT,
            city VARCHAR(100) NOT NULL,
            tier ENUM('Nexus','Omkara','Diamond','Charminar','Tajmahal','Gold') DEFAULT 'Nexus',
            created_by INT NOT NULL,
            max_members INT DEFAULT 20,
            meeting_day VARCHAR(20) DEFAULT 'Wednesday',
            meeting_time VARCHAR(20) DEFAULT '8:00 AM',
            meeting_format VARCHAR(50) DEFAULT 'Hybrid',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT NOW()
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('admin','member','pending') DEFAULT 'pending',
            joined_at DATETIME DEFAULT NOW(),
            UNIQUE KEY uniq_member (group_id, user_id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS group_meetings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            title VARCHAR(200),
            meeting_date DATE,
            meeting_time VARCHAR(20),
            venue TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT NOW()
        )");
    } catch(Exception $e){}
}

// Join / Leave group
if($_SERVER['REQUEST_METHOD']==='POST' && $pdo){
    $action   = $_POST['action']??'';
    $group_id = (int)($_POST['group_id']??0);
    if($action==='join' && $group_id){
        try {
            $pdo->prepare("INSERT IGNORE INTO group_members (group_id,user_id,role) VALUES (?,?,'pending')")->execute([$group_id,$uid]);
        } catch(Exception $e){}
    } elseif($action==='leave' && $group_id){
        $pdo->prepare("DELETE FROM group_members WHERE group_id=? AND user_id=?")->execute([$group_id,$uid]);
    }
    header('Location: /groups/index.php'); exit;
}

// Get all groups with member count and my status
$groups = [];
if($pdo){
    $stmt = $pdo->prepare("
        SELECT g.*,
               COUNT(gm.id) as member_count,
               MAX(CASE WHEN gm.user_id=? THEN gm.role ELSE NULL END) as my_status,
               u.name as creator_name
        FROM bizgroups g
        LEFT JOIN group_members gm ON g.id=gm.group_id AND gm.role != 'pending'
        LEFT JOIN users u ON g.created_by=u.id
        WHERE g.is_active=1
        GROUP BY g.id
        ORDER BY member_count DESC, g.created_at DESC
    ");
    $stmt->execute([$uid]);
    $groups = $stmt->fetchAll();
}

// My groups
$my_groups = array_filter($groups, fn($g) => in_array($g['my_status'],['admin','member']));

$tier_colors = ['Nexus'=>'#6c63ff','Omkara'=>'#00ff88','Diamond'=>'#00d4ff','Charminar'=>'#ff6b6b','Tajmahal'=>'#FFD700','Gold'=>'#ffa94d'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Business Groups – BizNexus</title>
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
.page-header h1{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px}
.group-card{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:22px;transition:.2s;height:100%}
.group-card:hover{border-color:rgba(255,215,0,.2);transform:translateY(-2px)}
.tier-badge{display:inline-flex;align-items:center;gap:5px;border-radius:50px;padding:4px 12px;font-size:.72rem;font-weight:700;margin-bottom:12px}
.group-name{font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;margin-bottom:6px}
.group-city{font-size:.8rem;color:#888;margin-bottom:10px}
.group-desc{font-size:.82rem;color:#666;margin-bottom:14px;line-height:1.5;min-height:40px}
.group-meta{display:flex;gap:16px;font-size:.75rem;color:#555;margin-bottom:16px}
.group-meta span{display:flex;align-items:center;gap:4px}
.btn-join{background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border:none;border-radius:50px;padding:9px 22px;font-size:.82rem;cursor:pointer;transition:.2s;width:100%}
.btn-join:hover{transform:translateY(-1px)}
.btn-joined{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;border-radius:50px;padding:9px 22px;font-size:.82rem;width:100%;cursor:default;font-weight:600}
.btn-pending{background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);color:#FFD700;border-radius:50px;padding:9px 22px;font-size:.82rem;width:100%;font-weight:600;cursor:default}
.capacity-bar{height:4px;background:#1e1e2e;border-radius:4px;margin:8px 0}
.capacity-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#FFD700,#00ff88);transition:width .5s}
.stats-row{display:flex;gap:12px;margin-bottom:28px}
.stat-box{background:#0e0e16;border:1px solid #1e1e2e;border-radius:12px;padding:16px 20px;flex:1;text-align:center}
.stat-box .n{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#FFD700}
.stat-box .l{font-size:.72rem;color:#555;text-transform:uppercase;letter-spacing:.5px}
.bnl-explain{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:24px;margin-bottom:28px}
.empty-state{text-align:center;padding:60px 20px;color:#555}
.empty-state .icon{font-size:3rem;margin-bottom:12px}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav style="flex:1;padding:12px 0;overflow-y:auto">
    <a href="/dashboard/index.php" class="nav-link">🏠 Dashboard</a>
    <a href="/profile/edit.php" class="nav-link">👤 My Profile</a>
    <a href="/referrals/send.php" class="nav-link">🤝 Referrals</a>
    <a href="/meetings/book.php" class="nav-link">📅 Meetings</a>
    <a href="/marketplace/index.php" class="nav-link">🏪 Marketplace</a>
    <a href="/crm/index.php" class="nav-link">📊 CRM</a>
    <a href="/invoices/create.php" class="nav-link">🧾 Invoices</a>
    <a href="/coins/balance.php" class="nav-link">🪙 VooCoins</a>
    <a href="/community/index.php" class="nav-link">💬 Community</a>
    <a href="/groups/index.php" class="nav-link active">👥 Groups</a>
    <a href="/advisor/index.php" class="nav-link">🤖 AI Advisor</a>
    <a href="/notifications/index.php" class="nav-link">🔔 Notifications</a>
    <a href="/settings/index.php" class="nav-link">⚙️ Settings</a>
  </nav>
  <div class="sidebar-footer"><a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a></div>
</div>

<div class="main">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px">Business Groups 👥</h1>
      <p style="color:#777;font-size:.88rem">BNI-style chapters — meet weekly, give referrals, grow together</p>
    </div>
    <a href="/groups/create.php" style="background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border-radius:50px;padding:11px 24px;text-decoration:none;font-size:.88rem;font-family:'Syne',sans-serif">+ Create Group</a>
  </div>

  <!-- Platform Stats -->
  <div class="stats-row">
    <div class="stat-box"><div class="n"><?= count($groups) ?></div><div class="l">Active Groups</div></div>
    <div class="stat-box"><div class="n"><?= array_sum(array_column($groups,'member_count')) ?></div><div class="l">Total Members</div></div>
    <div class="stat-box"><div class="n"><?= count($my_groups) ?></div><div class="l">My Groups</div></div>
    <div class="stat-box"><div class="n">₹∞</div><div class="l">Referrals in Groups</div></div>
  </div>

  <!-- How Groups Work -->
  <div class="bnl-explain">
    <div class="row align-items-center g-3">
      <div class="col-md-8">
        <h5 style="font-family:'Syne',sans-serif;color:#FFD700;margin-bottom:8px">How BizNexus Groups Work</h5>
        <div class="row g-2" style="font-size:.82rem;color:#888">
          <div class="col-sm-4">📅 <strong style="color:#e0e0f0">Weekly Meetings</strong> — Online or physical, every week</div>
          <div class="col-sm-4">🤝 <strong style="color:#e0e0f0">Priority Referrals</strong> — Group members refer each other first</div>
          <div class="col-sm-4">🪙 <strong style="color:#e0e0f0">+50 VooCoins</strong> — Bonus for each group referral closed</div>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <div style="font-size:.78rem;color:#555">Inspired by BNI — Built for India's SMEs 🇮🇳</div>
      </div>
    </div>
  </div>

  <!-- Groups Grid -->
  <?php if(empty($groups)): ?>
  <div class="empty-state">
    <div class="icon">👥</div>
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">No Groups Yet</div>
    <div style="font-size:.88rem;margin-bottom:20px">Be the first to create a BizNexus chapter in your city!</div>
    <a href="/groups/create.php" style="background:#FFD700;color:#000;font-weight:800;border-radius:50px;padding:12px 28px;text-decoration:none;font-size:.9rem">Create First Group →</a>
  </div>
  <?php else: ?>
  <div class="row g-4">
    <?php foreach($groups as $g):
        $color = $tier_colors[$g['tier']] ?? '#FFD700';
        $pct = $g['max_members']>0 ? min(100, round($g['member_count']/$g['max_members']*100)) : 0;
        $is_member = in_array($g['my_status'],['admin','member']);
        $is_pending = $g['my_status']==='pending';
        $is_full = $g['member_count'] >= $g['max_members'];
    ?>
    <div class="col-md-6 col-lg-4">
      <div class="group-card">
        <div class="tier-badge" style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44">
          <?= match($g['tier']){'Nexus'=>'⭐','Omkara'=>'🌿','Diamond'=>'💎','Charminar'=>'🕌','Tajmahal'=>'🏛️','Gold'=>'🥇',default=>'👥'} ?> <?= $g['tier'] ?>
        </div>
        <div class="group-name"><?= htmlspecialchars($g['name']) ?></div>
        <div class="group-city">📍 <?= htmlspecialchars($g['city']) ?></div>
        <div class="group-desc"><?= htmlspecialchars(substr($g['description']??'',0,100)) ?><?= strlen($g['description']??'')>100?'...':'' ?></div>
        <div class="group-meta">
          <span>👥 <?= $g['member_count'] ?>/<?= $g['max_members'] ?></span>
          <span>📅 <?= $g['meeting_day'] ?></span>
          <span>🕐 <?= $g['meeting_time'] ?></span>
        </div>
        <div class="capacity-bar"><div class="capacity-fill" style="width:<?= $pct ?>%"></div></div>
        <div style="font-size:.72rem;color:#555;margin-bottom:12px"><?= $pct ?>% full</div>
        <?php if($is_member): ?>
          <form method="POST" style="display:flex;gap:8px">
            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
            <input type="hidden" name="action" value="leave">
            <div class="btn-joined" style="flex:1">✅ Joined</div>
            <a href="/groups/members.php?id=<?= $g['id'] ?>" style="background:rgba(255,215,0,0.1); border:1px solid #FFD70044; color:#FFD700; border-radius:50px; padding:8px 14px; font-size:.75rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px">👥 Members</a>
            <button type="submit" style="background:transparent;border:1px solid #2a2a3a;color:#555;border-radius:50px;padding:8px 14px;font-size:.75rem;cursor:pointer" onclick="return confirm('Leave this group?')">Leave</button>
          </form>
        <?php elseif($is_pending): ?>
          <div class="btn-pending">⏳ Request Pending</div>
        <?php elseif($is_full): ?>
          <div class="btn-pending" style="color:#555;border-color:#2a2a3a">Group Full</div>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
            <input type="hidden" name="action" value="join">
            <button type="submit" class="btn-join">Join Group →</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
