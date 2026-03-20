<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
$uid = $_SESSION['user_id'] ?? null;
$user = $_SESSION['user'] ?? [];
$coins = $user['coins'] ?? 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?? 'BizNexus' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background:#13131a;border-bottom:1px solid #2a2a3a;padding:12px 0">
<div class="container-fluid px-4">
  <a class="navbar-brand" href="/dashboard.php" style="color:#FFD700;font-weight:900;font-size:1.4rem">⚡ BizNexus</a>
  <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" style="color:#aaa">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navMain">
    <ul class="navbar-nav me-auto gap-1 ms-3">
      <li class="nav-item"><a class="nav-link" href="/dashboard.php" style="color:<?=$current_page==='dashboard.php'?'#FFD700':'#888'?>">🏠 Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="/profile/list.php" style="color:#888">👥 Members</a></li>
      <li class="nav-item"><a class="nav-link" href="/marketplace/list.php" style="color:#888">🛒 Marketplace</a></li>
      <li class="nav-item"><a class="nav-link" href="/leads/list.php" style="color:#888">📊 Leads</a></li>
      <li class="nav-item"><a class="nav-link" href="/referrals/list.php" style="color:#888">🤝 Referrals</a></li>
      <li class="nav-item"><a class="nav-link" href="/groups/list.php" style="color:#888">👥 Groups</a></li>
    </ul>
    <div class="d-flex align-items-center gap-3">
      <a href="/coins/wallet.php" style="color:#FFD700;text-decoration:none;font-weight:700;font-size:.88rem">🪙 <?=number_format($coins)?></a>
      <a href="/notifications/list.php" style="color:#888;font-size:1.1rem">🔔</a>
      <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle" style="background:#1a1a24;border:1px solid #2a2a3a;color:#e0e0f0;font-size:.85rem" data-bs-toggle="dropdown">
          <?=htmlspecialchars(substr($user['name'] ?? 'Account', 0, 15))?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="background:#13131a;border:1px solid #2a2a3a">
          <li><a class="dropdown-item" href="/profile/view.php" style="color:#aaa">👤 My Profile</a></li>
          <li><a class="dropdown-item" href="/leads/list.php" style="color:#aaa">📇 CRM</a></li>
          <li><a class="dropdown-item" href="/invoice/list.php" style="color:#aaa">🧾 Invoices</a></li>
          <li><hr class="dropdown-divider" style="border-color:#2a2a3a"></li>
          <li><a class="dropdown-item" href="/auth/logout.php" style="color:#ff6666">🚪 Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>
</nav>
<div class="container-xl py-4">
