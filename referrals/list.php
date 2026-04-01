<?php
header('Content-Type: text/html; charset=utf-8');
// referrals/list.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes_functions.php';

$uid = (int)$_SESSION['user_id'];

// Handle status updates if needed (e.g., mark as completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid = (int)$_POST['referral_id'];
    $new_status = $_POST['status']; 
    if (in_array($new_status, ['pending', 'inprogress', 'completed', 'rejected', 'closed'])) {
        $pdo->prepare("UPDATE referrals SET status = ? WHERE id = ? AND (sender_id = ? OR receiver_id = ?)")
            ->execute([$new_status, $rid, $uid, $uid]);
            
        // Reward: Referral Accepted (+100 🪙 to the sender)
        if ($new_status === 'inprogress' || $new_status === 'completed') {
            // Check if already rewarded
            $chk = $pdo->prepare("SELECT sender_id, coins_rewarded FROM referrals WHERE id = ?");
            $chk->execute([$rid]);
            $ref = $chk->fetch();
            if ($ref && !$ref['coins_rewarded']) {
                awardCoins($pdo, $ref['sender_id'], 100, "Referral Accepted: ID $rid");
                $pdo->prepare("UPDATE referrals SET coins_rewarded = 1 WHERE id = ?")->execute([$rid]);
                sendNotification($pdo, $ref['sender_id'], "Referral Accepted!", "Your referral was accepted! You earned 100 VooCoins.", 'coins');
            }
        }
    }
}

