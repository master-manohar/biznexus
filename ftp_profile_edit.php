<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid=$_SESSION['user_id'];
$s=$pdo->prepare("SELECT * FROM users WHERE id=?");$s->execute([$uid]);$user=$s->fetch();
$s=$pdo->prepare("SELECT * FROM business_profiles WHERE user_id=?");$s->execute([$uid]);$bp=$s->fetch();
$success=false;$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $pdo->prepare("UPDATE users SET name=?,phone=? WHERE id=?")->execute([trim($_POST['name']??$user['name']),trim($_POST['phone']??''),$uid]);
        $_SESSION['name']=trim($_POST['name']??$user['name']);
        $bn=trim($_POST['business_name']??'');
        if($bn){
            if($bp){
                $pdo->prepare("UPDATE business_profiles SET business_name=?,tagline=?,description=?,category=?,city=?,whatsapp=?,phone=?,email=?,website=?,updated_at=NOW() WHERE user_id=?")
                    ->execute([$bn,$_POST['tagline']??'',$_POST['description']??'',$_POST['category']??'',$_POST['city']??'',$_POST['whatsapp']??'',$_POST['biz_phone']??'',$_POST['biz_email']??'',$_POST['website']??'',$uid]);
            }else{
                $pdo->prepare("INSERT INTO business_profiles(user_id,business_name,tagline,description,category,city,whatsapp,phone,email,website,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())")
                    ->execute([$uid,$bn,$_POST['tagline']??'',$_POST['description']??'',$_POST['category']??'',$_POST['city']??'',$_POST['whatsapp']??'',$_POST['biz_phone']??'',$_POST['biz_email']??'',$_POST['website']??'']);
                awardCoins($pdo,$uid,50,'Profile created');
            }
            $s=$pdo->prepare("SELECT * FROM business_profiles WHERE user_id=?");$s->execute([$uid]);$bp=$s->fetch();
        }
        $success=true;
    }catch(Exception $e){$error=$e->getMessage();}
}
$cats=$pdo->query("SELECT name FROM product_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
*{font-family:'Inter',sans-serif}
body{background:#0a0a0f;color:#e8e8f0;display:flex;margin:0}
.sidebar{width:240px;background:#13131a;border-right:1px solid #2a2a3a;min-height:100vh;position:fixed;top:0;left:0;display:flex;flex-direction:column}
.sidebar-logo{color:#FFD700;font-size:1.3rem;font-weight:900;padding:20px;border-bottom:1px solid #2a2a3a}
.nav-link{color:#aaaabc!important;padding:10px 20px!important;display:flex;align-items:center;gap:10px;font-size:.9rem;transition:.2s;text-decoration:none}
.nav-link:hover,.nav-link.active{color:#FFD700!important;background:rgba(255,215,0,.06)}
.main{margin-left:240px;padding:28px;flex:1}
.fc{background:#13131a;border:1px solid #2a2a3a;border-radius:14px;padding:28px;margin-bottom:20px}
.sec{color:#FFD700;font-weight:800;font-size:.95rem;margin:0 0 16px;padding-bottom:8px;border-bottom:1px solid #1a1a1a}
label{color:#aaaabc!important;font-size:.83rem;display:block;margin-bottom:4px;font-weight:500}
input,select,textarea{background:#0f0f18;border:1.5px solid #2a2a3a;color:#e8e8f0;border-radius:8px;padding:11px 13px;width:100%;font-size:.9rem;outline:none;font-family:'Inter',sans-serif;transition:.2s;margin-bottom:14px}
input:focus,select:focus,textarea:focus{border-color:#FFD700}
select option{background:#13131a}
</style>
</head><body>
<div class="sidebar">
<div class="sidebar-logo">⚡ BizNexus</div>
<nav class="nav flex-column" style="flex:1">
<a class="nav-link" href="/dashboard/index.php">🏠 Dashboard</a>
<a class="nav-link active" href="/profile/edit.php">👤 My Profile</a>
<a class="nav-link" href="/referrals/send.php">🤝 Referrals</a>
<a class="nav-link" href="/meetings/list.php">📅 Meetings</a>
<a class="nav-link" href="/marketplace/index.php">🏪 Marketplace</a>
<a class="nav-link" href="/crm/index.php">📊 CRM</a>
<a class="nav-link" href="/invoices/create.php">🧾 Invoices</a>
<a class="nav-link" href="/coins/balance.php">🪙 Coins</a>
<a class="nav-link" href="/community/index.php">💬 Community</a>
<a class="nav-link" href="/advisor/index.php">🤖 AI Advisor</a>
<a class="nav-link" href="/notifications/index.php">🔔 Notifications</a>
</nav>
<a href="/auth/logout.php" style="color:#ff4455;padding:16px 20px;border-top:1px solid #2a2a3a;text-decoration:none;font-size:.9rem;display:block">🚪 Logout</a>
</div>
<div class="main">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
<h4 style="color:#FFD700;font-weight:900;margin:0">👤 My Profile</h4>
<?php if($bp):?><a href="/profile/view.php?id=<?=$bp['id']?>" style="border:1.5px solid #2a2a3a;color:#e8e8f0;border-radius:8px;padding:9px 18px;font-weight:600;text-decoration:none;font-size:.88rem">View Public Profile →</a><?php endif;?>
</div>
<?php if($success):?><div style="background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.3);border-radius:10px;padding:14px;margin-bottom:20px;color:#00ff88;font-weight:600">✅ Profile updated successfully!</div><?php endif;?>
<?php if($error):?><div style="background:rgba(255,68,68,.08);border:1px solid rgba(255,68,68,.3);border-radius:10px;padding:14px;margin-bottom:20px;color:#ff8888">❌ <?=htmlspecialchars($error)?></div><?php endif;?>
<form method="POST">
<div class="fc">
<div class="sec">👤 Personal Info</div>
<div class="row g-3">
<div class="col-md-6"><label>Full Name</label><input type="text" name="name" value="<?=htmlspecialchars($user['name']??'')?>" required></div>
<div class="col-md-6"><label>Phone</label><input type="tel" name="phone" value="<?=htmlspecialchars($user['phone']??'')?>"></div>
</div>
<label>Email (cannot change)</label>
<input type="email" value="<?=htmlspecialchars($user['email']??'')?>" disabled style="opacity:.5;cursor:not-allowed">
</div>
<div class="fc">
<div class="sec">🏢 Business Info</div>
<div class="row g-3">
<div class="col-md-6"><label>Business Name</label><input type="text" name="business_name" value="<?=htmlspecialchars($bp['business_name']??'')?>" placeholder="Your business name"></div>
<div class="col-md-6"><label>Tagline</label><input type="text" name="tagline" value="<?=htmlspecialchars($bp['tagline']??'')?>" placeholder="One line about your business"></div>
</div>
<label>Description</label>
<textarea name="description" rows="3" placeholder="What does your business do?"><?=htmlspecialchars($bp['description']??'')?></textarea>
<div class="row g-3">
<div class="col-md-6"><label>Category</label>
<select name="category"><option value="">Select...</option>
<?php foreach($cats as $c):?><option value="<?=$c?>" <?=($bp['category']??'')===$c?'selected':''?>><?=$c?></option><?php endforeach;?>
</select></div>
<div class="col-md-6"><label>City</label><input type="text" name="city" value="<?=htmlspecialchars($bp['city']??'')?>" placeholder="Hyderabad"></div>
</div>
</div>
<div class="fc">
<div class="sec">📞 Contact Details</div>
<div class="row g-3">
<div class="col-md-4"><label>WhatsApp</label><input type="tel" name="whatsapp" value="<?=htmlspecialchars($bp['whatsapp']??'')?>" placeholder="9876543210"></div>
<div class="col-md-4"><label>Business Phone</label><input type="tel" name="biz_phone" value="<?=htmlspecialchars($bp['phone']??'')?>" placeholder="9876543210"></div>
<div class="col-md-4"><label>Business Email</label><input type="email" name="biz_email" value="<?=htmlspecialchars($bp['email']??'')?>" placeholder="biz@email.com"></div>
</div>
<label>Website</label>
<input type="url" name="website" value="<?=htmlspecialchars($bp['website']??'')?>" placeholder="https://yourwebsite.com">
</div>
<button type="submit" style="background:#FFD700;color:#000;border:none;border-radius:10px;padding:13px 36px;font-weight:800;font-size:1rem;cursor:pointer">💾 Save Profile</button>
</form>
</div></body></html>