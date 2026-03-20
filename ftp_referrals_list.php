<?php
define("BASE", dirname(dirname(__FILE__)));
require_once BASE . "/includes/db.php";
require_once BASE . "/includes/auth.php";
requireLogin();
$uid = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='send') {
    try {
        $pdo->prepare("INSERT INTO referrals (sender_id,receiver_id,referred_name,referred_business_type,referred_phone,referred_email,estimated_value,notes,status,created_at) VALUES (?,?,?,?,?,?,?,?,'pending',NOW())")
            ->execute([$uid,$_POST['receiver_id']??null,$_POST['referred_name'],$_POST['business_type']??'',$_POST['phone']??'',$_POST['email']??'',$_POST['value']??0,$_POST['notes']??'']);
        try{$pdo->prepare("UPDATE users SET coins=coins+50 WHERE id=?")->execute([$uid]);}catch(Throwable $e){}
        try{$pdo->prepare("INSERT INTO coins_transactions(user_id,amount,type,description,created_at)VALUES(?,50,'earn','Referral given',NOW())")->execute([$uid]);}catch(Throwable $e){}
    } catch(Throwable $e){$err=$e->getMessage();}
    if(!isset($err)){header("Location:/referrals/list.php?ok=1");exit;}
}
$sent=$pdo->prepare("SELECT r.*,u.business_name as rname FROM referrals r LEFT JOIN users u ON r.receiver_id=u.id WHERE r.sender_id=? ORDER BY r.created_at DESC");$sent->execute([$uid]);$sent=$sent->fetchAll();
$recv=$pdo->prepare("SELECT r.*,u.business_name as sname FROM referrals r LEFT JOIN users u ON r.sender_id=u.id WHERE r.receiver_id=? ORDER BY r.created_at DESC");$recv->execute([$uid]);$recv=$recv->fetchAll();
$members=$pdo->prepare("SELECT id,business_name,city FROM users WHERE id!=? AND is_active=1 ORDER BY business_name LIMIT 300");$members->execute([$uid]);$members=$members->fetchAll();
require_once BASE . "/includes/header.php";
?>
<div class="container py-4">
<?php if(isset($_GET['ok'])): ?><div class="alert alert-success">✅ Referral sent! +50 VooCoins earned.</div><?php endif; ?>
<?php if(isset($err)): ?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="card bg-dark border-warning">
      <div class="card-header text-warning fw-bold">🤝 Give a Referral</div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="send">
          <label class="text-muted small mb-1">Send To Member *</label>
          <select name="receiver_id" class="form-select form-select-sm bg-dark text-light border-secondary mb-2" required>
            <option value="">Select member...</option>
            <?php foreach($members as $m): ?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['business_name'])?> — <?=htmlspecialchars($m['city']??'')?></option><?php endforeach; ?>
          </select>
          <input type="text" name="referred_name" placeholder="Referred Person Name *" class="form-control form-control-sm bg-dark text-light border-secondary mb-2" required>
          <input type="text" name="business_type" placeholder="Their Business Type" class="form-control form-control-sm bg-dark text-light border-secondary mb-2">
          <input type="tel" name="phone" placeholder="Phone Number" class="form-control form-control-sm bg-dark text-light border-secondary mb-2">
          <input type="email" name="email" placeholder="Email (optional)" class="form-control form-control-sm bg-dark text-light border-secondary mb-2">
          <input type="number" name="value" placeholder="Deal Value ₹ (optional)" class="form-control form-control-sm bg-dark text-light border-secondary mb-2">
          <textarea name="notes" placeholder="Notes..." rows="2" class="form-control form-control-sm bg-dark text-light border-secondary mb-3"></textarea>
          <button class="btn btn-warning btn-sm w-100 fw-bold">Give Referral → +50 🪙</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#st">Sent (<?=count($sent)?>)</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rv">Received (<?=count($recv)?>)</a></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="st">
        <?php if(!$sent): ?><div class="text-muted text-center py-4">No referrals sent yet.</div><?php endif; ?>
        <?php foreach($sent as $r): ?>
        <div class="card bg-dark border-secondary mb-2 p-3">
          <div class="d-flex justify-content-between">
            <div><div class="fw-bold"><?=htmlspecialchars($r['referred_name']??'')?></div>
            <div class="text-muted small"><?=htmlspecialchars($r['referred_business_type']??'')?> · <?=htmlspecialchars($r['referred_phone']??'')?></div>
            <?php if($r['rname']): ?><div class="small text-warning">→ <?=htmlspecialchars($r['rname'])?></div><?php endif; ?>
            <?php if($r['estimated_value']): ?><div class="small text-muted">₹<?=number_format($r['estimated_value'])?></div><?php endif; ?></div>
            <span class="badge bg-<?=match($r['status']??'pending'){'done','completed','closed'=>'success','rejected'=>'danger',default=>'warning'}?>"><?=ucfirst($r['status']??'pending')?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="tab-pane fade" id="rv">
        <?php if(!$recv): ?><div class="text-muted text-center py-4">No referrals received yet.</div><?php endif; ?>
        <?php foreach($recv as $r): ?>
        <div class="card bg-dark border-secondary mb-2 p-3">
          <div class="d-flex justify-content-between">
            <div><div class="fw-bold"><?=htmlspecialchars($r['referred_name']??'')?></div>
            <div class="text-muted small">📞 <?=htmlspecialchars($r['referred_phone']??'')?></div>
            <?php if($r['sname']): ?><div class="small text-info">From: <?=htmlspecialchars($r['sname'])?></div><?php endif; ?></div>
            <span class="badge bg-<?=match($r['status']??'pending'){'done','completed','closed'=>'success','rejected'=>'danger',default=>'warning'}?>"><?=ucfirst($r['status']??'pending')?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once BASE . "/includes/footer.php"; ?>