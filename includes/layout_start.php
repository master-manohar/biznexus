<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes_functions.php';

$user_id = $_SESSION['user_id'];

// Global Stats for Sidebar/Navbar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COALESCE(v.balance, 0) as balance FROM voocoin_balances v WHERE user_id = ?");
$stmt->execute([$user_id]);
$coin_balance = $stmt->fetchColumn() ?: 0;

$unread_notifs = getUnreadCount($pdo, $user_id);

$member_name = htmlspecialchars($user['name'] ?? 'Member');
$member_initial = strtoupper(substr($member_name, 0, 1));
$is_verified = $user['is_verified'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
$active_dash = ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) ? 'active' : '';
$active_crm = (strpos($_SERVER['REQUEST_URI'], '/leads/') !== false) ? 'active' : '';
$active_profile = (strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) ? 'active' : '';
$active_ref = (strpos($_SERVER['REQUEST_URI'], '/referrals/') !== false) ? 'active' : '';
$active_market = (strpos($_SERVER['REQUEST_URI'], '/marketplace/') !== false) ? 'active' : '';
$active_coins = (strpos($_SERVER['REQUEST_URI'], '/coins/') !== false) ? 'active' : '';
$active_quotes = (strpos($_SERVER['REQUEST_URI'], '/quotes/') !== false) ? 'active' : '';
$active_invoices = (strpos($_SERVER['REQUEST_URI'], '/invoices/') !== false) ? 'active' : '';
$active_notifs = (strpos($_SERVER['REQUEST_URI'], '/notifications/') !== false) ? 'active' : '';

$is_premium = ($user['plan'] ?? 'free') !== 'free' || ($user['membership'] ?? 'free') !== 'free';

// Group role & name for sidebar display
$group_role = $user['group_role'] ?? 'member';
$group_id   = $user['group_id'] ?? null;
$group_name = '';
if ($group_id) {
    $grpStmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
    $grpStmt->execute([$group_id]);
    $group_name = $grpStmt->fetchColumn() ?: '';
}

