<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$uid = getCurrentUserId();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_email = trim($_POST['to_email'] ?? '');
    $s = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $s->execute([$to_email, $uid]); $to = $s->fetch();
    if ($to) {
        $status = 'open';
        $pdo->prepare('INSERT INTO leads (from_user_id,to_user_id,title,description,contact_name,contact_phone,contact_email,estimated_value,category,city,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())')
        ->execute([$uid,$to['id'],$_POST['title']??'',$_POST['description']??'',$_POST['contact_name']??'',$_POST['contact_phone']??'',$_POST['contact_email']??'',$_POST['estimated_value']??0,$_POST['category']??'',$_POST['city']??'',$status]);
        header('Location: /leads/list.php'); exit;
    } else { $msg = 'Member not found.'; }
}
$page_title = 'Send Lead - BizNexus';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-7">
<div class="d-flex justify-content-between mb-4">
  <a href="/leads/list.php" style="color:#FFD700;text-decoration:none;font-weight:700">← Leads</a>
  <h4 style="color:#FFD700;margin:0">📤 Send Lead</h4>
</div>
<?php if($msg): ?><div class="alert alert-danger"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:20px;padding:32px">
<form method="POST">
<label style="color:#888;font-size:.82rem">Recipient Email *</label>
<input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="to_email" type="email" required>
<label style="color:#888;font-size:.82rem">Lead Title *</label>
<input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="title" required>
<div class="row">
<div class="col-6"><label style="color:#888;font-size:.82rem">Contact Name</label><input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="contact_name"></div>
<div class="col-6"><label style="color:#888;font-size:.82rem">Contact Phone</label><input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="contact_phone"></div>
</div>
<div class="row">
<div class="col-6"><label style="color:#888;font-size:.82rem">Category</label><input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="category"></div>
<div class="col-6"><label style="color:#888;font-size:.82rem">City</label><input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="city"></div>
</div>
<label style="color:#888;font-size:.82rem">Est. Value (INR)</label>
<input class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="estimated_value" type="number" value="0">
<label style="color:#888;font-size:.82rem">Description</label>
<textarea class="form-control mb-3" style="background:#0f0f18;border-color:#2a2a3a;color:#e0e0f0" name="description" rows="3"></textarea>
<button type="submit" class="btn btn-warning w-100 fw-bold">Send Lead</button>
</form>
</div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
