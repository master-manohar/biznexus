<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/functions.php";
requireLogin();
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$coins = $user["coins"] ?? 0;
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
$stmt2->execute([$user_id]);
$ref_count = $stmt2->fetchColumn();
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE user_id = ?");
$stmt3->execute([$user_id]);
$leads_count = $stmt3->fetchColumn() ?? 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#0a0a0f;color:#e0e0f0;font-family:"Inter",sans-serif}
.navbar{background:#13131a;border-bottom:1px solid #2a2a3a;padding:14px 0}
.navbar-brand{color:#FFD700!important;font-weight:900;font-size:1.4rem}
.nav-link{color:#aaa!important}.nav-link:hover{color:#FFD700!important}
.sidebar{background:#13131a;border-right:1px solid #2a2a3a;min-height:100vh;padding:24px 0}
.sidebar a{display:block;padding:12px 24px;color:#888;text-decoration:none;font-size:.9rem;font-weight:500;border-left:3px solid transparent;transition:.2s}
.sidebar a:hover,.sidebar a.active{color:#FFD700;border-left-color:#FFD700;background:rgba(255,215,0,.05)}
.stat-card{background:#13131a;border:1px solid #2a2a3a;border-radius:16px;padding:28px;margin-bottom:20px}
.stat-num{font-size:2.5rem;font-weight:900;color:#FFD700}
.stat-lbl{color:#555;font-size:.88rem;margin-top:4px}
.section-title{font-size:1.2rem;font-weight:700;margin-bottom:16px;color:#e0e0f0}
</style>
</head>
<body>
<nav class="navbar">
<div class="container-fluid px-4 d-flex align-items-center justify-content-between">
<a class="navbar-brand" href="/dashboard.php">⚡ BizNexus</a>
<div class="d-flex align-items-center gap-3">
<span style="color:#FFD700;font-weight:700">🪙 <?=number_format($coins)?> VooCoins</span>
<span style="color:#888;font-size:.88rem"><?=htmlspecialchars($user["name"] ?? "User")?></span>
<a href="/auth/logout.php" style="color:#555;font-size:.85rem">Logout</a>
</div>
</div>
</nav>
<div class="container-fluid">
<div class="row g-0">
<div class="col-md-2 sidebar">
<a href="/dashboard.php" class="active">🏠 Dashboard</a>
<a href="/profile/edit.php">👤 My Profile</a>
<a href="/marketplace/list.php">🛒 Marketplace</a>
<a href="/referrals/list.php">🤝 Referrals</a>
<a href="/referrals/received.php">📥 Received</a>
<a href="/meetings/schedule.php">📅 Meetings</a>
<a href="/leads.php">📊 Leads</a>
<a href="/coins.php">🪙 VooCoins</a>
<a href="/groups.php">👥 Groups</a>
<a href="/notifications.php">🔔 Notifications</a>
</div>
<div class="col-md-10 p-4">
<h4 class="mb-4" style="color:#FFD700">Welcome back, <?=htmlspecialchars($user["name"] ?? "Member")?>! 👋</h4>
<div class="row">
<div class="col-md-3">
<div class="stat-card">
<div class="stat-num">🪙 <?=number_format($coins)?></div>
<div class="stat-lbl">VooCoins Balance</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-card">
<div class="stat-num"><?=$ref_count?></div>
<div class="stat-lbl">Referrals Given</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-card">
<div class="stat-num"><?=$leads_count?></div>
<div class="stat-lbl">Leads Received</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-card">
<div class="stat-num" style="font-size:1.4rem"><?=ucfirst($user["membership"] ?? "free")?></div>
<div class="stat-lbl">Membership Tier</div>
</div>
</div>
</div>
<div class="stat-card mt-2">
<div class="section-title">Quick Actions</div>
<div class="d-flex gap-3 flex-wrap">
<a href="/referrals/list.php" class="btn btn-warning btn-sm fw-bold">+ Give Referral</a>
<a href="/marketplace/add.php" class="btn btn-outline-warning btn-sm fw-bold">+ List Product</a>
<a href="/meetings/schedule.php" class="btn btn-outline-secondary btn-sm fw-bold">+ Schedule Meeting</a>
<a href="/profile/edit.php" class="btn btn-outline-light btn-sm fw-bold">Edit Profile</a>
</div>
</div>
</div>
</div>
</div>
</body>
</html>