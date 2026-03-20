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
$error = ''; $success = '';

// Ensure table has all columns
if($pdo){
    try {
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS listing_type VARCHAR(50) DEFAULT 'product'");
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT ''");
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) DEFAULT ''");
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS tags VARCHAR(300) DEFAULT ''");
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS views INT DEFAULT 0");
        $pdo->exec("ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    } catch(Exception $e){}
}

$CATEGORIES = ['Real Estate','Construction','Food and Beverage','Healthcare','Education','Retail','IT Services','Finance','Legal','Event Management','Photography','Fashion','Manufacturing','Other'];

if($_SERVER['REQUEST_METHOD']==='POST' && $pdo){
    $title    = trim(strip_tags($_POST['title']??''));
    $desc     = trim(strip_tags($_POST['description']??''));
    $price    = (float)($_POST['price']??0);
    $category = $_POST['category']??'';
    $type     = in_array($_POST['listing_type']??'',['product','service','offer','wanted'])?$_POST['listing_type']:'product';
    $location = trim(strip_tags($_POST['location']??''));
    $phone    = trim($_POST['contact_phone']??'');
    $tags     = trim(strip_tags($_POST['tags']??''));

    if(strlen($title)<5){ $error='Title must be at least 5 characters.'; }
    elseif(strlen($desc)<20){ $error='Description must be at least 20 characters.'; }
    elseif(!in_array($category,$CATEGORIES)){ $error='Please select a valid category.'; }
    else {
        try {
            $pdo->prepare("INSERT INTO marketplace_listings (user_id,title,description,price,category,listing_type,location,contact_phone,tags,status,created_at)
                VALUES (?,?,?,?,?,?,?,?,?,'active',NOW())")
                ->execute([$uid,$title,$desc,$price,$category,$type,$location,$phone,$tags]);
            $lid = $pdo->lastInsertId();
            // Award 5 coins for first listing
            $cnt = $pdo->prepare("SELECT COUNT(*) as c FROM marketplace_listings WHERE user_id=? AND status='active'");
            $cnt->execute([$uid]);
            if($cnt->fetch()['c']<=1){
                $pdo->prepare("INSERT INTO voocoin_balances (user_id,balance,total_earned,total_spent) VALUES (?,5,5,0) ON DUPLICATE KEY UPDATE balance=balance+5,total_earned=total_earned+5")->execute([$uid]);
                $pdo->prepare("INSERT INTO coin_transactions (user_id,amount,type,description,created_at) VALUES (?,'5','credit','First marketplace listing',NOW())")->execute([$uid]);
                sendNotification($pdo, $uid, "🪙 Coins Earned", "You earned 5 BizCoins for your first marketplace listing! 🏪", 'coin');
            }
            header('Location: /marketplace/index.php?created=1'); exit;
        } catch(Exception $e){ $error='Error creating listing: '.$e->getMessage(); }
    }
}

$user = $pdo ? $pdo->prepare("SELECT name,plan FROM users WHERE id=?") : null;
if($user){ $user->execute([$uid]); $user=$user->fetch(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Listing – BizNexus Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#06060a;color:#e0e0f0;font-family:'DM Sans',sans-serif}
.sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0a0a12;border-right:1px solid #1e1e2e;display:flex;flex-direction:column;z-index:100;padding:0}
.sidebar-logo{padding:22px 20px;font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:900;color:#FFD700;border-bottom:1px solid #1e1e2e}
.sidebar nav{flex:1;padding:12px 0;overflow-y:auto}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#777;font-size:.85rem;font-weight:500;transition:.15s;border-radius:0;text-decoration:none}
.nav-link:hover,.nav-link.active{color:#FFD700;background:rgba(255,215,0,.06)}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1e1e2e}
.main{margin-left:220px;padding:32px}
.page-header{margin-bottom:28px}
.page-header h1{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px}
.page-header p{color:#777;font-size:.88rem}
.card-biz{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:28px}
.form-label{font-size:.82rem;font-weight:600;color:#888;margin-bottom:6px}
.form-control,.form-select{background:#080810;border:1.5px solid #1e1e2e;color:#e0e0f0;border-radius:10px;padding:12px 15px;font-size:.9rem;font-family:'DM Sans',sans-serif}
.form-control:focus,.form-select:focus{border-color:#FFD700;box-shadow:none;background:#080810;color:#e0e0f0}
.form-control::placeholder{color:#444}
.form-select option{background:#0e0e16;color:#e0e0f0}
.btn-gold{background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border:none;border-radius:50px;padding:13px 32px;font-size:.95rem;cursor:pointer;transition:.2s;font-family:'Syne',sans-serif}
.btn-gold:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(255,215,0,.2)}
.btn-sec{background:transparent;border:1.5px solid #2a2a3a;color:#888;border-radius:50px;padding:12px 28px;font-size:.9rem;cursor:pointer;transition:.2s;text-decoration:none;display:inline-block}
.btn-sec:hover{border-color:#FFD700;color:#FFD700}
.type-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:4px}
.type-card{background:#080810;border:1.5px solid #1e1e2e;border-radius:10px;padding:14px 10px;text-align:center;cursor:pointer;transition:.2s}
.type-card:hover{border-color:#FFD700}
.type-card input{display:none}
.type-card.selected{border-color:#FFD700;background:rgba(255,215,0,.05)}
.type-icon{font-size:1.5rem;margin-bottom:4px}
.type-label{font-size:.75rem;font-weight:600;color:#888}
.type-card.selected .type-label{color:#FFD700}
.char-count{font-size:.75rem;color:#555;text-align:right;margin-top:4px}
.alert-err{background:rgba(255,80,80,.08);border:1px solid rgba(255,80,80,.2);color:#ff8080;border-radius:10px;padding:12px 16px;font-size:.85rem;margin-bottom:20px}
.tip-box{background:rgba(255,215,0,.04);border:1px solid rgba(255,215,0,.1);border-radius:10px;padding:14px;font-size:.82rem;color:#888;margin-bottom:20px}
.coin-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.15);border-radius:50px;padding:6px 14px;font-size:.78rem;font-weight:600;color:#FFD700}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav>
    <a href="/dashboard/index.php" class="nav-link">🏠 Dashboard</a>
    <a href="/profile/edit.php" class="nav-link">👤 My Profile</a>
    <a href="/referrals/send.php" class="nav-link">🤝 Referrals</a>
    <a href="/meetings/book.php" class="nav-link">📅 Meetings</a>
    <a href="/marketplace/index.php" class="nav-link active">🏪 Marketplace</a>
    <a href="/crm/index.php" class="nav-link">📊 CRM</a>
    <a href="/invoices/create.php" class="nav-link">🧾 Invoices</a>
    <a href="/coins/balance.php" class="nav-link">🪙 VooCoins</a>
    <a href="/community/index.php" class="nav-link">💬 Community</a>
    <a href="/groups/index.php" class="nav-link">👥 Groups</a>
    <a href="/advisor/index.php" class="nav-link">🤖 AI Advisor</a>
    <a href="/notifications/index.php" class="nav-link">🔔 Notifications</a>
    <a href="/settings/index.php" class="nav-link">⚙️ Settings</a>
  </nav>
  <div class="sidebar-footer">
    <a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a>
  </div>
</div>

<div class="main">
  <div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h1>Create Listing 🏪</h1>
        <p>List your product or service to reach thousands of B2B buyers</p>
      </div>
      <span class="coin-badge">🪙 +5 VooCoins for your first listing!</span>
    </div>
  </div>

  <?php if($error): ?><div class="alert-err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="tip-box">
    💡 <strong style="color:#FFD700">Pro Tip:</strong> Listings with clear pricing and detailed descriptions get 3x more inquiries. Add your WhatsApp number for instant buyer contact!
  </div>

  <form method="POST">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card-biz mb-4">
          <h5 style="font-family:'Syne',sans-serif;margin-bottom:20px">Listing Details</h5>

          <!-- Listing Type -->
          <div class="mb-4">
            <label class="form-label">Listing Type</label>
            <div class="type-grid">
              <?php foreach(['product'=>['📦','Product'],'service'=>['⚙️','Service'],'offer'=>['🎯','Special Offer'],'wanted'=>['🔍','Wanted / Buying']] as $val=>[$icon,$label]): ?>
              <label class="type-card <?= (($_POST['listing_type']??'product')===$val)?'selected':'' ?>">
                <input type="radio" name="listing_type" value="<?= $val ?>" <?= (($_POST['listing_type']??'product')===$val)?'checked':'' ?> onchange="selectType(this)">
                <div class="type-icon"><?= $icon ?></div>
                <div class="type-label"><?= $label ?></div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Premium Steel Pipes — Wholesale Rates" maxlength="120" required oninput="cnt(this,'tc')" value="<?= htmlspecialchars($_POST['title']??'') ?>">
            <div class="char-count"><span id="tc">0</span>/120</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description *</label>
            <textarea name="description" class="form-control" rows="5" placeholder="Describe your product/service in detail. Include specs, MOQ, delivery terms, etc." maxlength="1000" required oninput="cnt(this,'dc')"><?= htmlspecialchars($_POST['description']??'') ?></textarea>
            <div class="char-count"><span id="dc">0</span>/1000</div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Category *</label>
              <select name="category" class="form-select" required>
                <option value="">Select category...</option>
                <?php foreach($CATEGORIES as $cat): ?>
                <option value="<?= $cat ?>" <?= (($_POST['category']??'')===$cat)?'selected':'' ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Price (₹)</label>
              <input type="number" name="price" class="form-control" placeholder="0 = Contact for price" min="0" step="0.01" value="<?= htmlspecialchars($_POST['price']??'') ?>">
            </div>
          </div>
        </div>

        <div class="card-biz">
          <h5 style="font-family:'Syne',sans-serif;margin-bottom:20px">Contact & Location</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Location / City</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Hyderabad, Telangana" value="<?= htmlspecialchars($_POST['location']??'') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">WhatsApp / Contact</label>
              <input type="text" name="contact_phone" class="form-control" placeholder="10-digit number" maxlength="15" value="<?= htmlspecialchars($_POST['contact_phone']??'') ?>">
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label">Tags (comma separated)</label>
            <input type="text" name="tags" class="form-control" placeholder="wholesale, steel, pipes, manufacturing" value="<?= htmlspecialchars($_POST['tags']??'') ?>">
            <div style="font-size:.75rem;color:#555;margin-top:4px">Tags help buyers find your listing in search</div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card-biz mb-4">
          <h5 style="font-family:'Syne',sans-serif;margin-bottom:16px">📋 Listing Checklist</h5>
          <div id="checklist" style="font-size:.83rem;line-height:2">
            <div id="chk-title" style="color:#555">○ Title (min 5 chars)</div>
            <div id="chk-desc" style="color:#555">○ Description (min 20 chars)</div>
            <div id="chk-cat" style="color:#555">○ Category selected</div>
            <div id="chk-price" style="color:#555">○ Price entered</div>
            <div id="chk-loc" style="color:#555">○ Location added</div>
            <div id="chk-phone" style="color:#555">○ Contact number</div>
          </div>
        </div>

        <div class="card-biz mb-4">
          <h5 style="font-family:'Syne',sans-serif;margin-bottom:12px">🎯 Reach Estimate</h5>
          <div style="font-size:.85rem;color:#888;line-height:1.8">
            <div>👥 <strong style="color:#e0e0f0">400+</strong> active members</div>
            <div>🔍 <strong style="color:#e0e0f0">Buyers search daily</strong></div>
            <div>📧 Email alert to matches</div>
            <div>🤖 AI-matched to buyers</div>
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn-gold">🚀 Publish Listing</button>
          <a href="/marketplace/index.php" class="btn-sec text-center">Cancel</a>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function cnt(el,id){ document.getElementById(id).textContent=el.value.length; updateChecklist(); }
function selectType(radio){
  document.querySelectorAll('.type-card').forEach(c=>c.classList.remove('selected'));
  radio.closest('.type-card').classList.add('selected');
}
function updateChecklist(){
  const title=document.querySelector('[name=title]')?.value||'';
  const desc=document.querySelector('[name=description]')?.value||'';
  const cat=document.querySelector('[name=category]')?.value||'';
  const price=document.querySelector('[name=price]')?.value||'';
  const loc=document.querySelector('[name=location]')?.value||'';
  const ph=document.querySelector('[name=contact_phone]')?.value||'';
  const set=(id,ok)=>{const el=document.getElementById(id);if(el){el.style.color=ok?'#00ff88':'#555';el.textContent=(ok?'✓ ':'○ ')+el.textContent.substring(2);}};
  set('chk-title',title.length>=5);
  set('chk-desc',desc.length>=20);
  set('chk-cat',cat.length>0);
  set('chk-price',price.length>0);
  set('chk-loc',loc.length>0);
  set('chk-phone',ph.length>=10);
}
document.addEventListener('input',updateChecklist);
document.addEventListener('change',updateChecklist);
// Init char counts
['title','description'].forEach(n=>{const el=document.querySelector('[name='+n+']');if(el)el.dispatchEvent(new Event('input'));});
</script>
</body>
</html>
