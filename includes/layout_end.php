<?php
$is_dashboard = (strpos($_SERVER['REQUEST_URI'], '/dashboard/index.php') !== false);
?>
<!-- AI Support Chat Widget (Global) -->
<?php
$is_logged_in = isset($_SESSION['user_id']);
$bot_endpoint = $is_logged_in ? '/api/support_bot_chat.php' : '/api/public_bot_chat.php';
?>
<script>
window.BizBotConfig = {
    endpoint: '<?= $bot_endpoint ?>',
    autoOpen: <?= ($is_dashboard && $is_logged_in) ? 'true' : 'false' ?>,
    autoOpenDelay: 1500
};
</script>
<script src="/assets/js/nexus_bot.js"></script>
<!-- End Chat Widget -->
<!-- BookAnEvent Company Footer -- required for Razorpay compliance -->
<div style="margin-left:var(--sidebar-width,250px);padding:14px 28px;border-top:1px solid #1a1a2a;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:.72rem;color:#555577;">
    <div>
        <span style="color:#8888aa;">© 2025 <strong style="color:#9090b8;">BizNexus</strong> -- A product of
        <a href="https://bookanevent.in" target="_blank" style="color:#FFD700;text-decoration:none;font-weight:600;">BookAnEvent</a>.
        All rights reserved.</span>
    </div>
    <div style="display:flex;gap:16px;">
        <a href="/pages/terms.php" style="color:#555577;text-decoration:none;">Terms</a>
        <a href="/pages/privacy.php" style="color:#555577;text-decoration:none;">Privacy</a>
        <a href="/pages/contact.php" style="color:#555577;text-decoration:none;">Contact</a>
        <a href="/pages/about.php" style="color:#555577;text-decoration:none;">About</a>
    </div>
</div>

</main>

<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }


</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
