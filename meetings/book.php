<?php
// /meetings/book.php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$uid = (int)$_SESSION['user_id'];
$uname = $_SESSION['name'] ?? 'Member';

global $pdo;
if (!$pdo) {
    die("Database connection failed.");
}

// Fetch member list for the "Attendee ID" dropdown
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id != ? AND status = 'active' ORDER BY name ASC");
$stmt->execute([$uid]);
$members = $stmt->fetchAll();

// Get user coin balance
$stmtBalance = $pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id = ?");
$stmtBalance->execute([$uid]);
$userBalance = $stmtBalance->fetchColumn() ?: 0;

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendee_id = (int)$_POST['attendee_id'];
    $meeting_type = trim($_POST['meeting_type']);
    $meeting_date = trim($_POST['meeting_date']);
    $meeting_time = trim($_POST['meeting_time']);
    $agenda = trim($_POST['agenda']);
    
    // Determine cost
    $cost = 0;
    if ($meeting_type === 'online') $cost = 25;
    if ($meeting_type === 'priority') $cost = 50;

    if (empty($attendee_id) || empty($meeting_date) || empty($meeting_time)) {
        $errorMessage = "Attendee, Date, and Time are required.";
    } elseif ($userBalance < $cost) {
        $errorMessage = "Insufficient VooCoins. This meeting costs $cost coins, but you only have $userBalance.";
    } else {
        try {
            $pdo->beginTransaction();

            // Check if the attendee exists
            $stmtAtt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmtAtt->execute([$attendee_id]);
            $attendee = $stmtAtt->fetch();
            
            if (!$attendee) throw new Exception("Selected member does not exist.");

            // 1. Deduct coins and Award scheduling bonus
            if ($cost > 0) {
                // Deduct cost
                awardCoins($pdo, $uid, -$cost, "Meeting Cost ($meeting_type): " . $attendee['name']);
            }
            
            // Award scheduling bonus
            awardCoins($pdo, $uid, 30, "Meeting Scheduled Bonus: " . $attendee['name']);
            sendNotification($pdo, $uid, "Meeting Booked!", "You earned 30 VooCoins for scheduling a meeting.", 'coins');

            // 2. Insert Meeting
            $meetingLink = ($meeting_type === 'online' || $meeting_type === 'priority') ? "meet.google.com/" . substr(md5(time() . $uid), 0, 10) : "";
            
            $stmtInsert = $pdo->prepare("INSERT INTO meetings (created_by, attendee_id, meeting_type, meeting_date, meeting_time, meeting_brief, meeting_link, coin_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
            $stmtInsert->execute([$uid, $attendee_id, $meeting_type, $meeting_date, $meeting_time, $agenda, $meetingLink, $cost]);

            // 3. Notify Attendee
            sendNotification($pdo, $attendee_id, "New Meeting Request", "$uname has scheduled a $meeting_type meeting with you on $meeting_date.", 'meeting');
            
            // Send Email if config is available
            if (file_exists('../includes/email_config.php')) {
                require_once '../includes/email_config.php';
                if (function_exists('sendEmail')) {
                    $emailSubject = "📅 Upcoming Meeting: $uname has booked a time with you";
                    $emailBody = "
                        <h2>Hi {$attendee['name']},</h2>
                        <p><strong>$uname</strong> has requested a $meeting_type meeting with you on BizNexus.</p>
                        <ul style='background: #13131a; padding: 15px; border-left: 4px solid #FFD700; list-style: none;'>
                            <li><strong>Date:</strong> $meeting_date</li>
                            <li><strong>Time:</strong> $meeting_time</li>
                            <li><strong>Agenda:</strong> $agenda</li>
                            " . ($meetingLink ? "<li><strong>Link:</strong> <a href='https://$meetingLink'>Join Meeting</a></li>" : "") . "
                        </ul>
                        <p>Please log in to your dashboard to view full details.</p>
                        <a href='https://biznexus.in/meetings/list.php' style='color:#000; background:#FFD700; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold;'>View Meetings</a>
                    ";
                    $html = emailTemplate("Meeting Scheduled", $emailBody);
                    sendEmail($attendee['email'], $emailSubject, $html);
                }
            }

            $pdo->commit();
            // Redirect to meetings list after success
            header('Location: /meetings/list.php?booked=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Failed to book meeting: " . $e->getMessage();
        }
    }
}
?>
<?php
$page_title = 'Book a Meeting';
$active_page = 'meetings';
require_once __DIR__ . '/../includes/layout_start.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="font-family: 'Syne', sans-serif; font-weight: 800; margin: 0;">Book a Meeting</h2>
        <div style="background: var(--card); padding: 10px 20px; border-radius: 30px; border: 1px solid var(--border);">
            🪙 <strong style="color: var(--gold);"><?= number_format($userBalance) ?></strong> Coins Available
        </div>
    </div>
    
    <p class="mb-4" style="color: var(--text2);">Schedule a 1-2-1 network meeting with other verified members.</p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success" style="background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid #00ff88;"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger" style="background: rgba(255, 68, 68, 0.15); color: #ff4455; border: 1px solid rgba(255, 68, 68, 0.2);"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px;">
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label" style="color: var(--text2);">Select Member to Meet *</label>
                <select name="attendee_id" class="form-select" required>
                    <option value="">-- Choose a Member --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h5 class="mb-3" style="color: var(--gold);">Meeting Type</h5>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="w-100">
                        <input type="radio" name="meeting_type" value="physical" class="d-none" id="type_physical" checked onchange="updateRadioUI()">
                        <div class="type-card" id="card_physical">
                            <h6 style="color: #fff; margin-bottom: 5px;">☕ Physical Meet</h6>
                            <p style="font-size: 0.8rem; color: var(--text3); margin: 0;">In-person offline</p>
                            <span class="badge bg-success mt-2">FREE</span>
                        </div>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="w-100">
                        <input type="radio" name="meeting_type" value="online" class="d-none" id="type_online" onchange="updateRadioUI()">
                        <div class="type-card" id="card_online">
                            <h6 style="color: #fff; margin-bottom: 5px;">💻 Online Meet</h6>
                            <p style="font-size: 0.8rem; color: var(--text3); margin: 0;">Auto Google Meet link</p>
                            <span class="badge" style="background: #e6a800; margin-top: 8px; color:#000;">25 Coins</span>
                        </div>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="w-100">
                        <input type="radio" name="meeting_type" value="priority" class="d-none" id="type_priority" onchange="updateRadioUI()">
                        <div class="type-card" id="card_priority">
                            <h6 style="color: #fff; margin-bottom: 5px;">⚡ Priority Meet</h6>
                            <p style="font-size: 0.8rem; color: var(--text3); margin: 0;">Guaranteed response</p>
                            <span class="badge" style="background: #e6a800; margin-top: 8px; color:#000;">50 Coins</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Date *</label>
                    <input type="date" name="meeting_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="color: var(--text2);">Time *</label>
                    <input type="time" name="meeting_time" class="form-control" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: var(--text2);">Agenda / Notes</label>
                <textarea name="agenda" class="form-control" rows="3" placeholder="What would you like to discuss?"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">Book Meeting</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
