<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$uid = (int)$_SESSION['user_id'];

// Fetch user and business profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$bpStmt = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ?");
$bpStmt->execute([$uid]);
$bp = $bpStmt->fetch(PDO::FETCH_ASSOC);

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update user basics
        $two_factor = isset($_POST['two_factor']) ? 1 : 0;
        $pdo->prepare("UPDATE users SET name=?, phone=?, whatsapp=?, two_factor=? WHERE id=?")
            ->execute([trim($_POST['name'] ?? $user['name']), trim($_POST['phone'] ?? ''), trim($_POST['whatsapp'] ?? ''), $two_factor, $uid]);
        
        $_SESSION['name'] = trim($_POST['name'] ?? $user['name']);
        
        // Business profile logic
        $bn = trim($_POST['business_name'] ?? ($bp['business_name'] ?? ''));
        $gst   = trim($_POST['gst_number'] ?? '');
        $aadh  = trim($_POST['aadhaar_number'] ?? '');
        $pan   = trim($_POST['pan_number'] ?? '');
        $firm  = trim($_POST['firm_type'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $cat   = trim($_POST['category'] ?? '');
        
        // Auto-verify logic
        $gst_v = (strlen($gst) >= 15) ? 1 : ($bp['gst_verified'] ?? 0);
        $kyc_v = (strlen($aadh) >= 12 || strlen($pan) >= 10) ? 1 : ($user['kyc_verified'] ?? 0);
        
        if ($bn || $bp) {
            if ($bp) {
                $pdo->prepare("UPDATE business_profiles SET business_name=?,tagline=?,description=?,category=?,city=?,address=?,whatsapp=?,phone=?,email=?,website=?,gst_number=?,aadhaar_number=?,pan_number=?,firm_type=?,gst_verified=? WHERE user_id=?")
                    ->execute([$bn,$_POST['tagline']??'',$_POST['description']??'',$_POST['category']??'',$_POST['city']??'',$_POST['address']??'',$_POST['biz_whatsapp']??'',$_POST['biz_phone']??'',$_POST['biz_email']??'',$_POST['website']??'',$gst,$aadh,$pan,$firm,$gst_v,$uid]);
            } else {
                $pdo->prepare("INSERT INTO business_profiles(user_id,business_name,tagline,description,category,city,address,whatsapp,phone,email,website,gst_number,aadhaar_number,pan_number,firm_type,gst_verified,profile_complete,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW())")
                    ->execute([$uid,$bn,$_POST['tagline']??'',$_POST['description']??'',$_POST['category']??'',$_POST['city']??'',$_POST['address']??'',$_POST['biz_whatsapp']??'',$_POST['biz_phone']??'',$_POST['biz_email']??'',$_POST['website']??'',$gst,$aadh,$pan,$firm,$gst_v]);
                
                awardCoins($pdo, $uid, 100, "Business Profile Completed Bonus");
            }
        }
        
        // Award points for GST/KYC if just verified
        if ($gst_v && !($bp['gst_verified']??0)) {
            awardCoins($pdo, $uid, 200, "GST Verification Bonus");
        }
        if ($kyc_v && !($user['kyc_verified']??0)) {
            $pdo->prepare("UPDATE users SET kyc_verified = 1 WHERE id = ?")->execute([$uid]);
            awardCoins($pdo, $uid, 300, "KYC Documents Verified Bonus");
        }

        // Set profile_complete if basics are filled
        if (!empty($bn) && !empty($cat) && !empty($desc)) {
            $pdo->prepare("UPDATE users SET profile_complete = 1 WHERE id = ?")->execute([$uid]);
        }
        
        // Recalculate Trust Score
        calculateTrustScore($pdo, $uid);
        
        // Refresh data
        $stmtV = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmtV->execute([$uid]);
        $user = $stmtV->fetch(PDO::FETCH_ASSOC);
        
        $bpStmtV = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ?");
        $bpStmtV->execute([$uid]);
        $bp = $bpStmtV->fetch(PDO::FETCH_ASSOC);
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Unified Categories from Governance Table
try {
    $cats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) {
    $cats = ['Business Services','Construction','Digital Marketing','Education','Finance','Healthcare','Manufacturing','Real Estate','Retail','Technology'];
}

$can_edit_cat = ($user['role'] === 'admin') || empty($bp['category']);

$page_title = 'Edit Profile — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<style>
.profile-section { background:#13131a; border:1px solid #2a2a3a; border-radius:14px; padding:24px; margin-bottom:20px; }
.section-heading { font-size:.82rem; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#FFD700; margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid #1e1e2e; }
.form-label { color:#c0c0d8 !important; font-size:.82rem; font-weight:500; margin-bottom:5px; display:block; }
.form-control, .form-select {
    background:#0d0d16 !important;
    border:1px solid #2a2a3a !important;
    color:#e8e8f5 !important;
    border-radius:8px;
    padding:10px 13px;
    font-size:.88rem;
    transition:.2s;
}
.form-control:focus, .form-select:focus {
    border-color:#FFD700 !important;
    box-shadow:0 0 0 2px rgba(255,215,0,.1) !important;
    color:#e8e8f5 !important;
    background:#0d0d16 !important;
}
.form-control::placeholder { color:#555577; }
.form-select option { background:#13131a; }
.btn-save {
    background:linear-gradient(135deg,#FFD700,#ff8c00);
    color:#000;
    font-weight:700;
    border:none;
    border-radius:10px;
    padding:12px 30px;
    font-size:.92rem;
    cursor:pointer;
    transition:.2s;
}
.btn-save:hover { opacity:.9; transform:translateY(-1px); }
.avatar-circle {
    width:80px; height:80px;
    border-radius:50%;
    background:linear-gradient(135deg,#6c63ff,#4834d4);
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; font-weight:700; color:white;
    border:3px solid #FFD700;
}
.success-msg { background:rgba(0,232,122,.1); border:1px solid rgba(0,232,122,.3); border-radius:10px; padding:12px 16px; color:#00e87a; font-size:.88rem; margin-bottom:20px; }
.error-msg { background:rgba(255,77,109,.1); border:1px solid rgba(255,77,109,.3); border-radius:10px; padding:12px 16px; color:#ff4d6d; font-size:.88rem; margin-bottom:20px; }
</style>

<!-- Page Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <div class="avatar-circle"><?= strtoupper(substr($user['name']??'?', 0, 1)) ?></div>
        <div>
            <div style="font-size:1.3rem;font-weight:800;color:#e8e8f5;font-family:'Syne',sans-serif;"><?= htmlspecialchars($user['name']??'Member') ?></div>
            <div style="font-size:.82rem;color:#8888aa;margin-top:2px;display:flex;align-items:center;gap:8px;">
                <?= htmlspecialchars($user['email']??'') ?>
                <?php if($user['email_verified']??0): ?>
                    <span title="Verified" style="color:#00e87a;font-size:0.9rem;">✅</span>
                <?php else: ?>
                    <button type="button" class="btn btn-sm p-0 text-warning" onclick="sendEmailVerification()" style="font-size:.72rem;border:none;background:none;text-decoration:underline;font-weight:600;">Verify Now</button>
                <?php endif; ?>
            </div>
            <?php if ($user['is_verified']??0): ?>
            <span style="font-size:.7rem;background:rgba(255,215,0,.15);color:#FFD700;border:1px solid rgba(255,215,0,.3);border-radius:20px;padding:2px 10px;margin-top:4px;display:inline-block;">✓ Platinum Verified</span>
            <?php endif; ?>
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:.7rem;color:#8888aa;text-transform:uppercase;letter-spacing:1px;">Member Since</div>
        <div style="font-size:.9rem;color:#c0c0d8;font-weight:600;"><?= date('M Y', strtotime($user['created_at']??'now')) ?></div>
    </div>
</div>

<?php if ($success): ?>
<div class="success-msg">✅ Profile updated successfully!</div>
<?php elseif ($error): ?>
<div class="error-msg">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
// Membership widget data
$plan         = $user['plan'] ?? 'free';
$plan_expires = $user['plan_expires_at'] ?? null;
$days_left    = $plan_expires ? max(0, (int)((strtotime($plan_expires) - time()) / 86400)) : 0;
$plan_labels  = ['free'=>['emoji'=>'🆓','color'=>'#666699','label'=>'Free','billing'=>null],
                 'silver'=>['emoji'=>'⚪','color'=>'#c0c0c0','label'=>'Silver','billing'=>'Monthly/Yearly'],
                 'gold'=>['emoji'=>'🥇','color'=>'#FFD700','label'=>'Gold','billing'=>'Monthly/Yearly'],
                 'platinum'=>['emoji'=>'💎','color'=>'#a259ff','label'=>'Platinum','billing'=>'Monthly/Yearly']];
$pi = $plan_labels[$plan] ?? $plan_labels['free'];
$pct = $plan_expires ? min(100, max(2, round(($days_left / 30) * 100))) : 0;
$urgent = $days_left > 0 && $days_left <= 7;
?>
<!-- ── Membership Status Widget ── -->
<div style="background:linear-gradient(135deg,#13131a,#0f0f18);border:1px solid <?= $urgent ? 'rgba(255,77,109,.4)' : 'rgba(255,215,0,.15)' ?>;border-radius:16px;padding:20px 22px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="font-size:2.2rem;line-height:1;"><?= $pi['emoji'] ?></div>
        <div>
            <div style="font-size:.68rem;color:#666699;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Current Plan</div>
            <div style="font-size:1.15rem;font-weight:800;color:<?= $pi['color'] ?>;font-family:'Syne',sans-serif;"><?= $pi['label'] ?> Plan</div>
            <?php if ($plan !== 'free' && $plan_expires): ?>
            <div style="font-size:.75rem;color:<?= $urgent ? '#ff4d6d' : '#8888aa' ?>;margin-top:3px;">
                <?php if ($urgent): ?>⚠️ Expires <?= date('d M Y', strtotime($plan_expires)) ?> — <?php else: ?>✅ Active until <?= date('d M Y', strtotime($plan_expires)) ?> — <?php endif; ?>
                <strong style="color:<?= $urgent?'#ff4d6d':$pi['color'] ?>;"><?= $days_left ?> days left</strong>
            </div>
            <!-- Days progress bar -->
            <div style="width:200px;height:5px;background:#1a1a28;border-radius:3px;margin-top:7px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,<?= $urgent?'#ff4d6d':$pi['color'] ?>,<?= $urgent?'#ff8c00':'#ff8c00' ?>);border-radius:3px;transition:.5s;"></div>
            </div>
            <?php elseif ($plan === 'free'): ?>
            <div style="font-size:.75rem;color:#8888aa;margin-top:3px;">Upgrade to unlock full features</div>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
        <div style="display:flex;gap:12px;align-items:center;">
            <div style="text-align:right;">
                <div style="font-size:.68rem;color:#666699;text-transform:uppercase;letter-spacing:.8px;">Trust Score</div>
                <div style="font-size:1rem;font-weight:700;color:#00e87a;"><?= calculateTrustScore($pdo, $uid) ?> <span style="font-size:.8rem;"><?= getTrustLevel(calculateTrustScore($pdo, $uid))['badge'] ?></span></div>
            </div>
            <?php if ($plan !== 'free'): ?>
            <div style="text-align:right;border-left:1px solid #2a2a3a;padding-left:12px;">
                <div style="font-size:.68rem;color:#666699;text-transform:uppercase;letter-spacing:.8px;">Coins</div>
                <div style="font-size:1rem;font-weight:700;color:#FFD700;"><?= number_format($user['coins'] ?? 0) ?> 🪙</div>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="/profile/view.php?id=<?= $uid ?>" target="_blank" style="padding:8px 15px;background:rgba(255,255,255,.05);color:#fff;border:1px solid #2a2a3a;border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:600;">👁 View Profile</a>
            <a href="/membership/upgrade.php?plan=<?= $plan === 'free' ? 'silver' : $plan ?>&billing=yearly"
               style="padding:8px 18px;background:<?= $plan==='free'?'linear-gradient(135deg,#FFD700,#ff8c00)':'rgba(255,215,0,.1)' ?>;color:<?= $plan==='free'?'#000':'#FFD700' ?>;border:1px solid rgba(255,215,0,.3);border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:700;white-space:nowrap;">
                <?= $plan === 'free' ? '⬆ Upgrade Now' : ($urgent ? '🔄 Renew Plan' : '⬆ Upgrade Plan') ?>
            </a>
        </div>
    </div>
</div>

<form method="POST">
<!-- ── Personal Info ── -->
<div class="profile-section">
    <div class="section-heading">👤 Personal Information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']??'') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="flex:1;position:relative;">
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']??'') ?>" disabled style="opacity:.7;padding-right:40px;">
                    <?php if($user['email_verified']??0): ?>
                        <span title="Verified Email" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#00e87a;font-size:1.1rem;">✅</span>
                    <?php endif; ?>
                </div>
                <?php if(!($user['email_verified']??0)): ?>
                    <button type="button" id="btnVerifyEmail" onclick="sendEmailVerification()" style="background:rgba(255,215,0,.1);color:#FFD700;border:1px solid #FFD700;border-radius:8px;padding:8px 12px;font-size:.73rem;font-weight:700;white-space:nowrap;transition:.2s;">Verify Now</button>
                <?php endif; ?>
            </div>
            <div id="emailVerifyMsg" style="font-size:0.72rem;margin-top:5px;display:none;"></div>
            <small style="color:#666688;font-size:.73rem;">Login email cannot be changed here</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+91 00000 00000">
        </div>
        <div class="col-md-6">
            <label class="form-label">WhatsApp Number</label>
            <input type="tel" name="whatsapp" class="form-control" value="<?= htmlspecialchars($user['whatsapp']??'') ?>" placeholder="+91 00000 00000">
        </div>
        <div class="col-12">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="two_factor" id="twoFactor" <?= ($user['two_factor']??0) ? 'checked' : '' ?>>
                <label class="form-check-label text-white fw-bold" for="twoFactor">🛡️ Enable Two-Factor Authentication (2FA)</label>
                <div style="font-size:0.75rem;color:#888;">Adds an extra layer of security to your account (+100 Points)</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Security & Trust Checklist ── -->
<div class="profile-section" style="border-color: #00e87a33; background: rgba(0, 232, 122, 0.02);">
    <div class="section-heading" style="color: #00e87a;">📊 Security Rating Checklist</div>
    <div class="row g-3">
        <?php
        $checks = [
            ['Email Verified', $user['email_verified'], 150, 'Check your inbox for the verification link'],
            ['Phone Number', !empty($user['phone']), 150, 'Enter your mobile number above'],
            ['KYC Documents', $user['kyc_verified'], 300, 'Upload Aadhaar/PAN below'],
            ['GST Verification', $bp['gst_verified']??0, 200, 'Enter valid GSTIN for business trust'],
            ['2FA enabled', $user['two_factor']??0, 100, 'Toggle the 2FA switch above'],
            ['Profile Complete', $user['profile_complete']??0, 100, 'Ensure business details are filled']
        ];
        foreach($checks as $c):
        ?>
        <div class="col-md-4 col-sm-6">
            <div class="p-3 rounded-3" style="background: <?= $c[1] ? 'rgba(0,232,122,0.1)' : 'rgba(255,255,255,0.02)' ?>; border: 1px solid <?= $c[1] ? '#00e87a44' : '#2a2a3a' ?>;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.8rem; font-weight:700; color:<?= $c[1] ? '#00e87a' : '#888' ?>;">
                        <?= $c[1] ? '✅' : '⭕' ?> <?= $c[0] ?>
                    </span>
                    <span class="badge bg-dark text-white">+<?= $c[2] ?></span>
                </div>
                <div style="font-size:0.65rem; color:#666;"><?= $c[3] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Business Profile ── -->
<div class="profile-section">
    <div class="section-heading" style="font-size:1.1rem; color:#FFD700; border-bottom:2px solid #FFD700; padding-bottom:12px;">💼 Setup Your Business Profile</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Business Name *</label>
            <input type="text" name="business_name" class="form-control" value="<?= htmlspecialchars($bp['business_name']??'') ?>" placeholder="Your Company Name">
        </div>
        <div class="col-md-6">
            <label class="form-label">Tagline</label>
            <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($bp['tagline']??'') ?>" placeholder="What you do in one line">
        </div>
        <div class="col-12">
            <label class="form-label">Business Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Describe your business, products/services, USP..."><?= htmlspecialchars($bp['description']??'') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Category <?= $can_edit_cat ? '*' : '<span style="color:#ff4d6d;font-size:.7rem;">(LOCKED)</span>' ?></label>
            <select name="category" class="form-select" <?= $can_edit_cat ? 'required' : 'disabled' ?>>
                <option value="">-- Select Category --</option>
                <?php foreach ($cats as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($bp['category']??'')===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!$can_edit_cat): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($bp['category']) ?>">
                <small style="color:#666688;font-size:.73rem;">Contact Admin to change your primary business category</small>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($bp['city']??$user['city']??'') ?>" placeholder="e.g. Hyderabad">
        </div>
        <div class="col-md-6">
            <label class="form-label">Business Phone</label>
            <input type="tel" name="biz_phone" class="form-control" value="<?= htmlspecialchars($bp['phone']??'') ?>" placeholder="Office/Business number">
        </div>
        <div class="col-md-6">
            <label class="form-label">Business Email</label>
            <input type="email" name="biz_email" class="form-control" value="<?= htmlspecialchars($bp['email']??'') ?>" placeholder="business@example.com">
        </div>
        <div class="col-md-6">
            <label class="form-label">WhatsApp</label>
            <input type="tel" name="biz_whatsapp" class="form-control" value="<?= htmlspecialchars($bp['whatsapp']??$user['whatsapp']??'') ?>" placeholder="+91 number">
        </div>
        <div class="col-md-6">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($bp['website']??'') ?>" placeholder="https://yoursite.com">
        </div>
        <div class="col-12">
            <label class="form-label">Business Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Full street address..."><?= htmlspecialchars($bp['address']??'') ?></textarea>
        </div>
    </div>
</div>

<!-- ── KYC & Compliance ── -->
<div class="profile-section">
    <div class="section-heading">📋 KYC & Compliance Details</div>
    <div style="font-size:.78rem;color:#8888aa;margin-bottom:14px;">⚠️ This information is private and used for verification only. Not shown publicly.</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Type of Firm</label>
            <select name="firm_type" class="form-select">
                <option value="">-- Select Firm Type --</option>
                <?php foreach(['Sole Proprietorship','Partnership','LLP','Private Limited','Public Limited','OPC','Society/Trust/NGO','HUF','Cooperative','Franchise'] as $ft): ?>
                <option value="<?= $ft ?>" <?= ($bp['firm_type']??'')===$ft?'selected':'' ?>><?= $ft ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">GST Number</label>
            <div style="position:relative;">
                <input type="text" name="gst_number" class="form-control" value="<?= htmlspecialchars($bp['gst_number']??'') ?>" placeholder="22AAAAA0000A1Z5" maxlength="15" style="text-transform:uppercase;">
                <?php if($bp['gst_verified']??0): ?>
                <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#00e87a;font-size:.75rem;font-weight:700;">✓ Verified</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Aadhaar Number <span style="color:#555577;font-size:.72rem;">(stored encrypted)</span></label>
            <input type="text" name="aadhaar_number" class="form-control" value="<?= htmlspecialchars($bp['aadhaar_number']??'') ?>" placeholder="XXXX XXXX XXXX" maxlength="14">
        </div>
        <div class="col-md-6">
            <label class="form-label">PAN Number</label>
            <input type="text" name="pan_number" class="form-control" value="<?= htmlspecialchars($bp['pan_number']??'') ?>" placeholder="ABCDE1234F" maxlength="10" style="text-transform:uppercase; text-align: left;">
        </div>
    </div>
</div>

<!-- ── Save ── -->
<div style="display:flex;align-items:center;gap:16px;">
    <button type="submit" class="btn-save">💾 Save Profile</button>
    <a href="/dashboard/index.php" style="color:#8888aa;font-size:.85rem;text-decoration:none;">← Back to Dashboard</a>
</div>

</form>

<?php
$layout_end = __DIR__ . '/../includes/layout_end.php';
if (file_exists($layout_end)) {
    include $layout_end;
} else {
    echo '</main></body></html>';
}
?>
<script>
function sendEmailVerification() {
    const btn = document.getElementById('btnVerifyEmail');
    const msg = document.getElementById('emailVerifyMsg');
    
    btn.disabled = true;
    btn.innerText = 'Sending...';
    btn.style.opacity = '0.6';
    
    fetch('/auth/send_verification.php')
    .then(r => r.json())
    .then(data => {
        msg.style.display = 'block';
        msg.style.color = data.success ? '#00e87a' : '#ff4d6d';
        msg.innerText = data.message;
        
        if(data.success) {
            btn.innerText = 'Email Sent!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = 'Resend Code';
                btn.style.opacity = '1';
            }, 60000);
        } else {
            btn.disabled = false;
            btn.innerText = 'Try Again';
            btn.style.opacity = '1';
        }
    })
    .catch(e => {
        msg.style.display = 'block';
        msg.style.color = '#ff4d6d';
        msg.innerText = 'Error: Connection failed.';
        btn.disabled = false;
        btn.innerText = 'Retry';
        btn.style.opacity = '1';
    });
}
</script>
