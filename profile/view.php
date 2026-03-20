<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$profile_id = (int)($_GET['id'] ?? 0);
if (!$profile_id) {
    echo "Invalid Profile ID";
    exit;
}

// Fetch member and profile
$stmt = $pdo->prepare("SELECT u.id, u.name, u.plan, u.created_at, bp.* 
                       FROM users u 
                       LEFT JOIN business_profiles bp ON u.id = bp.user_id 
                       WHERE u.id = ? AND u.status = 'active'");
$stmt->execute([$profile_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    echo "Member not found or account inactive.";
    exit;
}

$trustScore = calculateTrustScore($pdo, $profile_id);
$trust       = getTrustLevel($trustScore);
$page_title = ($member['business_name'] ?: $member['name']) . ' - Business Profile | BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>

<style>
:root {
    --gold: #FFD700; --gold-dark: #b8860b; --bg: #0a0a0f; --card: #13131a; --border: #1e1e2e;
    --text: #d0d0e8; --text-dim: #8888aa; --green: #00e87a; --blue: #4488ff;
}

.profile-hero {
    background: linear-gradient(135deg, #13131a 0%, #0a0a0f 100%);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}
.profile-hero::after {
    content: '⚡'; position: absolute; top: -20px; right: -20px; font-size: 15rem; opacity: 0.03; color: var(--gold);
}

.trust-shield {
    background: rgba(255, 215, 0, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 12px;
    padding: 15px 25px;
    display: inline-flex;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}
.ts-score { font-size: 2.2rem; font-weight: 900; color: var(--gold); line-height: 1; font-family: 'Syne', sans-serif; }
.ts-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-dark); }

.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
.info-card { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; }
.ic-icon { font-size: 1.5rem; margin-bottom: 12px; opacity: 0.7; }
.ic-label { font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
.ic-value { font-size: 1rem; color: #fff; font-weight: 600; }

.desc-box { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 30px; margin-top: 30px; line-height: 1.7; color: var(--text); }
.desc-title { font-size: 1.1rem; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px; color: var(--gold); }

.badge-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 10px; }
.bp-verified { background: rgba(0, 232, 122, 0.1); color: var(--green); border: 1px solid rgba(0, 232, 122, 0.2); }
</style>

<div class="container-fluid py-4">
    <!-- Hero Section -->
    <div class="profile-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 style="font-family: 'Syne', sans-serif; font-weight: 800; color: #fff; margin-bottom: 8px;"><?= htmlspecialchars($member['business_name'] ?: $member['name']) ?></h1>
                <p style="font-size: 1.1rem; color: var(--gold); opacity: 0.9; margin-bottom: 20px;"><?= htmlspecialchars($member['tagline'] ?: 'Innovative Business Partner') ?></p>
                
                <?php if ($member['gst_number']): ?>
                    <div class="badge-pill bp-verified">🛡️ GST VERIFIED</div>
                <?php endif; ?>
                
                <div class="trust-shield">
                    <div class="ts-score"><?= $trustScore ?></div>
                    <div>
                        <div class="ts-label"><?= $trust['label'] ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-dim)">Trust Quotient Score</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$member['whatsapp'] ?: '') ?>" target="_blank" class="btn btn-lg w-100 mb-3" style="background: var(--green); color: #000; font-weight: 800; border-radius: 12px;">Contact on WhatsApp</a>
                <?php if ($member['website']): ?>
                    <a href="<?= htmlspecialchars($member['website']) ?>" target="_blank" class="btn btn-outline-light btn-lg w-100" style="border-radius: 12px; border-color: var(--border);">Visit Website</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-card">
            <div class="ic-icon">🏢</div>
            <div class="ic-label">Industry & Category</div>
            <div class="ic-value"><?= htmlspecialchars($member['category'] ?: 'General Business') ?></div>
        </div>
        <div class="info-card">
            <div class="ic-icon">📍</div>
            <div class="ic-label">Service Location</div>
            <div class="ic-value"><?= htmlspecialchars($member['city'] ?: 'Global / Remote') ?></div>
        </div>
        <div class="info-card">
            <div class="ic-icon">💎</div>
            <div class="ic-label">Membership Tier</div>
            <div class="ic-value" style="color: var(--gold);"><?= ucfirst($member['plan']) ?> Member</div>
        </div>
        <div class="info-card">
            <div class="ic-icon">📅</div>
            <div class="ic-label">Registered Since</div>
            <div class="ic-value"><?= date('M Y', strtotime($member['created_at'])) ?></div>
        </div>
    </div>

    <!-- About Section -->
    <div class="desc-box">
        <div class="desc-title">Business Overview</div>
        <p><?= nl2br(htmlspecialchars($member['description'] ?: 'No business description provided yet.')) ?></p>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