$sent = $pdo->prepare("SELECT r.*, u.business_name as rname 
                       FROM referrals r 
                       LEFT JOIN users u ON r.receiver_id = u.id 
                       WHERE r.sender_id = ? 
                       ORDER BY r.created_at DESC");
$sent->execute([$uid]);
$sent_refs = $sent->fetchAll(PDO::FETCH_ASSOC);

$recv = $pdo->prepare("SELECT r.*, u.business_name as sname 
                       FROM referrals r 
                       LEFT JOIN users u ON r.sender_id = u.id 
                       WHERE r.receiver_id = ? 
                       ORDER BY r.created_at DESC");
$recv->execute([$uid]);
$recv_refs = $recv->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My Referrals — BizNexus';
$active_page = 'referrals';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<style>
.ref-tabs { display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid #1e1e2e; }
.ref-tab { padding:10px 20px; color:#8888aa; cursor:pointer; font-weight:600; font-size:.85rem; border-bottom:2px solid transparent; transition:.2s; }
.ref-tab:hover { color:#e8e8f5; }
.ref-tab.active { color:#FFD700; border-bottom-color:#FFD700; }

.ref-card { background:#13131a; border:1px solid #1e1e2e; border-radius:12px; padding:18px; margin-bottom:12px; }
.ref-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
.ref-name { font-weight:700; font-size:1rem; color:#e8e8f5; }
.ref-meta { font-size:.75rem; color:#666688; margin-top:3px; }
.ref-pill { padding:3px 10px; border-radius:20px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.rp-pending { background:rgba(255,140,0,.1); color:#ff8c00; border:1px solid rgba(255,140,0,.3); }
.rp-completed, .rp-closed { background:rgba(0,232,122,.1); color:#00e87a; border:1px solid rgba(0,232,122,.3); }
.rp-rejected { background:rgba(255,77,109,.1); color:#ff4d6d; border:1px solid rgba(255,77,109,.3); }

.ref-body { font-size:.85rem; color:#b0b0cc; line-height:1.6; }
.ref-footer { margin-top:12px; padding-top:10px; border-top:1px solid #1e1e2e; display:flex; justify-content:space-between; align-items:center; }
.ref-time { font-size:.7rem; color:#666688; }
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
    <div>
        <h2 style="font-family:'Syne',sans-serif;font-weight:800;margin:0;color:#e8e8f5;font-size:1.4rem;">🤝 Referral Network</h2>
        <p style="color:#8888aa;margin:5px 0 0;font-size:.83rem;">Manage referrals you've sent and received</p>
    </div>
    <a href="/referrals/send.php" style="padding:9px 20px;background:linear-gradient(135deg,#FFD700,#ff8c00);color:#000;font-weight:700;border-radius:9px;text-decoration:none;font-size:.84rem;">+ Give Referral</a>
</div>

<div class="ref-tabs">
    <div class="ref-tab active" onclick="showTab('sent')">Sent (<?= count($sent_refs) ?>)</div>
    <div class="ref-tab" onclick="showTab('received')">Received (<?= count($recv_refs) ?>)</div>
</div>

<div id="tab-sent">
    <?php if (empty($sent_refs)): ?>
        <div style="text-align:center;padding:60px 20px;color:#666688;">
            <div style="font-size:3rem;margin-bottom:10px;">📤</div>
            <h3>No referrals sent</h3>
            <p style="font-size:.85rem;">Start growing your network by giving referrals!</p>
        </div>
    <?php else: ?>
        <?php foreach($sent_refs as $r): ?>
        <div class="ref-card">
            <div class="ref-header">
                <div>
                    <div class="ref-name"><?= htmlspecialchars($r['referred_name']) ?></div>
                    <div class="ref-meta">
                        👤 Sent to: <strong style="color:#d0d0e8;"><?= htmlspecialchars($r['rname'] ?: 'Unknown') ?></strong><br>
                        📞 <?= htmlspecialchars($r['referred_phone'] ?: 'N/A') ?> · ✉️ <?= htmlspecialchars($r['referred_email'] ?: 'N/A') ?>
                    </div>
                </div>
                <span class="ref-pill rp-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
            </div>
            <div class="ref-body">
                <strong>Type:</strong> <?= htmlspecialchars($r['referred_business_type'] ?: 'General') ?><br>
                <?php if($r['notes']): ?><em>"<?= htmlspecialchars($r['notes']) ?>"</em><?php endif; ?>
            </div>
            <div class="ref-footer">
                <span class="ref-time">📅 Sent on <?= date('d M Y', strtotime($r['created_at'])) ?></span>
                <?php if($r['estimated_value']): ?><span style="color:#FFD700;font-weight:700;font-size:.8rem;">Est. ₹<?= number_format($r['estimated_value']) ?></span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="tab-received" style="display:none;">
    <?php if (empty($recv_refs)): ?>
        <div style="text-align:center;padding:60px 20px;color:#666688;">
            <div style="font-size:3rem;margin-bottom:10px;">📥</div>
            <h3>No referrals received</h3>
            <p style="font-size:.85rem;">When members refer business to you, it will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach($recv_refs as $r): ?>
        <div class="ref-card">
            <div class="ref-header">
                <div>
                    <div class="ref-name"><?= htmlspecialchars($r['referred_name']) ?></div>
                    <div class="ref-meta">
                        👤 From: <strong style="color:#d0d0e8;"><?= htmlspecialchars($r['sname'] ?: 'Unknown') ?></strong><br>
                        📞 <?= htmlspecialchars($r['referred_phone'] ?: 'N/A') ?> · ✉️ <?= htmlspecialchars($r['referred_email'] ?: 'N/A') ?>
                    </div>
                </div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="referral_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" onchange="this.form.submit()" class="ref-pill rp-<?= $r['status'] ?>" style="background:transparent;border:1px solid #2a2a3a;color:inherit;outline:none;cursor:pointer;">
                        <option value="pending" <?= $r['status']==='pending'?'selected':'' ?>>Pending</option>
                        <option value="inprogress" <?= $r['status']==='inprogress'?'selected':'' ?>>In Progress</option>
                        <option value="completed" <?= $r['status']==='completed'?'selected':'' ?>>Completed</option>
                        <option value="rejected" <?= $r['status']==='rejected'?'selected':'' ?>>Rejected</option>
                    </select>
                </form>
            </div>
            <div class="ref-body">
                <strong>Requirement:</strong> <?= htmlspecialchars($r['referred_business_type'] ?: 'General Inquiry') ?><br>
                <?php if($r['notes']): ?><em>"<?= htmlspecialchars($r['notes']) ?>"</em><?php endif; ?>
            </div>
            <div class="ref-footer">
                <span class="ref-time">📅 Received on <?= date('d M Y', strtotime($r['created_at'])) ?></span>
                <?php if($r['estimated_value']): ?><span style="color:#FFD700;font-weight:700;font-size:.8rem;">Est. ₹<?= number_format($r['estimated_value']) ?></span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function showTab(type) {
    document.querySelectorAll('.ref-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
    
    if (type === 'sent') {
        document.querySelector('.ref-tab:nth-child(1)').classList.add('active');
        document.getElementById('tab-sent').style.display = 'block';
    } else {
        document.querySelector('.ref-tab:nth-child(2)').classList.add('active');
        document.getElementById('tab-received').style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
