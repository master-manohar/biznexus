<?php
require_once '../includes/layout_start.php';
global $pdo;
$uid = (int)$_SESSION['user_id'];

// Handle Cancellation
if (isset($_GET['cancel'])) {
    $cancel_id = (int)$_GET['cancel'];
    // Verify ownership
    $stmt = $pdo->prepare("UPDATE meetings SET status='cancelled' WHERE id=? AND (created_by=? OR attendee_id=?)");
    $stmt->execute([$cancel_id, $uid, $uid]);
    header("Location: list.php?msg=cancelled");
    exit;
}

// Handle Completion
if (isset($_GET['complete'])) {
    $mid = (int)$_GET['complete'];
    // Verify participation
    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id=? AND (created_by=? OR attendee_id=?) AND status != 'completed'");
    $stmt->execute([$mid, $uid, $uid]);
    $meeting = $stmt->fetch();
    
    if ($meeting) {
        $pdo->prepare("UPDATE meetings SET status='completed' WHERE id=?")->execute([$mid]);
        
        // Reward: Meeting Completed (+50 🪙 to BOTH)
        awardCoins($pdo, $meeting['created_by'], 50, "B2B Meeting Completed: ID $mid");
        awardCoins($pdo, $meeting['attendee_id'], 50, "B2B Meeting Completed: ID $mid");
        
        sendNotification($pdo, $meeting['created_by'], "Meeting Completed!", "You earned 50 VooCoins for completing your meeting.", 'coins');
        sendNotification($pdo, $meeting['attendee_id'], "Meeting Completed!", "You earned 50 VooCoins for completing your meeting.", 'coins');
    }
    header("Location: list.php?msg=completed");
    exit;
}

// Fetch user's meetings
$stmt = $pdo->prepare("
    SELECT m.*, 
           u_host.name as host_name, u_host.email as host_email,
           u_guest.name as guest_name, u_guest.email as guest_email,
           bp_host.business_name as host_business,
           bp_guest.business_name as guest_business
    FROM meetings m
    JOIN users u_host ON m.created_by = u_host.id
    LEFT JOIN users u_guest ON m.attendee_id = u_guest.id
    LEFT JOIN business_profiles bp_host ON u_host.id = bp_host.user_id
    LEFT JOIN business_profiles bp_guest ON u_guest.id = bp_guest.user_id
    WHERE m.created_by = ? OR m.attendee_id = ?
    ORDER BY m.meeting_date DESC, m.meeting_time DESC
");
$stmt->execute([$uid, $uid]);
$meetings = $stmt->fetchAll();
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Meetings 📅</h2>
            <p class="text-muted small">Manage your scheduled B2B connections</p>
        </div>
        <a href="book.php" class="btn btn-gold btn-sm px-4">Book New Meeting +</a>
    </div>

    <div class="row">
        <?php if(empty($meetings)): ?>
            <div class="col-12">
                <div class="card bg-dark border-secondary p-5 text-center">
                    <div class="display-1 mb-3">📅</div>
                    <h3>No Meetings Found</h3>
                    <p class="text-muted">You haven't booked or received any meeting requests yet.</p>
                    <a href="book.php" class="btn btn-outline-gold mt-3">Book Your First Meeting</a>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="card bg-dark border-secondary overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="bg-black">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small text-uppercase">Meeting Details</th>
                                    <th class="py-3 text-muted small text-uppercase">With Whom</th>
                                    <th class="py-3 text-muted small text-uppercase">Date & Time</th>
                                    <th class="py-3 text-muted small text-uppercase">Status</th>
                                    <th class="pe-4 py-3 text-muted small text-uppercase text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($meetings as $m): 
                                    $is_host = ($m['created_by'] == $uid);
                                    $other_name = $is_host ? $m['guest_name'] : $m['host_name'];
                                    $other_biz = $is_host ? ($m['guest_business'] ?: 'Freelancer/Indiv.') : ($m['host_business'] ?: 'Freelancer/Indiv.');
                                    $status_class = match($m['status']){
                                        'scheduled' => 'text-primary',
                                        'active' => 'text-info',
                                        'completed' => 'text-success',
                                        'cancelled' => 'text-danger',
                                        default => 'text-warning'
                                    };
                                ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="fw-bold"><?= htmlspecialchars($m['title'] ?: 'B2B Meeting') ?></div>
                                        <?php if($m['meeting_type'] === 'zoom' && $m['zoom_link']): ?>
                                            <span class="badge bg-primary-subtle text-primary small">Zoom Call 📹</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= ucwords($m['meeting_type'] ?: 'Physical') ?> 📍</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($other_name ?: 'Unknown Member') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($other_biz) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= date('d M Y', strtotime($m['meeting_date'])) ?></div>
                                        <div class="text-muted small"><?= date('h:i A', strtotime($m['meeting_time'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= $status_class ?>"><?= strtoupper($m['status']) ?></span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <?php if($m['status'] === 'scheduled'): ?>
                                            <button class="btn btn-outline-success btn-sm" onclick="completeMeeting(<?= $m['id'] ?>)">Mark Done</button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="cancelMeeting(<?= $m['id'] ?>)">Cancel</button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-gold btn-sm ms-1">Details</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelMeeting(id) {
    if(confirm('Are you sure you want to cancel this meeting?')) {
        window.location.href = 'list.php?cancel=' + id;
    }
}
function completeMeeting(id) {
    if(confirm('Mark this meeting as completed? Both parties will earn 50 VooCoins!')) {
        window.location.href = 'list.php?complete=' + id;
    }
}
</script>

<?php require_once '../includes/layout_end.php'; ?>
