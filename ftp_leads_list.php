<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$uid = getCurrentUserId();
$stmt = $pdo->prepare('SELECT l.*,u.name as sender,u.business_name FROM leads l JOIN users u ON l.from_user_id=u.id WHERE l.to_user_id=? ORDER BY l.created_at DESC LIMIT 50');
$stmt->execute([$uid]); $leads = $stmt->fetchAll();
$page_title = 'My Leads - BizNexus';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 style="color:#FFD700;margin:0">📊 Leads Received</h4>
  <a href="/leads/add.php" class="btn btn-warning btn-sm fw-bold">+ Send Lead</a>
</div>
<?php if(empty($leads)): ?>
<div style="text-align:center;color:#555;padding:60px">No leads received yet.</div>
<?php else: ?>
<div class="row g-3">
<?php foreach($leads as $l): ?>
<div class="col-md-6">
<div style="background:#13131a;border:1px solid #2a2a3a;border-radius:14px;padding:20px">
  <div class="d-flex justify-content-between mb-2">
    <strong><?=htmlspecialchars($l['title'])?></strong>
    <span class="badge bg-<?=$l['status']==='accepted'?'success':($l['status']==='open'?'warning text-dark':'secondary')?>"><?=$l['status']?></span>
  </div>
  <?php if($l['contact_name']): ?><div style="color:#aaa;font-size:.85rem">👤 <?=htmlspecialchars($l['contact_name'])?></div><?php endif; ?>
  <?php if($l['contact_phone']): ?><div style="color:#aaa;font-size:.85rem">📞 <?=htmlspecialchars($l['contact_phone'])?></div><?php endif; ?>
  <?php if($l['estimated_value'] > 0): ?><div style="color:#FFD700;font-weight:700">₹<?=number_format($l['estimated_value'])?></div><?php endif; ?>
  <div style="color:#555;font-size:.78rem;margin-top:6px">From: <?=htmlspecialchars($l['business_name']??$l['sender'])?></div>
  <a href="/leads/view.php?id=<?=$l['id']?>" class="btn btn-outline-warning btn-sm mt-2 w-100">View Details</a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
