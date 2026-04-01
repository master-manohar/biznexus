<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: /auth/login.php'); exit; }

function getDB(){
    $configs=[['localhost','u175452495_biznexus','u175452495_bizuser','Biz@9990'],['localhost','u175452495_biznexus','u175452495_voo_user','Vooschool@123'],['localhost','u175452495_biznexus','u175452495','Biz@9990']];
    foreach($configs as $c){ try{ return new PDO("mysql:host={$c[0]};dbname={$c[1]};charset=utf8mb4",$c[2],$c[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}

$pdo = getDB();
$uid = (int)$_SESSION['user_id'];
$gid = (int)($_GET['id'] ?? 0);

if (!$gid) {
    // Try to find the user's primary group if no ID provided
    $stmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id = ? AND role != 'pending' LIMIT 1");
    $stmt->execute([$uid]);
    $gid = $stmt->fetchColumn();
    if (!$gid) { die("You are not part of any group. Please join a group first."); }
}

// Check membership
$stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role != 'pending'");
$stmt->execute([$gid, $uid]);
$my_role = $stmt->fetchColumn();
if (!$my_role) { die("Access denied. You are not a member of this group."); }

// Fetch group info
$stmt = $pdo->prepare("SELECT * FROM bizgroups WHERE id = ?");
$stmt->execute([$gid]);
$group = $stmt->fetch();

// Fetch all members
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.phone, gm.role, bp.category, bp.business_name
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    WHERE gm.group_id = ? AND gm.role != 'pending'
    ORDER BY CASE 
        WHEN gm.role = 'president' THEN 1
        WHEN gm.role = 'vice_president' THEN 2
        WHEN gm.role = 'treasurer' THEN 3
        WHEN gm.role = 'secretary' THEN 4
        WHEN gm.role = 'admin' THEN 5
        ELSE 6 END, u.name ASC
");
$stmt->execute([$gid]);
$members = $stmt->fetchAll();

$role_badges = [
    'president' => ['label' => 'President', 'short' => 'P', 'color' => '#FFD700'],
    'vice_president' => ['label' => 'VP', 'short' => 'VP', 'color' => '#00d4ff'],
    'treasurer' => ['label' => 'Treasurer', 'short' => 'T', 'color' => '#00ff88'],
    'secretary' => ['label' => 'Secretary', 'short' => 'S', 'color' => '#ff6b6b'],
    'admin' => ['label' => 'Admin', 'short' => 'A', 'color' => '#6c63ff']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($group['name']) ?> Members – BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body{background:#06060a;color:#e0e0f0;font-family:'DM Sans',sans-serif}
.sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0a0a12;border-right:1px solid #1e1e2e;display:flex;flex-direction:column;z-index:100}
.sidebar-logo{padding:22px 20px;font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:900;color:#FFD700;border-bottom:1px solid #1e1e2e}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#777;font-size:.85rem;font-weight:500;transition:.15s;text-decoration:none}
.nav-link:hover,.nav-link.active{color:#FFD700;background:rgba(255,215,0,.06)}
.main{margin-left:220px;padding:32px}
.member-card{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:20px;transition:.2s;display:flex;align-items:center;gap:15px;margin-bottom:16px}
.member-card:hover{border-color:rgba(255,215,0,.2);background:#12121c}
.member-avatar{width:50px;height:50px;background:#1e1e2e;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:#555;flex-shrink:0}
.member-info{flex:1}
.member-name{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:800;margin-bottom:2px;display:flex;align-items:center;gap:8px}
.member-cat{font-size:.78rem;color:#FFD700;margin-bottom:2px}
.member-biz{font-size:.75rem;color:#888}
.member-contact{font-size:.72rem;color:#555;margin-top:4px;display:flex;gap:12px}
.role-badge{padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px}
.role-initial{width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:900;border:1px solid currentColor}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav style="flex:1;padding:12px 0;">
    <a href="/dashboard.php" class="nav-link">🏠 Dashboard</a>
    <a href="/groups/index.php" class="nav-link active">👥 Groups</a>
    <a href="/profile/edit.php" class="nav-link">👤 My Profile</a>
  </nav>
</div>

<div class="main">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem">
      <li class="breadcrumb-item"><a href="/groups/index.php" style="color:#777;text-decoration:none">Groups</a></li>
      <li class="breadcrumb-item active" style="color:#FFD700"><?= htmlspecialchars($group['name']) ?></li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($group['name']) ?> Members 👥</h1>
      <p style="color:#777;font-size:.88rem">Trusted business partners in <?= htmlspecialchars($group['city']) ?></p>
    </div>
  </div>

  <div class="row">
    <?php foreach($members as $m): 
        $role = $m['role'];
        $badge = $role_badges[$role] ?? null;
    ?>
    <div class="col-md-6 col-lg-6">
      <div class="member-card">
        <div class="member-avatar"><?= substr($m['name'],0,1) ?></div>
        <div class="member-info">
          <div class="member-name">
            <?= htmlspecialchars($m['name']) ?>
            <?php if($badge): ?>
              <span class="role-initial" style="color:<?= $badge['color'] ?>; background:<?= $badge['color'] ?>11" title="<?= $badge['label'] ?>"><?= $badge['short'] ?></span>
            <?php endif; ?>
          </div>
          <div class="member-cat"><?= htmlspecialchars($m['category'] ?? 'Business Owner') ?></div>
          <div class="member-biz"><?= htmlspecialchars($m['business_name'] ?? '') ?></div>
          <div class="member-contact">
            <span>📧 <?= htmlspecialchars($m['email']) ?></span>
            <span>📱 <?= htmlspecialchars($m['phone']) ?></span>
          </div>
        </div>
        <a href="https://wa.me/<?= preg_replace('/\D/','',$m['phone']) ?>" target="_blank" style="background:#25D36622;color:#25D366;padding:8px;border-radius:10px;text-decoration:none">💬</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if(empty($members)): ?>
    <div class="text-center py-5" style="color:#555">No members found in this group.</div>
  <?php endif; ?>
</div>
</body>
</html>
