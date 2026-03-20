<?php
require_once '../includes/layout_start.php';
?>

<div class="container-fluid px-4 py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-gradient-gold mb-3">Group Tiers & Benefits</h1>
        <p class="lead text-muted mx-auto" style="max-width: 700px;">
            Choose the right community tier to accelerate your business growth. 
            Automated grouping ensures you're always surrounded by active, high-value members.
        </p>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle premium-table">
            <thead>
                <tr class="bg-black text-uppercase small letter-spacing-1">
                    <th class="py-4 ps-4">Benefit / Feature</th>
                    <th class="py-4 text-center">Nexus (Free)</th>
                    <th class="py-4 text-center text-gold">Omkara</th>
                    <th class="py-4 text-center text-diamond">Diamond</th>
                    <th class="py-4 text-center text-info">Charminar</th>
                    <th class="py-4 text-center text-purple">Tajmahal</th>
                    <th class="py-4 text-center text-warning">Gold</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="ps-4 fw-bold">Price (Monthly)</td>
                    <td class="text-center">₹0</td>
                    <td class="text-center">₹999</td>
                    <td class="text-center">₹2,499</td>
                    <td class="text-center">₹4,999</td>
                    <td class="text-center">₹9,999</td>
                    <td class="text-center">₹19,999</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">AI Business Advisor</td>
                    <td class="text-center text-muted">Basic</td>
                    <td class="text-center">Standard</td>
                    <td class="text-center">Advanced</td>
                    <td class="text-center">Pro</td>
                    <td class="text-center">Enterprise</td>
                    <td class="text-center">Custom AI Agent</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">Leads Per Day</td>
                    <td class="text-center">1</td>
                    <td class="text-center">5</td>
                    <td class="text-center">15</td>
                    <td class="text-center">30</td>
                    <td class="text-center">50</td>
                    <td class="text-center">Unlimited</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">Priority Lead Dispatch</td>
                    <td class="text-center text-muted">No</td>
                    <td class="text-center text-muted">No</td>
                    <td class="text-center text-success">Low</td>
                    <td class="text-center text-success">Medium</td>
                    <td class="text-center text-success">High</td>
                    <td class="text-center text-success">Instant</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">Marketplace Listings</td>
                    <td class="text-center">2</td>
                    <td class="text-center">10</td>
                    <td class="text-center">25</td>
                    <td class="text-center">Unlimited</td>
                    <td class="text-center">Unlimited</td>
                    <td class="text-center">Unlimited (Featured)</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">WhatsApp Automation</td>
                    <td class="text-center text-muted">No</td>
                    <td class="text-center text-muted">No</td>
                    <td class="text-center text-success">✓</td>
                    <td class="text-center text-success">✓</td>
                    <td class="text-center text-success">✓</td>
                    <td class="text-center text-success">Managed</td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">Meeting Room Credits</td>
                    <td class="text-center">0</td>
                    <td class="text-center">2</td>
                    <td class="text-center">5</td>
                    <td class="text-center">10</td>
                    <td class="text-center">25</td>
                    <td class="text-center">Unlimited</td>
                </tr>
                <tr class="bg-dark-subtle">
                    <td class="ps-4"></td>
                    <td class="text-center py-4">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" disabled>Current</button>
                    </td>
                    <td class="text-center py-4">
                        <a href="../membership/upgrade.php?plan=omkara" class="btn btn-gold btn-sm rounded-pill px-3">Upgrade</a>
                    </td>
                    <td class="text-center py-4">
                        <a href="../membership/upgrade.php?plan=diamond" class="btn btn-light btn-sm rounded-pill px-3">Upgrade</a>
                    </td>
                    <td class="text-center py-4">
                        <a href="../membership/upgrade.php?plan=charminar" class="btn btn-info btn-sm rounded-pill px-4 text-white">Upgrade</a>
                    </td>
                    <td class="text-center py-4">
                        <a href="../membership/upgrade.php?plan=tajmahal" class="btn btn-primary btn-sm rounded-pill px-4">Upgrade</a>
                    </td>
                    <td class="text-center py-4">
                        <a href="../membership/upgrade.php?plan=gold" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold text-black">Elite Access</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tier Descriptions -->
    <div class="row g-4 mt-5">
        <div class="col-md-4">
            <div class="card bg-dark border-secondary h-100 p-3">
                <div class="card-body">
                    <h5 class="text-gold fw-bold mb-3">Automated Networking</h5>
                    <p class="text-muted small">Our algorithm automatically pairs you with members in your category, ensuring every connection is relevant to your business domain.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark border-secondary h-100 p-3">
                <div class="card-body">
                    <h5 class="text-success fw-bold mb-3">Group Roles (Presidency)</h5>
                    <p class="text-muted small">Active members get the chance to lead their group as President for 90 days, gaining massive visibility and leadership rewards.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark border-secondary h-100 p-3">
                <div class="card-body">
                    <h5 class="text-info fw-bold mb-3">Activity-Based Movement</h5>
                    <p class="text-muted small">Stay active to stay in the elite groups. Inactive members are automatically rotated to secondary groups to maintain community quality.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.premium-table {
    border-collapse: separate;
    border-spacing: 0 10px;
    background: transparent !important;
}
.premium-table thead th {
    background: #000;
    border: none !important;
}
.premium-table tbody tr {
    background: #111;
    transition: transform 0.2s;
}
.premium-table tbody tr:hover {
    background: #181818;
    transform: scale(1.002);
}
.premium-table td {
    border: none !important;
    padding-top: 20px;
    padding-bottom: 20px;
}
.premium-table tr:first-child td:first-child { border-top-left-radius: 12px; }
.premium-table tr:first-child td:last-child { border-top-right-radius: 12px; }
.premium-table tr:last-child td:first-child { border-bottom-left-radius: 12px; }
.premium-table tr:last-child td:last-child { border-bottom-right-radius: 12px; }

.text-gradient-gold {
    background: linear-gradient(135deg, #FFD700, #e6a800);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.text-diamond { color: #00d4ff; }
.text-purple { color: #a259ff; }
.letter-spacing-1 { letter-spacing: 1px; }
</style>

<?php require_once '../includes/layout_end.php'; ?>
