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
$toast = '';

// Ensure notification columns exist
if($pdo){
    try{
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_leads TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_referrals TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_meetings TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_coins TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_marketing TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_whatsapp TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(20) DEFAULT ''");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_visibility ENUM('public','members','private') DEFAULT 'public'");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor TINYINT(1) DEFAULT 0");
    }catch(Exception $e){}
}

$user = [];
if($pdo){
    $st=$pdo->prepare("SELECT * FROM users WHERE id=?");
    $st->execute([$uid]);
    $user=$st->fetch();
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD']==='POST' && $pdo){
    $action = $_POST['action']??'';

    if($action==='password'){
        $curr = $_POST['current_password']??'';
        $new  = $_POST['new_password']??'';
        $conf = $_POST['confirm_password']??'';
        if(!password_verify($curr, $user['password'])){ $toast='error:Current password is incorrect.'; }
        elseif(strlen($new)<8){ $toast='error:New password must be at least 8 characters.'; }
        elseif($new!==$conf){ $toast='error:Passwords do not match.'; }
        else{
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT),$uid]);
            $toast='success:Password updated successfully!';
        }
    }

    elseif($action==='notifications'){
        $fields=['notify_leads','notify_referrals','notify_meetings','notify_coins','notify_marketing','notify_whatsapp'];
        $vals=[]; $params=[];
        foreach($fields as $f){ $vals[]="$f=?"; $params[]=isset($_POST[$f])?1:0; }
        $params[]=$uid;
        $pdo->prepare("UPDATE users SET ".implode(',',$vals)." WHERE id=?")->execute($params);
        if(isset($_POST['whatsapp_number'])){
            $wp=preg_replace('/[^0-9]/','',$_POST['whatsapp_number']);
            $pdo->prepare("UPDATE users SET whatsapp_number=? WHERE id=?")->execute([$wp,$uid]);
        }
        $toast='success:Notification preferences saved!';
    }

    elseif($action==='privacy'){
        $vis = in_array($_POST['profile_visibility']??'',['public','members','private'])?$_POST['profile_visibility']:'public';
        $pdo->prepare("UPDATE users SET profile_visibility=? WHERE id=?")->execute([$vis,$uid]);
        $toast='success:Privacy settings updated!';
    }

    elseif($action==='deactivate'){
        $pw = $_POST['deactivate_password']??'';
        if(password_verify($pw, $user['password'])){
            $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$uid]);
            session_destroy();
            header('Location: /auth/login.php?deactivated=1'); exit;
        } else { $toast='error:Incorrect password.'; }
    }

    // Reload user data
    $st=$pdo->prepare("SELECT * FROM users WHERE id=?");
    $st->execute([$uid]);
    $user=$st->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings – BizNexus</title>
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
.tabs{display:flex;gap:4px;background:#0a0a12;border:1px solid #1e1e2e;border-radius:12px;padding:5px;margin-bottom:28px;width:fit-content}
.tab-btn{padding:9px 20px;border-radius:9px;border:none;background:transparent;color:#777;font-size:.85rem;font-weight:600;cursor:pointer;transition:.15s;font-family:'DM Sans',sans-serif}
.tab-btn.active{background:#FFD700;color:#000}
.tab-pane{display:none}.tab-pane.active{display:block}
.card-biz{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:28px;margin-bottom:20px}
.card-biz h5{font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #1e1e2e}
.form-label{font-size:.82rem;font-weight:600;color:#888;margin-bottom:6px}
.form-control,.form-select{background:#080810;border:1.5px solid #1e1e2e;color:#e0e0f0;border-radius:10px;padding:12px 15px;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:#FFD700;box-shadow:none;background:#080810;color:#e0e0f0}
.form-control::placeholder{color:#444}
.btn-gold{background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border:none;border-radius:50px;padding:11px 28px;font-size:.88rem;cursor:pointer;transition:.2s}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(255,215,0,.2)}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #0d0d1a}
.toggle-row:last-child{border-bottom:none}
.toggle-info .t{font-size:.88rem;font-weight:600;color:#e0e0f0}
.toggle-info .s{font-size:.78rem;color:#555;margin-top:2px}
.switch{position:relative;display:inline-block;width:44px;height:24px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#1e1e2e;border-radius:24px;transition:.3s}
.slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#555;border-radius:50%;transition:.3s}
input:checked+.slider{background:rgba(255,215,0,.2);border:1px solid rgba(255,215,0,.3)}
input:checked+.slider:before{transform:translateX(20px);background:#FFD700}
.toast-wrap{position:fixed;top:20px;right:20px;z-index:9999}
.toast-msg{padding:14px 20px;border-radius:12px;font-size:.88rem;font-weight:600;margin-bottom:8px;animation:slideIn .3s ease}
.toast-success{background:rgba(0,255,136,.1);border:1px solid rgba(0,255,136,.3);color:#00ff88}
.toast-error{background:rgba(255,80,80,.1);border:1px solid rgba(255,80,80,.3);color:#ff8080}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
.danger-zone{background:rgba(255,60,60,.03);border:1px solid rgba(255,60,60,.15);border-radius:16px;padding:24px}
.plan-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:50px;padding:6px 16px;font-size:.8rem;font-weight:700;color:#FFD700;margin-bottom:16px}
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
    <a href="/groups/index.php" class="nav-link">👥 Groups</a>
    <a href="/advisor/index.php" class="nav-link">🤖 AI Advisor</a>
    <a href="/settings/index.php" class="nav-link active">⚙️ Settings</a>
  </nav>
  <div class="sidebar-footer"><a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a></div>
</div>

<div class="main">
  <?php if($toast): list($type,$msg)=explode(':',$toast,2); ?>
  <div class="toast-wrap"><div class="toast-msg toast-<?=$type?>"><?=$type==='success'?'✅':'⚠'?> <?=htmlspecialchars($msg)?></div></div>
  <script>setTimeout(()=>document.querySelector('.toast-msg').style.opacity=0,3000)</script>
  <?php endif; ?>

  <div class="mb-4">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px">Settings ⚙️</h1>
    <p style="color:#777;font-size:.88rem">Manage your account, security, and notification preferences</p>
  </div>

  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('account',this)">👤 Account</button>
    <button class="tab-btn" onclick="showTab('security',this)">🔐 Security</button>
    <button class="tab-btn" onclick="showTab('notifications',this)">🔔 Notifications</button>
    <button class="tab-btn" onclick="showTab('privacy',this)">🛡️ Privacy</button>
  </div>

  <!-- ACCOUNT TAB -->
  <div id="tab-account" class="tab-pane active">
    <div class="card-biz">
      <h5>📋 Account Information</h5>
      <div class="plan-badge">
        <?= match(strtolower($user['plan']??'free')){'silver'=>'🥈 Silver','gold'=>'🥇 Gold','platinum'=>'💎 Platinum',default=>'🆓 Free'} ?>
        Plan
      </div>
      <div class="row g-3" style="font-size:.88rem">
        <div class="col-md-6"><div style="color:#555;margin-bottom:4px">Full Name</div><div style="color:#e0e0f0;font-weight:600"><?=htmlspecialchars($user['name']??'-')?></div></div>
        <div class="col-md-6"><div style="color:#555;margin-bottom:4px">Email</div><div style="color:#e0e0f0;font-weight:600"><?=htmlspecialchars($user['email']??'-')?></div></div>
        <div class="col-md-6"><div style="color:#555;margin-bottom:4px">Business</div><div style="color:#e0e0f0;font-weight:600"><?=htmlspecialchars($user['business_name']??'-')?></div></div>
        <div class="col-md-6"><div style="color:#555;margin-bottom:4px">Member Since</div><div style="color:#e0e0f0;font-weight:600"><?=date('d M Y',strtotime($user['created_at']??'now'))?></div></div>
      </div>
      <div style="margin-top:20px;display:flex;gap:10px">
        <a href="/profile/edit.php" class="btn-gold" style="text-decoration:none">✏️ Edit Profile</a>
        <a href="/membership/upgrade.php" style="background:transparent;border:1.5px solid #FFD700;color:#FFD700;border-radius:50px;padding:11px 24px;font-size:.88rem;text-decoration:none;font-weight:700">⬆️ Upgrade Plan</a>
      </div>
    </div>
    <div class="card-biz">
      <h5>🪙 VooCoin Balance</h5>
      <?php
      $bal=0;
      if($pdo){ $bs=$pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id=?"); $bs->execute([$uid]); $bd=$bs->fetch(); $bal=$bd['balance']??0; }
      ?>
      <div style="font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:900;color:#FFD700;margin-bottom:8px"><?=number_format($bal)?> <span style="font-size:1rem;color:#555">VooCoins</span></div>
      <a href="/coins/balance.php" style="color:#FFD700;font-size:.85rem;text-decoration:none">View full history →</a>
    </div>
  </div>

  <!-- SECURITY TAB -->
  <div id="tab-security" class="tab-pane">
    <div class="card-biz">
      <h5>🔑 Change Password</h5>
      <form method="POST" style="max-width:420px">
        <input type="hidden" name="action" value="password">
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" placeholder="Your current password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters" required oninput="pwStr(this.value)">
          <div style="height:4px;background:#1e1e2e;border-radius:4px;margin-top:6px;overflow:hidden"><div id="pwbar" style="height:100%;border-radius:4px;transition:.3s;width:0"></div></div>
        </div>
        <div class="mb-4">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn-gold">Update Password</button>
      </form>
    </div>
    <div class="danger-zone">
      <h5 style="font-family:'Syne',sans-serif;font-size:.95rem;color:#ff6060;margin-bottom:16px">⚠️ Danger Zone</h5>
      <p style="font-size:.85rem;color:#777;margin-bottom:16px">Deactivating your account will hide your profile and pause all activity. You can reactivate by contacting support.</p>
      <button onclick="document.getElementById('deact-modal').style.display='flex'" style="background:transparent;border:1px solid #ff4444;color:#ff4444;border-radius:50px;padding:10px 22px;font-size:.85rem;cursor:pointer;font-weight:600">Deactivate Account</button>
    </div>
  </div>

  <!-- NOTIFICATIONS TAB -->
  <div id="tab-notifications" class="tab-pane">
    <div class="card-biz">
      <h5>🔔 Email Notifications</h5>
      <form method="POST">
        <input type="hidden" name="action" value="notifications">
        <?php
        $toggles=[
          'notify_leads'=>['📥 New Leads','Get notified when a matching lead arrives in your category'],
          'notify_referrals'=>['🤝 Referral Updates','Updates when you send or receive referrals'],
          'notify_meetings'=>['📅 Meeting Reminders','Reminders 1 hour before your scheduled meetings'],
          'notify_coins'=>['🪙 VooCoin Activity','When you earn or spend VooCoins'],
          'notify_marketing'=>['📢 Platform Updates','BizNexus news, new features, and tips'],
          'notify_whatsapp'=>['💬 WhatsApp Alerts','Get critical alerts on WhatsApp too'],
        ];
        foreach($toggles as $field=>[$title,$sub]):
        ?>
        <div class="toggle-row">
          <div class="toggle-info">
            <div class="t"><?=$title?></div>
            <div class="s"><?=$sub?></div>
          </div>
          <label class="switch">
            <input type="checkbox" name="<?=$field?>" <?=($user[$field]??1)?'checked':''?>>
            <span class="slider"></span>
          </label>
        </div>
        <?php endforeach; ?>
        <div class="mt-4">
          <label class="form-label">WhatsApp Number (for critical alerts)</label>
          <input type="text" name="whatsapp_number" class="form-control" style="max-width:280px" placeholder="10-digit number" value="<?=htmlspecialchars($user['whatsapp_number']??'')?>">
        </div>
        <div class="mt-4"><button type="submit" class="btn-gold">Save Preferences</button></div>
      </form>
    </div>
  </div>

  <!-- PRIVACY TAB -->
  <div id="tab-privacy" class="tab-pane">
    <div class="card-biz">
      <h5>🛡️ Profile Visibility</h5>
      <form method="POST">
        <input type="hidden" name="action" value="privacy">
        <p style="color:#777;font-size:.87rem;margin-bottom:20px">Control who can see your profile and contact details on BizNexus.</p>
        <?php
        $opts=['public'=>['🌐','Public','Visible to everyone including search engines'],'members'=>['👥','Members Only','Only logged-in BizNexus members can see your profile'],'private'=>['🔒','Private','Only you can see your profile']];
        foreach($opts as $val=>[$icon,$label,$desc]):
        $sel=($user['profile_visibility']??'public')===$val;
        ?>
        <label style="display:flex;align-items:flex-start;gap:14px;padding:16px;background:<?=$sel?'rgba(255,215,0,.04)':'#080810'?>;border:1.5px solid <?=$sel?'#FFD700':'#1e1e2e'?>;border-radius:12px;cursor:pointer;margin-bottom:10px;transition:.2s">
          <input type="radio" name="profile_visibility" value="<?=$val?>" <?=$sel?'checked':''?> style="margin-top:3px;accent-color:#FFD700">
          <div>
            <div style="font-weight:700;font-size:.9rem"><?=$icon?> <?=$label?></div>
            <div style="font-size:.8rem;color:#555;margin-top:2px"><?=$desc?></div>
          </div>
        </label>
        <?php endforeach; ?>
        <div class="mt-4"><button type="submit" class="btn-gold">Save Privacy Settings</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Deactivate Modal -->
<div id="deact-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#0e0e16;border:1px solid #ff4444;border-radius:16px;padding:28px;max-width:380px;width:90%">
    <h5 style="font-family:'Syne',sans-serif;color:#ff6060;margin-bottom:12px">⚠️ Deactivate Account?</h5>
    <p style="font-size:.85rem;color:#888;margin-bottom:20px">Enter your password to confirm. Your profile will be hidden and all activity paused.</p>
    <form method="POST">
      <input type="hidden" name="action" value="deactivate">
      <input type="password" name="deactivate_password" class="form-control mb-3" placeholder="Confirm your password" required>
      <div style="display:flex;gap:10px">
        <button type="submit" style="background:#ff4444;color:#fff;border:none;border-radius:50px;padding:11px 22px;font-size:.88rem;cursor:pointer;font-weight:700">Deactivate</button>
        <button type="button" onclick="document.getElementById('deact-modal').style.display='none'" style="background:transparent;border:1px solid #2a2a3a;color:#888;border-radius:50px;padding:11px 22px;font-size:.88rem;cursor:pointer">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showTab(id,btn){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  btn.classList.add('active');
}
function pwStr(p){
  const bar=document.getElementById('pwbar');
  let s=0;
  if(p.length>=8)s++;if(/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;
  bar.style.width=['0','25%','50%','75%','100%'][s];
  bar.style.background=['#555','#ff4455','#ff8c42','#FFD700','#00ff88'][s];
}
// Auto-hide toast
const t=document.querySelector('.toast-msg');
if(t)setTimeout(()=>{t.style.transition='.5s';t.style.opacity='0';},3500);
</script>
</body>
</html>
