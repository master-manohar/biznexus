<?php
$page_title = 'VooCoin Wallet - BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';

$uid = $_SESSION['user_id'];
$txn_stmt = $pdo->prepare('SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$txn_stmt->execute([$uid]);
$txns = $txn_stmt->fetchAll();
?>

<style>
.wallet-hero { background: linear-gradient(135deg, #13131a, #0a0a0f); border: 2px solid #FFD700; border-radius: 20px; padding: 40px; text-align: center; position: relative; overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
.wallet-hero::after { content: "🪙"; position: absolute; top: -20px; right: -20px; font-size: 8rem; opacity: 0.05; }
.wallet-balance { font-size: 4.5rem; font-weight: 900; color: #FFD700; line-height: 1; text-shadow: 0 0 30px rgba(255, 215, 0, 0.4); }
.earn-card { background: #13131a; border: 1px solid #2a2a3a; border-radius: 16px; padding: 24px; height: 100%; transition: 0.3s; }
.earn-card:hover { border-color: #444; }
.earn-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #1a1a24; }
.earn-item:last-child { border-bottom: none; }
.txn-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #1a1a24; border: 1px solid #2a2a3a; border-radius: 12px; margin-bottom: 10px; transition: 0.3s; }
.txn-item:hover { border-color: #444; background: #1d1d29; }
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">🪙 VooCoin Wallet</h1>
        <div class="page-subtitle">Earn rewards for growing the BizNexus community.</div>
    </div>
    <div class="text-end">
        <div style="font-size: 0.75rem; color: #7a7a9a; margin-bottom: 4px;">MEMBERSHIP</div>
        <div class="badge bg-warning text-dark text-uppercase px-3 rounded-pill">Premium Member</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="wallet-hero">
            <div style="font-size: 0.85rem; color: #7a7a9a; letter-spacing: 2px; margin-bottom: 10px; font-weight: 700;">CURRENT BALANCE</div>
            <div class="wallet-balance mb-2"><?= number_format($coin_balance) ?></div>
            <div style="color: #FFD700; font-size: 1rem; font-weight: 600;">VooCoins Available</div>
            <div class="mt-4">
                <a href="/referrals/list.php" class="btn btn-warning btn-sm px-4 rounded-pill fw-bold">Earn More Coins →</a>
            </div>
        </div>

        <div class="earn-card">
            <h6 style="color: #FFD700; font-weight: 800; letter-spacing: 1px; margin-bottom: 20px;">HOW TO EARN</h6>
            <?php
            $earn = [
                'Give a Referral' => 50,
                'Referral Accepted' => 100,
                'Schedule a Meeting' => 30,
                'Meeting Completed' => 50,
                'Send a New Lead' => 25,
                'Complete Business Profile' => 100,
                'Monthly Active Bonus' => 200
            ];
            foreach ($earn as $act => $pts): ?>
            <div class="earn-item">
                <span style="color: #a0a0b8; font-size: 0.88rem;"><?= $act ?></span>
                <span style="color: #00ff88; font-weight: 800;">+<?= $pts ?> 🪙</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="earn-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 style="color: #e8e8f0; font-weight: 800; letter-spacing: 1px; margin: 0;">TRANSACTION HISTORY</h6>
                <a href="/coins/history.php" style="color: #FFD700; font-size: 0.75rem; text-decoration: none;">VIEW ALL →</a>
            </div>

            <?php if (empty($txns)): ?>
                <div class="text-center py-5" style="color: #555;">
                    <i class="fas fa-history mb-3" style="font-size: 3rem; opacity: 0.2;"></i>
                    <p>No transactions yet.</p>
                </div>
            <?php else: foreach ($txns as $t): ?>
                <div class="txn-item">
                    <div>
                        <div style="color: #e0e0f0; font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($t['description'] ?? 'VooCoin Transaction') ?></div>
                        <div style="color: #555570; font-size: 0.75rem; margin-top: 2px;">
                            <i class="far fa-clock me-1"></i> <?= date('M j, Y • H:i', strtotime($t['created_at'])) ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 800; font-size: 1.1rem; color: <?= $t['amount'] >= 0 ? '#00ff88' : '#ff4444' ?>">
                            <?= $t['amount'] >= 0 ? '+' : '' ?><?= $t['amount'] ?> 🪙
                        </span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
