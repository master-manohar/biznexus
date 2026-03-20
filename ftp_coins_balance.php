<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Get user balance and stats
$stmt = $pdo->prepare("SELECT u.name as username, COALESCE(v.balance, 0) as coins_balance FROM users u LEFT JOIN voocoin_balances v ON u.id = v.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Derived stats from coin_transactions
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_earned FROM coin_transactions WHERE user_id = ? AND type = 'earn'");
$stmt->execute([$user_id]);
$user['coins_earned'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_spent FROM coin_transactions WHERE user_id = ? AND type = 'spend'");
$stmt->execute([$user_id]);
$user['coins_spent'] = $stmt->fetchColumn();

// Get transaction history
$stmt = $pdo->prepare("
    SELECT ct.*, 
           CASE WHEN ct.type = 'earn' THEN '+' ELSE '-' END as sign
    FROM coin_transactions ct
    WHERE ct.user_id = ?
    ORDER BY ct.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly earned
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as monthly_earned
    FROM coin_transactions
    WHERE user_id = ? AND type = 'earn'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
");
$stmt->execute([$user_id]);
$monthly = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user rank
$stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as rank
    FROM users
    WHERE coins_balance > (SELECT coins_balance FROM users WHERE id = ?)
");
$stmt->execute([$user_id]);
$rank = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - BizNexus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a0f;
            color: #e0e0e0;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background: #13131a;
            border-bottom: 1px solid #FFD700;
            padding: 12px 0;
        }
        .navbar-brand {
            color: #FFD700 !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        .nav-link {
            color: #e0e0e0 !important;
            transition: color 0.2s;
        }
        .nav-link:hover { color: #FFD700 !important; }

        /* Wallet Hero */
        .wallet-hero {
            background: linear-gradient(135deg, #13131a 0%, #1a1a2e 50%, #13131a 100%);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .wallet-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.05) 0%, transparent 60%);
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 1; }
        }
        .coin-icon-big {
            font-size: 4rem;
            color: #FFD700;
            margin-bottom: 15px;
            display: block;
            text-shadow: 0 0 30px rgba(255,215,0,0.5);
            animation: coinGlow 2s ease-in-out infinite alternate;
        }
        @keyframes coinGlow {
            from { text-shadow: 0 0 20px rgba(255,215,0,0.5); }
            to { text-shadow: 0 0 50px rgba(255,215,0,0.9), 0 0 80px rgba(255,215,0,0.3); }
        }
        .balance-amount {
            font-size: 4.5rem;
            font-weight: 800;
            color: #FFD700;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 0 30px rgba(255,215,0,0.4);
        }
        .balance-label {
            font-size: 1.1rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,215,0,0.1);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 25px;
            padding: 8px 20px;
            color: #FFD700;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Stat Cards */
        .stat-card {
            background: #13131a;
            border: 1px solid #1e1e2e;
            border-radius: 16px;
            padding: 28px 24px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        .stat-card:hover {
            border-color: rgba(255,215,0,0.3);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
        .stat-card.earned { border-left: 4px solid #00ff88; }
        .stat-card.spent { border-left: 4px solid #ff4757; }
        .stat-card.monthly { border-left: 4px solid #FFD700; }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-value.green { color: #00ff88; }
        .stat-value.red { color: #ff4757; }
        .stat-value.gold { color: #FFD700; }
        .stat-label {
            color: #888;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Transaction Table */
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-dark {
            background: #13131a;
            border: 1px solid #1e1e2e;
            border-radius: 16px;
            overflow: hidden;
        }
        .table-dark-custom {
            color: #e0e0e0;
            margin: 0;
        }
        .table-dark-custom thead th {
            background: #0d0d14;
            color: #FFD700;
            border-color: #1e1e2e;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 14px 18px;
            font-weight: 600;
        }
        .table-dark-custom tbody td {
            background: transparent;
            border-color: #1e1e2e;
            padding: 14px 18px;
            vertical-align: middle;
        }
        .table-dark-custom tbody tr:hover td {
            background: rgba(255,215,0,0.03);
        }
        .tx-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .tx-type-badge.earn {
            background: rgba(0,255,136,0.1);
            color: #00ff88;
            border: 1px solid rgba(0,255,136,0.2);
        }
        .tx-type-badge.spend {
            background: rgba(255,71,87,0.1);
            color: #ff4757;
            border: 1px solid rgba(255,71,87,0.2);
        }
        .tx-amount.positive { color: #00ff88; font-weight: 700; }
        .tx-amount.negative { color: #ff4757; font-weight: 700; }
        .tx-date { color: #666; font-size: 0.85rem; }
        .tx-desc { color: #bbb; font-size: 0.9rem; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #555;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #333;
        }

        /* Quick Actions */
        .quick-action-btn {
            background: #13131a;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            padding: 18px 15px;
            text-align: center;
            color: #e0e0e0;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            border-color: #FFD700;
            color: #FFD700;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.15);
        }
        .quick-action-btn i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 8px;
        }
        .quick-action-btn span {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Progress bar */
        .progress-dark {
            background: #0d0d14;
            height: 8px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .progress-bar-gold {
            background: linear-gradient(90deg, #FFD700, #ffaa00);
            border-radius: 4px;
            height: 100%;
            transition: width 1s ease;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0a0a0f; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #FFD700; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="fas fa-coins me-2"></i>BizNexus
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                style="border-color:#FFD700;">
                <span class="navbar-toggler-icon" style="filter: invert(1) sepia(1) saturate(5) hue-rotate(0deg);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link" href="/dashboard/index.php"><i class="fas fa-home me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/coins/leaderboard.php"><i class="fas fa-trophy me-1"></i>Leaderboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/profile/edit.php"><i class="fas fa-user me-1"></i>Profile</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/logout.php" style="color:#ff4757!important;">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">

        <!-- Wallet Hero -->
        <div class="wallet-hero position-relative">
            <i class="fas fa-coins coin-icon-big"></i>
            <div class="balance-amount"><?php echo number_format($user['coins_balance'] ?? 0); ?></div>
            <div class="balance-label">BizCoins Balance</div>
            <div class="rank-badge">
                <i class="fas fa-crown"></i>
                Rank #<?php echo $rank['rank'] ?? '--'; ?> on Leaderboard
            </div>
            <div class="mt-3" style="color:#666; font-size:0.9rem;">
                Welcome back, <strong style="color:#FFD700;"><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></strong>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card earned">
                    <div class="stat-icon text-success"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-value green"><?php echo number_format($user['coins_earned'] ?? 0); ?></div>
                    <div class="stat-label">Total Earned</div>
                    <div class="progress-dark mt-2">
                        <div class="progress-bar-gold" style="width: 100%; background: linear-gradient(90deg, #00ff88, #00cc6a);"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card spent">
                    <div class="stat-icon text-danger"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-value red"><?php echo number_format($user['coins_spent'] ?? 0); ?></div>
                    <div class="stat-label">Total Spent</div>
                    <div class="progress-dark mt-2">
                        <?php
                            $spent_pct = ($user['coins_earned'] > 0)
                                ? min(100, round(($user['coins_spent'] / $user['coins_earned']) * 100))
                                : 0;
                        ?>
                        <div class="progress-bar-gold" style="width:<?php echo $spent_pct; ?>%; background: linear-gradient(90deg, #ff4757, #cc2030);"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card monthly">
                    <div class="stat-icon" style="color:#FFD700;"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value gold"><?php echo number_format($monthly['monthly_earned'] ?? 0); ?></div>
                    <div class="stat-label">Earned This Month</div>
                    <div class="progress-dark mt-2">
                        <div class="progress-bar-gold" style="width: <?php echo min(100, ($monthly['monthly_earned'] / max(1, $user['coins_earned'])) * 100 * 5); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-5">
            <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="/posts/create.php" class="quick-action-btn">
                        <i class="fas fa-pen-to-square" style="color:#00ff88;"></i>
                        <span>Create Post</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/coins/leaderboard.php" class="quick-action-btn">
                        <i class="fas fa-trophy" style="color:#FFD700;"></i>
                        <span>Leaderboard</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/profile/edit.php" class="quick-action-btn">
                        <i class="fas fa-user-circle" style="color:#4dabf7;"></i>
                        <span>My Profile</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/dashboard/index.php" class="quick-action-btn">
                        <i class="fas fa-gauge-high" style="color:#be4bdb;"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="section-title"><i class="fas fa-history"></i> Transaction History</div>
        <div class="card-dark">
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h5 style="color:#555;">No Transactions Yet</h5>
                    <p style="color:#444; font-size:0.9rem;">Start earning BizCoins by creating posts, getting likes, and engaging with the community!</p>
                    <a href="/posts/create.php" class="btn mt-3"
                       style="background:#FFD700; color:#000; font-weight:600; border-radius:8px; padding:10px 25px;">
                        <i class="fas fa-plus me-2"></i>Create Your First Post
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $i => $tx): ?>
                            <tr>
                                <td style="color:#555; font-size:0.85rem;"><?php echo $i + 1; ?></td>
                                <td class="tx-desc">
                                    <i class="fas fa-<?php echo $tx['type'] === 'earn' ? 'plus-circle text-success' : 'minus-circle text-danger'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($tx['description'] ?? 'Transaction'); ?>
                                </td>
                                <td>
                                    <span class="tx-type-badge <?php echo $tx['type'] === 'earn' ? 'earn' : 'spend'; ?>">
                                        <i class="fas fa-<?php echo $tx['type'] === 'earn' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                        <?php echo ucfirst($tx['type'] ?? 'earn'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="tx-amount <?php echo $tx['type'] === 'earn' ? 'positive' : 'negative'; ?>">
                                        <?php echo $tx['type'] === 'earn' ? '+' : '-'; ?><?php echo number_format($tx['amount'] ?? 0); ?>
                                        <small style="font-size:0.7rem; opacity:0.7;">BC</small>
                                    </span>
                                </td>
                                <td style="color:#888;">
                                    <?php echo isset($tx['balance_after']) ? number_format($tx['balance_after']) : '--'; ?>
                                    <small style="color:#555; font-size:0.75rem;"> BC</small>
                                </td>
                                <td class="tx-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php
                                        $date = new DateTime($tx['created_at'] ?? 'now');
                                        echo $date->format('M d, Y');
                                    ?>
                                    <br>
                                    <span style="font-size:0.78rem; color:#555;">
                                        <?php echo $date->format('h:i A'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-5" style="color:#444; font-size:0.85rem;">
            <i class="fas fa-info-circle me-1"></i>
            BizCoins are earned by posting, receiving likes/comments, and community engagement.
            <a href="/pages/about.php" style="color:#FFD700; text-decoration:none;"> Learn more</a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate balance counter
        document.addEventListener('DOMContentLoaded', () => {
            const balanceEl = document.querySelector('.balance-amount');
            const target = parseInt(balanceEl.textContent.replace(/,/g, ''));
            let current = 0;
            const duration = 1200;
            const steps = 60;
            const increment = target / steps;
            const timer = setInterval(() => {
                current = Math.min(current + increment, target);
                balanceEl.textContent = Math.floor(current).toLocaleString();
                if (current >= target) clearInterval(timer);
            }, duration / steps);
        });
    </script>
</body>
</html>
<?php
?>