$role_icons = [
    'president'      => ['icon'=>'👑','color'=>'#FFD700', 'label'=>'President'],
    'vice_president' => ['icon'=>'🌟','color'=>'#00e87a', 'label'=>'Vice President'],
    'gen_secretary'  => ['icon'=>'📋','color'=>'#4488ff', 'label'=>'Gen. Secretary'],
    'treasurer'      => ['icon'=>'💰','color'=>'#a259ff', 'label'=>'Treasurer'],
    'joint_secretary'=> ['icon'=>'🤝','color'=>'#ff9900', 'label'=>'Joint Secretary'],
    'member'         => ['icon'=>'👤','color'=>'#555577', 'label'=>'Member'],
];
$my_role_info = $role_icons[$group_role] ?? $role_icons['member'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/assets/img/logo-icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/dashboard-sidebar.css">
    <style>
        html, body {
            background-color: #0a0a0f !important;
            color: #c8c8e0 !important;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .main-content {
            background-color: #0a0a0f !important;
            color: #c8c8e0;
        }
        .bg-white, .bg-light { background-color: #13131a !important; }
        .card { background-color: #13131a !important; border-color: #1e1e2e !important; color: #c8c8e0 !important; }
        .card-body { color: #c8c8e0 !important; }
        .text-muted     { color: #9090b8 !important; }
        .text-secondary { color: #9898bc !important; }
        .text-dark      { color: #d8d8f0 !important; }
        h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6 { color: #e8e8f5 !important; }
        .notification-list .card   { background: #13131a !important; }
        .notification-list h6      { color: #e8e8f5 !important; }
        .member-name-display { color: #e0e0f0 !important; font-weight: 600; }
        .navbar-brand-custom .brand-logo { 
            background: transparent !important; 
            box-shadow: none !important; 
            padding: 0 !important;
        }
    </style>
</head>
<body>

<nav class="top-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle btn text-white d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/dashboard/index.php" class="navbar-brand-custom">
            <div class="brand-logo"><img src="/assets/img/logo-icon.png" alt="BizNexus" style="height:28px; vertical-align:middle; filter: drop-shadow(0 0 5px rgba(255,215,0,0.5));"></div>
            <span class="brand-text">BizNexus</span>
        </a>
    </div>
    <div class="navbar-right">
        <a href="/coins/wallet.php" class="coin-badge text-decoration-none">
            <span class="coin-icon">🪙</span>
            <span class="coin-amount"><?= number_format($coin_balance) ?></span>
        </a>
        <a href="/notifications/index.php" class="text-white text-decoration-none position-relative me-2">
            <i class="fas fa-bell fs-5"></i>
            <?php if ($unread_notifs > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                    <?= $unread_notifs > 99 ? '99+' : $unread_notifs ?>
                </span>
            <?php endif; ?>
        </a>
        <div class="d-flex align-items-center gap-2">
            <div class="text-end d-none d-md-block">
                <div class="member-name-display"><?= $member_name ?></div>
                <div style="font-size: 0.65rem; color: <?= $user['email_verified'] ? '#00e87a' : '#ff9900' ?>; font-weight: 700;">
                    <?= $user['email_verified'] ? '✅ Verified' : '⚠️ Unverified' ?>
                </div>
            </div>
            <div class="member-avatar"><?= $member_initial ?></div>
        </div>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main Menu</div>
        <a href="/dashboard/index.php" class="sidebar-link <?= $active_dash ?>">
            <span class="nav-emoji">🏠</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="/leads/list.php" class="sidebar-link <?= $active_crm ?>">
            <span class="nav-emoji">💼</span>
            <span class="nav-label">CRM Pipeline</span>
        </a>
        <a href="/leads/add_contact.php" class="sidebar-link <?= ($current_page == 'add_contact.php') ? 'active' : '' ?>">
            <span class="nav-emoji">📇</span>
            <span class="nav-label">Add Contact</span>
        </a>
        <?php if (in_array($user['role'], ['admin', 'moderator'])): ?>
            <a href="/superadmin.php?s=leads" class="sidebar-link">
                <span class="nav-emoji"><i class="fas fa-bullseye"></i></span>
                <span class="nav-label">Leads Center</span>
            </a>
            <a href="/superadmin.php?s=members" class="sidebar-link">
                <span class="nav-emoji"><i class="fas fa-user-friends"></i></span>
                <span class="nav-label">Members</span>
            </a>
            <a href="/admin/manage_coupons.php" class="sidebar-link">
                <span class="nav-emoji"><i class="fas fa-ticket-alt"></i></span>
                <span class="nav-label">Manage Coupons</span>
            </a>
            <a href="/admin/roadmap.php" class="sidebar-link">
                <span class="nav-emoji">🛰️</span>
                <span class="nav-label">Mission Roadmap</span>
            </a>
            <a href="/admin/links.php" class="sidebar-link <?= ($current_page == 'links.php') ? 'active' : '' ?>">
                <span class="nav-emoji">🔗</span>
                <span class="nav-label">Links Hub</span>
            </a>
        <?php endif; ?>
        <a href="/profile/edit.php" class="sidebar-link <?= $active_profile ?>">
            <span class="nav-emoji">👤</span>
            <span class="nav-label">Profile</span>
            <?php if (!($user['email_verified'] ?? 0)): ?>
                <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.6rem; padding: 2px 5px;">Verify</span>
            <?php endif; ?>
        </a>
        <a href="/referrals/list.php" class="sidebar-link <?= $active_ref ?>">
            <span class="nav-emoji">🤝</span>
            <span class="nav-label">Referrals</span>
        </a>
        <a href="/marketplace/list.php" class="sidebar-link <?= $active_market ?>">
            <span class="nav-emoji">🛒</span>
            <span class="nav-label">Marketplace</span>
        </a>
        <a href="/bizfeed.php" class="sidebar-link <?= ($current_page == 'bizfeed.php') ? 'active' : '' ?>">
            <span class="nav-emoji">📱</span>
            <span class="nav-label">BizFeed</span>
        </a>
        <a href="/notifications/index.php" class="sidebar-link <?= $active_notifs ?>">
            <span class="nav-emoji">🔔</span>
            <span class="nav-label">Notifications</span>
        </a>

        <!-- Networking Ecosystem -->
        <div class="sidebar-section-title mt-4">Networking Ecosystem</div>
        <a href="/dashboard/index.php" class="sidebar-link">
            <span class="nav-emoji">⚡</span>
            <span class="nav-label">Join Session</span>
        </a>
        <?php if (in_array(($user['role'] ?? 'member'), ['admin', 'moderator']) || ($user['group_role'] ?? '') === 'president'): ?>
            <a href="/meetings/schedule.php" class="sidebar-link">
                <span class="nav-emoji">📅</span>
                <span class="nav-label">Schedule</span>
            </a>
            <a href="/meetings/attendance.php" class="sidebar-link">
                <span class="nav-emoji">📊</span>
                <span class="nav-label">Attendance</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-section-title mt-4">Premium Tools</div>
        <a href="<?= $is_premium ? '/quotes/list.php' : '#' ?>" class="sidebar-link <?= $active_quotes ?> <?= !$is_premium ? 'opacity-50' : '' ?>">
            <span class="nav-emoji"><?= $is_premium ? '📄' : '🔒' ?></span>
            <span class="nav-label">Quotes</span>
        </a>
        <a href="<?= $is_premium ? '/invoices/list.php' : '#' ?>" class="sidebar-link <?= $active_invoices ?> <?= !$is_premium ? 'opacity-50' : '' ?>">
            <span class="nav-emoji"><?= $is_premium ? '🧾' : '🔒' ?></span>
            <span class="nav-label">Invoices</span>
        </a>
        <div class="sidebar-divider"></div>
        <?php if ($group_name): ?>
            <div style="padding: 10px 16px; margin-bottom: 4px;">
                <div style="font-size: 0.65rem; color:#444466; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">My Group</div>
                <div style="font-size: 0.75rem; color:#e8e8f0; font-weight:600;"><?= htmlspecialchars($group_name) ?></div>
            </div>
        <?php endif; ?>
        <div class="sidebar-logout">
            <a href="/auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </nav>
</aside>

<main class="main-content">
<?php if (!($user['email_verified'] ?? 0)): ?>
    <div style="background: rgba(255, 153, 0, 0.1); border: 1px solid rgba(255, 153, 0, 0.3); border-radius: 12px; padding: 12px 20px; margin: 15px 20px 0; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.2rem;">⚠️</span>
            <div style="font-size: 0.85rem; color: #e8e8f0;">
                <strong>Email Not Verified:</strong> Please verify your email to secure your account and earn rewards.
            </div>
        </div>
        <a href="/profile/edit.php" style="background: #ff9900; color: #000; border: none; border-radius: 6px; padding: 6px 14px; font-size: 0.75rem; font-weight: 800; text-decoration: none;">Verify Now →</a>
    </div>
<?php endif; ?>
