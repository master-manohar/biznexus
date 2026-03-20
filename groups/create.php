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
$error='';

if($_SERVER['REQUEST_METHOD']==='POST' && $pdo){
    $name    = trim(strip_tags($_POST['name']??''));
    $desc    = trim(strip_tags($_POST['description']??''));
    $city    = trim(strip_tags($_POST['city']??''));
    $tier    = in_array($_POST['tier']??'',['Nexus','Omkara','Diamond','Charminar','Tajmahal','Gold'])?$_POST['tier']:'Nexus';
    $day     = $_POST['meeting_day']??'Wednesday';
    $time    = $_POST['meeting_time']??'8:00 AM';
    $format  = $_POST['meeting_format']??'Hybrid';
    $max     = max(5, min(50, (int)($_POST['max_members']??20)));

    if(strlen($name)<5){ $error='Group name must be at least 5 characters.'; }
    elseif(strlen($city)<2){ $error='Please enter a city.'; }
    else {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS bizgroups (id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(150),description TEXT,city VARCHAR(100),tier VARCHAR(30) DEFAULT 'Nexus',created_by INT,max_members INT DEFAULT 20,meeting_day VARCHAR(20),meeting_time VARCHAR(20),meeting_format VARCHAR(50),is_active TINYINT(1) DEFAULT 1,created_at DATETIME DEFAULT NOW())");
            $pdo->exec("CREATE TABLE IF NOT EXISTS group_members (id INT AUTO_INCREMENT PRIMARY KEY,group_id INT,user_id INT,role VARCHAR(20) DEFAULT 'pending',joined_at DATETIME DEFAULT NOW(),UNIQUE KEY uniq_member(group_id,user_id))");
            $pdo->prepare("INSERT INTO bizgroups (name,description,city,tier,created_by,max_members,meeting_day,meeting_time,meeting_format) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$desc,$city,$tier,$uid,$max,$day,$time,$format]);
            $gid = $pdo->lastInsertId();
            // Creator auto-joins as admin
            $pdo->prepare("INSERT IGNORE INTO group_members (group_id,user_id,role) VALUES (?,?,'admin')")->execute([$gid,$uid]);
            // Award 25 coins for creating a group
            $pdo->prepare("INSERT INTO voocoin_balances (user_id,balance,total_earned,total_spent) VALUES (?,25,25,0) ON DUPLICATE KEY UPDATE balance=balance+25,total_earned=total_earned+25")->execute([$uid]);
            $pdo->prepare("INSERT INTO coin_transactions (user_id,amount,type,description,created_at) VALUES (?,'25','credit','Created a business group',NOW())")->execute([$uid]);
            header('Location: /groups/index.php?created=1'); exit;
        } catch(Exception $e){ $error='Error: '.$e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Group – BizNexus</title>
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
.card-biz{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:28px}
.form-label{font-size:.82rem;font-weight:600;color:#888;margin-bottom:6px}
.form-control,.form-select{background:#080810;border:1.5px solid #1e1e2e;color:#e0e0f0;border-radius:10px;padding:12px 15px;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:#FFD700;box-shadow:none;background:#080810;color:#e0e0f0}
.form-select option{background:#0e0e16}
.btn-gold{background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border:none;border-radius:50px;padding:13px 36px;font-size:.95rem;cursor:pointer;font-family:'Syne',sans-serif}
.tier-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.tier-card{background:#080810;border:1.5px solid #1e1e2e;border-radius:10px;padding:14px;text-align:center;cursor:pointer;transition:.2s}
.tier-card input{display:none}
.tier-card.selected{border-color:var(--tc);background:rgba(255,255,255,.03)}
.alert-err{background:rgba(255,80,80,.08);border:1px solid rgba(255,80,80,.2);color:#ff8080;border-radius:10px;padding:12px 16px;font-size:.85rem;margin-bottom:20px}
.coin-tip{background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:10px;padding:14px;font-size:.83rem;color:#888;margin-bottom:24px}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav style="flex:1;padding:12px 0">
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
  </nav>
  <div class="sidebar-footer"><a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a></div>
</div>

<div class="main">
  <div class="mb-4">
    <a href="/groups/index.php" style="color:#777;font-size:.85rem;text-decoration:none">← Back to Groups</a>
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin:8px 0 4px">Create a Group 👥</h1>
    <p style="color:#777;font-size:.88rem">Start your own BNI-style chapter and grow together</p>
  </div>

  <?php if($error): ?><div class="alert-err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="coin-tip">🪙 <strong style="color:#FFD700">Earn +25 VooCoins</strong> for creating a group! Group admins also get priority lead dispatch in their city.</div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card-biz">
        <form method="POST">
          <div class="mb-4">
            <label class="form-label">Group Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Hyderabad Tech Founders Circle" maxlength="150" required value="<?= htmlspecialchars($_POST['name']??'') ?>">
          </div>
          <div class="mb-4">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="What kind of businesses are you looking to connect?"><?= htmlspecialchars($_POST['description']??'') ?></textarea>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">City *</label>
              <input type="text" name="city" class="form-control" placeholder="e.g. Hyderabad" required value="<?= htmlspecialchars($_POST['city']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Max Members</label>
              <select name="max_members" class="form-select">
                <?php foreach([10,15,20,25,30,40,50] as $m): ?>
                <option value="<?= $m ?>" <?= (($_POST['max_members']??20)==$m)?'selected':'' ?>><?= $m ?> members</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Tier Selection -->
          <div class="mb-4">
            <label class="form-label">Group Tier</label>
            <div class="tier-grid">
              <?php
              $tiers = ['Nexus'=>['⭐','Free starter','#6c63ff'],'Omkara'=>['🌿','Growth focus','#00ff88'],'Diamond'=>['💎','Elite group','#00d4ff'],'Charminar'=>['🕌','Local legacy','#ff6b6b'],'Tajmahal'=>['🏛️','Premium network','#FFD700'],'Gold'=>['🥇','Top performers','#ffa94d']];
              foreach($tiers as $tier=>[$icon,$desc,$color]):
              $sel = (($_POST['tier']??'Nexus')===$tier);
              ?>
              <label class="tier-card <?= $sel?'selected':'' ?>" style="--tc:<?= $color ?>" onclick="selectTier(this,'<?= $color ?>')">
                <input type="radio" name="tier" value="<?= $tier ?>" <?= $sel?'checked':'' ?>>
                <div style="font-size:1.4rem;margin-bottom:4px"><?= $icon ?></div>
                <div style="font-weight:700;font-size:.82rem;color:<?= $color ?>"><?= $tier ?></div>
                <div style="font-size:.72rem;color:#555"><?= $desc ?></div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Meeting Day</label>
              <select name="meeting_day" class="form-select">
                <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                <option value="<?=$d?>" <?=(($_POST['meeting_day']??'Wednesday')===$d)?'selected':''?>><?=$d?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Meeting Time</label>
              <select name="meeting_time" class="form-select">
                <?php foreach(['7:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','6:00 PM','7:00 PM'] as $t): ?>
                <option value="<?=$t?>" <?=(($_POST['meeting_time']??'8:00 AM')===$t)?'selected':''?>><?=$t?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Format</label>
              <select name="meeting_format" class="form-select">
                <option value="Online">💻 Online</option>
                <option value="Physical">🏢 Physical</option>
                <option value="Hybrid" selected>🔀 Hybrid</option>
              </select>
            </div>
          </div>

          <div class="d-flex gap-3">
            <button type="submit" class="btn-gold">🚀 Create Group (+25 🪙)</button>
            <a href="/groups/index.php" style="background:transparent;border:1.5px solid #2a2a3a;color:#888;border-radius:50px;padding:12px 24px;font-size:.9rem;text-decoration:none;display:inline-block">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-biz">
        <h5 style="font-family:'Syne',sans-serif;margin-bottom:16px">👑 Group Admin Gets</h5>
        <div style="font-size:.85rem;line-height:2;color:#888">
          <div>🪙 +25 coins on creation</div>
          <div>📊 Approve/reject members</div>
          <div>📅 Schedule meetings</div>
          <div>🚀 Priority leads in city</div>
          <div>📢 Broadcast to members</div>
          <div>🏆 Admin badge on profile</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function selectTier(card,color){
  document.querySelectorAll('.tier-card').forEach(c=>{c.classList.remove('selected');c.style.borderColor='';});
  card.classList.add('selected');
  card.style.borderColor=color;
  card.querySelector('input').checked=true;
}
</script>
</body>
</html>
