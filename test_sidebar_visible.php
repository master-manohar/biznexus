<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// Mock a user session
$_SESSION['user_id'] = 1;

// Mock the getUnreadCount function if necessary or just include the functions
require_once 'includes/db.php';
require_once 'includes_functions.php';

// Mock getUnreadCount to avoid DB issues during test if needed
function getUnreadCountMock($pdo, $uid) { return 5; }

$page_title = "Sidebar Test";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/dashboard-sidebar.css">
</head>
<body style="background: #0a0a0f;">

<nav class="top-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle btn text-white d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="#" class="navbar-brand-custom">
            <div class="brand-logo">B</div>
            <span class="brand-text">BizNexus</span>
        </a>
    </div>
</nav>

<aside class="sidebar" id="sidebar" style="transform: none !important; display: flex !important;">
    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Main Menu</div>
        <a href="#" class="sidebar-link active">
            <span class="nav-emoji">🏠</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="#" class="sidebar-link">
            <span class="nav-emoji">💼</span>
            <span class="nav-label">CRM Pipeline</span>
        </a>
    </nav>
</aside>

<main class="main-content">
    <div class="container py-5">
        <h1 class="text-white">Sidebar Visibility Test</h1>
        <p class="text-muted">If you can see the sidebar on the left, then the CSS is working.</p>
    </div>
</main>

</body>
</html>
