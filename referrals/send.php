<?php
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes_functions.php';

$uid = (int)$_SESSION['user_id'];

// Get user's group
$uStmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
$uStmt->execute([$uid]);
$my_group_id = $uStmt->fetchColumn();

// Fetch categories
$cats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($cats)) {
    $cats = ['Business Services', 'Construction', 'Digital Marketing', 'Education', 'Finance', 'Healthcare', 'Manufacturing', 'Real Estate', 'Retail', 'Technology'];
}

// Fetch group members (exclude self)
$members = [];
if ($my_group_id) {
    $mStmt = $pdo->prepare("SELECT id, name, category FROM users WHERE group_id = ? AND id != ? AND status = 'active' ORDER BY name ASC");
    $mStmt->execute([$my_group_id, $uid]);
    $members = $mStmt->fetchAll(PDO::FETCH_ASSOC);
}

$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = $_POST['category'] ?? '';
    $receiver_id = !empty($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : null;
    $r_name = trim($_POST['r_name']);
    $r_phone = trim($_POST['r_phone']);
    $r_email = trim($_POST['r_email']);
    $r_notes = trim($_POST['notes']);
    $val = (int)($_POST['deal_value'] ?? 0);

    if (!$cat || !$r_name || !$r_phone) {
        $error = "Category, Contact Name, and Phone are required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert referral
            $stmt = $pdo->prepare("INSERT INTO referrals (sender_id, receiver_id, category, referred_name, phone, email, notes, estimated_value, status, created_at, assigned_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW(), NOW())");
            $stmt->execute([$uid, $receiver_id, $cat, $r_name, $r_phone, $r_email, $r_notes, $val]);
            $rid = $pdo->lastInsertId();

            $assigned_to_name = "Open Pool";

            // If Open Pool selected, try to auto-match (FAIRNESS)
            if ($receiver_id === null) {
                $matchStmt = $pdo->prepare("SELECT id, name FROM users WHERE category = ? AND status = 'active' AND id != ? ORDER BY last_lead_at ASC LIMIT 1");
                $matchStmt->execute([$cat, $uid]);
                $match = $matchStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($match) {
                    $receiver_id = $match['id'];
                    $assigned_to_name = $match['name'];
                    $pdo->prepare("UPDATE referrals SET receiver_id = ?, assigned_at = NOW() WHERE id = ?")->execute([$receiver_id, $rid]);
                    sendNotification($pdo, $receiver_id, "New Lead Assigned (Open Pool)", "A new $cat lead has been assigned to you from the open pool.", 'crm');
                }
            } else {
                // Direct referral
                sendNotification($pdo, $receiver_id, "New Referral Received", "You received a new referral from " . $_SESSION['name'], 'referral');
            }
            
            // Economy Update: +50 VooCoins
            awardCoins($pdo, $uid, 50, "Referral Submitted: " . $cat);

            $pdo->commit();
            header("Location: /referrals/list.php?sent=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

$page_title = 'Give a Referral — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <div>
                <h2 style="font-family:'Syne',sans-serif;font-weight:800;margin:0;color:#e8e8f5;">🤝 Give a Referral</h2>
                <p style="color:#8888aa;margin-top:5px;">Support your network and track deal flow</p>
            </div>
            <a href="/referrals/list.php" style="color:#FFD700;text-decoration:none;font-size:.85rem;font-weight:600;">← Back to List</a>
        </div>

        <?php if($error): ?><div class="alert alert-danger" style="background:rgba(255,77,109,.1);border:1px solid rgba(255,77,109,.3);color:#ff4d6d;"><?= $error ?></div><?php endif; ?>

        <form method="POST" style="background:#13131a;border:1px solid #2a2a3a;border-radius:16px;padding:30px;">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">1. Select Expert / Option</label>
                    <select name="receiver_id" id="receiver_id" class="form-select" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;">
                        <option value="0">🌐 Open Pool (Auto-match by Category)</option>
                        <?php if($members): ?>
                            <optgroup label="My Group Peers">
                                <?php foreach($members as $m): ?>
                                    <option value="<?= $m['id'] ?>" data-cat="<?= htmlspecialchars($m['category']??'') ?>">
                                        👤 <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['category'] ?? 'General') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">2. Referral Category *</label>
                    <input list="catList" name="category" id="category" class="form-control" placeholder="Search Industry (e.g. SEO, Pharma)" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;" required autocomplete="off">
                    <datalist id="catList">
                        <?php foreach($cats as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-12"><hr style="border-color:#2a2a3a;margin:10px 0;"></div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">Prospect Name *</label>
                    <input type="text" name="r_name" class="form-control" placeholder="John Doe" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">Prospect Phone *</label>
                    <input type="tel" name="r_phone" class="form-control" placeholder="+91 00000 00000" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">Prospect Email</label>
                    <input type="email" name="r_email" class="form-control" placeholder="john@example.com" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">Estimated Deal Value (₹)</label>
                    <input type="number" name="deal_value" class="form-control" placeholder="0.00" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;">
                </div>

                <div class="col-12">
                    <label class="form-label" style="font-size:.8rem;color:#8888aa;">Detailed Notes</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Describe the requirement, context, and any specific expectations..." style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;padding:12px;border-radius:10px;"></textarea>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" style="width:100%;padding:14px;background:linear-gradient(135deg,#FFD700,#ff8c00);color:#000;border:none;border-radius:12px;font-weight:800;font-size:1rem;box-shadow:0 4px 15px rgba(255,215,0,0.2);">
                        🚀 Submit Referral
                    </button>
                    <p style="text-align:center;font-size:.7rem;color:#666;margin-top:12px;">Sending a referral earns you 50 VooCoins and builds your Trust Score.</p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('category').addEventListener('change', function() {
    const selectedCat = this.value;
    const receiverSelect = document.getElementById('receiver_id');
    const options = receiverSelect.querySelectorAll('option[data-cat]');
    
    options.forEach(opt => {
        const memberCat = opt.getAttribute('data-cat');
        if (selectedCat && memberCat !== selectedCat) {
            opt.style.display = 'none';
        } else {
            opt.style.display = 'block';
        }
    });

    // If currently selected direct member is now hidden, reset to Open Pool
    const currentOpt = receiverSelect.options[receiverSelect.selectedIndex];
    if (currentOpt.style.display === 'none') {
        receiverSelect.value = "0";
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
