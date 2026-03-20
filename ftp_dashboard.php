<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$is_verified = $user['is_verified'] ?? 0;

// Fetch coin balance
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as balance FROM coin_transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$coin_data = $stmt->fetch(PDO::FETCH_ASSOC);
$coin_balance = $coin_data['balance'] ?? 0;

// Fetch referrals sent count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = ?");
$stmt->execute([$user_id]);
$ref_sent = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// Fetch referrals received count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM referrals WHERE referred_id = ?");
$stmt->execute([$user_id]);
$ref_received = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// Fetch CRM leads count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM crm_leads WHERE user_id = ?");
$stmt->execute([$user_id]);
$crm_count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// Fetch invoices count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM invoices WHERE user_id = ?");
$stmt->execute([$user_id]);
$invoice_count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

// Fetch recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$member_name = htmlspecialchars($user['full_name'] ?? $user['name'] ?? 'Member');
$member_initial = strtoupper(substr($member_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BizNexus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0f;
            --card-dark: #13131a;
            --gold: #FFD700;
            --green: #00ff88;
            --sidebar-width: 250px;
            --border-color: #2a2a3a;
            --text-muted-custom: #7a7a9a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-dark);
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ========== TOP NAVBAR ========== */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: var(--card-dark);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.4);
        }

        .navbar-brand-custom {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--gold), #ff8c00);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 18px;
            color: #0a0a0f;
        }

        .brand-text {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold), #ff8c00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .coin-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 25px;
            padding: 6px 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .coin-badge:hover {
            background: rgba(255, 215, 0, 0.2);
            border-color: var(--gold);
        }

        .coin-badge .coin-icon {
            font-size: 18px;
        }

        .coin-badge .coin-amount {
            color: var(--gold);
            font-weight: 700;
            font-size: 15px;
        }

        .member-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c63ff, #4834d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            color: white;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: border-color 0.3s;
        }

        .member-avatar:hover {
            border-color: var(--gold);
        }

        .member-name-display {
            color: #c0c0d0;
            font-size: 14px;
            font-weight: 500;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            color: #a0a0b0;
            font-size: 20px;
            transition: color 0.3s;
        }

        .notification-bell:hover {
            color: var(--gold);
        }

        .notif-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 10px;
            height: 10px;
            background: #ff4444;
            border-radius: 50%;
            border: 2px solid var(--card-dark);
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - 64px);
            background: var(--card-dark);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 900;
            transition: transform 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }

        .sidebar-nav {
            padding: 16px 0;
            flex: 1;
        }

        .sidebar-section-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted-custom);
            padding: 12px 20px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: #a0a0b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            position: relative;
        }

        .sidebar-link:hover {
            background: rgba(255, 215, 0, 0.05);
            color: #e0e0f0;
            border-left-color: rgba(255, 215, 0, 0.4);
        }

        .sidebar-link.active {
            background: rgba(255, 215, 0, 0.08);
            color: var(--gold);
            border-left-color: var(--gold);
        }

        .sidebar-link .nav-emoji {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-link .nav-label {
            flex: 1;
        }

        .sidebar-link .nav-badge {
            background: var(--gold);
            color: #0a0a0f;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border-color);
            margin: 10px 16px;
        }

        .sidebar-logout {
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 68, 68, 0.08);
            border: 1px solid rgba(255, 68, 68, 0.2);
            border-radius: 10px;
            color: #ff6b6b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(255, 68, 68, 0.15);
            border-color: #ff4444;
            color: #ff4444;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 64px;
            padding: 28px;
            min-height: calc(100vh - 64px);
        }

        /* ========== PAGE HEADER ========== */
        .page-header {
            margin-bottom: 28px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            color: #e8e8f0;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: var(--text-muted-custom);
            font-size: 14px;
        }

        .page-subtitle span {
            color: var(--gold);
            font-weight: 600;
        }

        /* ========== STAT CARDS ========== */
        .stat-card {
            background: var(--card-dark);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 22px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-accent, var(--gold));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card:hover {
            border-color: rgba(255, 215, 0, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card.gold-accent { --card-accent: var(--gold); }
        .stat-card.green-accent { --card-accent: var(--green); }
        .stat-card.blue-accent { --card-accent: #6c63ff; }
        .stat-card.purple-accent { --card-accent: #ff6b9d; }

        .stat-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-icon.gold-bg { background: rgba(255, 215, 0, 0.12); }
        .stat-icon.green-bg { background: rgba(0, 255, 136, 0.12); }
        .stat-icon.blue-bg { background: rgba(108, 99, 255, 0.12); }
        .stat-icon.purple-bg { background: rgba(255, 107, 157, 0.12); }

        .stat-trend {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .trend-up {
            background: rgba(0, 255, 136, 0.1);
            color: var(--green);
        }

        .trend-neutral {
            background: rgba(160, 160, 180, 0.1);
            color: #a0a0b0;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #f0f0ff;
            margin-bottom: 4px;
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted-custom);
            font-weight: 500;
        }

        /* ========== COIN BALANCE HERO ========== */
        .coin-hero {
            background: var(--card-dark);
            border: 1px solid rgba(255, 215, 0, 0.25);
            border-radius: 20px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .coin-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .coin-hero-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .coin-hero-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .coin-hero-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--gold), #ff8c00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
            animation: coinGlow 2s ease-in-out infinite alternate;
        }

        @keyframes coinGlow {
            from { box-shadow: 0 0 20px rgba(255, 215, 0, 0.25); }
            to { box-shadow: 0 0 40px rgba(255, 215, 0, 0.5); }
        }

        .coin-hero-info .coin-label {
            font-size: 13px;
            color: var(--text-muted-custom);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .coin-hero-info .coin-big-value {
            font-size: 48px;
            font-weight: 900;
            color: var(--gold);
            line-height: 1;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }

        .coin-hero-info .coin-sub {
            font-size: 13px;
            color: #a0a0b0;
            margin-top: 4px;
        }

        .coin-hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-earn {
            background: linear-gradient(135deg, var(--gold), #ff8c00);
            color: #0a0a0f;
            font-weight: 700;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-earn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.35);
            color: #0a0a0f;
        }

        .btn-redeem {
            background: transparent;
            color: var(--gold);
            font-weight: 600;
            border: 1px solid rgba(255, 215, 0, 0.4);
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-redeem:hover {
            background: rgba(255, 215, 0, 0.08);
            border-color: var(--gold);
            color: var(--gold);
        }

        /* ========== SECTION CARDS ========== */
        .section-card {
            background: var(--card-dark);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 22px;
            height: 100%;
        }

        .section-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .section-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #e0e0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-card-title .title-emoji {
            font-size: 20px;
        }

        .view-all-link {
            font-size: 12px;
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .view-all-link:hover {
            opacity: 0.7;
            color: var(--gold);
        }

        /* ========== NOTIFICATIONS ========== */
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .notif-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .notif-dot-item {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
            margin-top: 5px;
            flex-shrink: 0;
        }

        .notif-dot-item.read {
            background: var(--border-color);
        }

        .notif-text {
            font-size: 13px;
            color: #c0c0d8;
            line-height: 1.5;
            flex: 1;
        }

        .notif-time {
            font-size: 11px;
            color: var(--text-muted-custom);
            flex-shrink: 0;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-muted-custom);
        }

        .empty-state-emoji {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state-text {
            font-size: 14px;
        }

        /* ========== QUICK ACTIONS ========== */
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 18px 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            text-decoration: none;
            color: #c0c0d8;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.25s;
            text-align: center;
        }

        .quick-action-btn:hover {
            background: rgba(255, 215, 0, 0.07);
            border-color: rgba(255, 215, 0, 0.35);
            color: var(--gold);
            transform: translateY(-3px);
        }

        .quick-action-btn .qa-emoji {
            font-size: 26px;
        }

        /* ========== ACTIVITY FEED ========== */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c63ff, #4834d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .activity-info {
            flex: 1;
        }

        .activity-title {
            font-size: 13px;
            color: #d0d0e8;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .activity-sub {
            font-size: 11px;
            color: var(--text-muted-custom);
        }

        .activity-value {
            font-size: 13px;
            font-weight: 700;
        }

        .activity-value.positive { color: var(--green); }
        .activity-value.negative { color: #ff6b6b; }
        .activity-value.neutral { color: var(--gold); }

        /* ========== PROGRESS BAR ========== */
        .progress-custom {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--gold), #ff8c00);
            transition: width 1s ease;
        }

        /* ========== GREETING BANNER ========== */
        .greeting-banner {
            background: linear-gradient(135deg, rgba(255,215,0,0.07), rgba(0,255,136,0.04));
            border: 1px solid rgba(255,215,0,0.15);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .greeting-left h2 {
            font-size: 22px;
            font-weight: 700;
            color: #f0f0ff;
            margin-bottom: 4px;
        }

        .greeting-left p {
            font-size: 14px;
            color: #8080a0;
        }

        .greeting-left span.highlight {
            color: var(--gold);
        }

        .greeting-date {
            font-size: 13px;
            color: #7070a0;
            text-align: right;
        }

        /* ========== MOBILE TOGGLE ========== */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: #a0a0b8;
            font-size: 22px;
            cursor: pointer;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 16px;
            }

            .coin-big-value {
                font-size: 36px !important;
            }

            .stat-value {
                font-size: 26px;
            }

            .page-title {
                font-size: 20px;
            }

            .member-name-display {
                display: none;
            }
        }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        /* ========== OVERLAY ========== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 850;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="top-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/dashboard/" class="navbar-brand-custom">
            <div class="brand-logo">B</div>
            <span class="brand-text">BizNexus</span>
        </a>
    </div>

    <div class="navbar-right">
        <div class="notification-bell" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if (count($notifications) > 0): ?>
            <span class="notif-dot"></span>
            <?php endif; ?>
        </div>

        <a href="/dashboard/coins.php" class="coin-badge text-decoration-none">
            <span class="coin-icon">🪙</span>
            <span class="coin-amount"><?= number_format($coin_balance) ?></span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <span class="member-name-display">
                <?= $member_name ?>
                <?php if($is_verified): ?><span title="BizNexus Verified" style="color:#FFD700;font-size:14px;margin-left:4px;">✓</span><?php endif; ?>
            </span>
            <div class="member-avatar" title="<?= $member_name ?>">
                <?= $member_initial ?>
            </div>
        </div>
    </div>
</nav>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main Menu</div>

        <a href="/dashboard/" class="sidebar-link active">
            <span class="nav-emoji">🏠</span>
            <span class="nav-label">Dashboard</span>
        </a>

        <a href="/dashboard/leads.php" class="sidebar-link">
            <span class="nav-emoji">💼</span>
            <span class="nav-label">CRM Pipeline</span>
        </a>

        <a href="/dashboard/profile.php" class="sidebar-link">
            <span class="nav-emoji">👤</span>
            <span class="nav-label">Profile</span>
        </a>

        <div class="greeting-banner">
    <div class="greeting-left">
        <h2>Welcome back, <span class="highlight"><?= $member_name ?></span>! 👋 
            <?php if($is_verified): ?><span class="badge" style="background:#FFD700;color:#000;font-size:0.75rem;vertical-align:middle;margin-left:10px;">BizNexus Verified ✓</span><?php endif; ?>
        </h2>
        <p>Your networking dashboard is ready. Give referrals and manage your leads.</p>
    </div>
    <div class="greeting-date">
        <strong><?= date('l, F j') ?></strong><br>
        Earn 50 🪙 per referral
    </div>
</div>

        <a href="/dashboard/referrals.php" class="sidebar-link">
            <span class="nav-emoji">🤝</span>
            <span class="nav-label">Referrals</span>
            <?php if ($ref_sent > 0): ?>
            <span class="nav-badge"><?= $ref_sent ?></span>
            <?php endif; ?>
        </a>

        <a href="/dashboard