<?php
// admin/links.php — BizNexus Admin Links Hub
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth_check.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied.");
}

$page_title = 'Admin Links Hub — BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';

$sections = [
    '🔧 Admin & Control' => [
        ['Mission Roadmap', '/admin/roadmap.php', 'Track all features from Planned to Live. Run Agent to deploy.', '#FFD700'],
        ['SuperAdmin Dashboard', '/superadmin.php?s=dashboard', 'Member counts, revenue, and platform KPIs.', '#a259ff'],
        ['Members Management', '/superadmin.php?s=members', 'Search, edit, and manage all registered users.', '#4488ff'],
        ['Manage Coupons', '/admin/manage_coupons.php', 'Create and manage discount/referral coupons.', '#00e87a'],
        ['SuperAdmin Links', '/superadmin.php?s=links', 'External links and platform integrations.', '#ff9900'],
    ],
    '💼 CRM & Leads' => [
        ['Add Contact', '/leads/add_contact.php', 'Quickly add a new business contact (with email + WhatsApp).', '#00e87a'],
        ['CRM Pipeline', '/leads/list.php', 'View and manage all captured leads.', '#4488ff'],
        ['AI Lead Center', '/superadmin.php?s=leads', 'AI-assisted lead discovery and management.', '#a259ff'],
    ],
    '🌐 Site & SEO' => [
        ['SEO Dashboard', '/admin/seo_dashboard.php', 'Track total pages generated, view categories/cities distribution.', '#FFD700'],
        ['SEO Monitor (Live)', '/admin/seo_monitor.php', 'Real-time progress tracker for bulk page generation.', '#00e87a'],
        ['Bulk Page Generator', '/agent/bulk_seo_agent.php?key=BizCron2024', 'Runs the agent: generates 10 SEO pages every 3 seconds.', '#a259ff'],
        ['Browse / Find Pages', '/find.php', 'Public search across all generated SEO city/category pages.', '#4488ff'],
        ['Homepage', '/', 'The public-facing homepage.', '#00e87a'],
        ['Sitemap', '/sitemap.php', 'Live XML sitemap for Google Search Console.', '#555577'],
    ],
    '💳 Payments & Membership' => [
        ['Upgrade Page', '/membership/upgrade.php', 'User-facing plan upgrade and Razorpay checkout.', '#FFD700'],
        ['Payment Bridge', 'https://bookanevent.in/pay_biznexus.php', 'Secure checkout bridge (external, bookanevent.in).', '#ff9900'],
    ],
    '⚙️ User Features' => [
        ['Profile Edit', '/profile/edit.php', 'User profile, KYC, and business details.', '#4488ff'],
        ['Notifications', '/notifications/index.php', 'System notifications for all members.', '#a259ff'],
        ['Referrals', '/referrals/list.php', 'Track and manage referral links and rewards.', '#00e87a'],
        ['Marketplace', '/marketplace/list.php', 'Business service listings marketplace.', '#FFD700'],
        ['Quotes', '/quotes/list.php', 'Business quotation system for premium users.', '#4488ff'],
        ['Invoices', '/invoices/list.php', 'Invoice generation and management.', '#a259ff'],
    ],
    '🤝 Meetings & Groups' => [
        ['Schedule Meeting', '/meetings/schedule.php', 'Schedule and manage H2H or group meetings.', '#FFD700'],
        ['Meeting Attendance', '/meetings/attendance.php', 'Track member attendance at meetings.', '#4488ff'],
        ['H2H Lead Tool', '/admin/h2h_lead.php', 'Original H2H meeting lead capture tool.', '#00e87a'],
    ],
];
?>

<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 style="font-family:'Syne',sans-serif; font-weight:800; color:#FFD700; margin:0;">🔗 Admin Links Hub</h2>
            <p style="color:#888; margin:4px 0 0; font-size:.85rem;">Every feature link in one place — no more asking!</p>
        </div>
        <input id="linkSearch" type="search" placeholder="🔍 Search..." class="form-control"
               style="max-width:220px; background:#0d0d16; border-color:#2a2a3a; color:#fff;">
    </div>

    <div id="linksGrid">
    <?php foreach ($sections as $title => $links): ?>
    <div class="link-section mb-4">
        <div class="sidebar-section-title mb-3" style="font-size:.75rem; text-transform:uppercase; letter-spacing:2px; color:#445;"><?= $title ?></div>
        <div class="row g-3">
            <?php foreach ($links as $l): ?>
            <div class="col-12 col-md-6 col-xl-4 link-card-wrap">
                <a href="<?= $l[0] === 'Payment Bridge' ? $l[1] : $l[1] ?>"
                   target="<?= str_starts_with($l[1], 'http') ? '_blank' : '_self' ?>"
                   class="d-block text-decoration-none h-100"
                   style="background:#13131a; border:1px solid <?= $l[3] ?>33; border-radius:14px; padding:18px 20px; transition:all .2s; position:relative; overflow:hidden;"
                   onmouseover="this.style.borderColor='<?= $l[3] ?>88'; this.style.boxShadow='0 4px 20px <?= $l[3] ?>22';"
                   onmouseout="this.style.borderColor='<?= $l[3] ?>33'; this.style.boxShadow='none';">
                    <div style="position:absolute; top:0; right:0; width:60px; height:60px; background:radial-gradient(<?= $l[3] ?>22, transparent); border-radius:0 14px 0 60px;"></div>
                    <div style="font-weight:800; color:#fff; margin-bottom:6px;"><?= htmlspecialchars($l[0]) ?></div>
                    <div style="font-size:.78rem; color:#888;"><?= htmlspecialchars($l[2]) ?></div>
                    <div style="font-size:.7rem; color:<?= $l[3] ?>; margin-top:10px; font-weight:600;">
                        <?= htmlspecialchars($l[1]) ?> →
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('linkSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.link-card-wrap').forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
    document.querySelectorAll('.link-section').forEach(section => {
        const visible = [...section.querySelectorAll('.link-card-wrap')].some(c => c.style.display !== 'none');
        section.style.display = visible ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
