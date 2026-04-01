<?php
// Returns a beautiful security score badge for the dashboard.
if(!isset($pdo) || !isset($uid)) return;

$score = calculateTrustScore($pdo, $uid);
$level = getTrustLevel($score);
?>
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #13131a, #0a0a0f); border-left: 4px solid <?= $level['color'] ?> !important; border-radius:15px;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 1px; color: #888;">Account Security Rating</h6>
                <div class="d-flex align-items-center">
                    <span style="font-size: 1.5rem; margin-right: 10px;"><?= $level['badge'] ?></span>
                    <h5 class="mb-0" style="color: <?= $level['color'] ?>; font-weight: 800;"><?= $level['label'] ?></h5>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size: 2rem; font-weight: 900; color: #fff; line-height: 1;"><?= $score ?></div>
                <div style="font-size: 0.7rem; color: #555; text-transform: uppercase;">Points / 1000</div>
            </div>
        </div>
        
        <div class="progress bg-dark" style="height: 8px; border-radius: 10px;">
            <div class="progress-bar" role="progressbar" style="width: <?= ($score/10) ?>%; background: <?= $level['color'] ?>; box-shadow: 0 0 10px <?= $level['color'] ?>;" aria-valuenow="<?= $score ?>" aria-valuemin="0" aria-valuemax="1000"></div>
        </div>
        
        <div class="d-flex justify-content-between mt-3">
            <span class="small" style="color: #666;"><i class="fas fa-shield-alt text-success me-1"></i> Data Security: <strong><?= $level['status'] ?></strong></span>
            <?php if ($score < 800): ?>
                <a href="/profile/edit.php" class="small text-warning text-decoration-none">Increase Score →</a>
            <?php else: ?>
                <span class="small text-success"><i class="fas fa-check-circle me-1"></i> Fully Protected</span>
            <?php endif; ?>
        </div>
    </div>
</div>
