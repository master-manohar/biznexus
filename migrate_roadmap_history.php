<?php
require_once __DIR__ . '/includes/db.php';

try {
    $modules = [
        ['CRM "Add Contact" Portal', 'Generalized contact capture tool for all members with WhatsApp follow-up.', 'Verified mobile entry and instant welcome emails.', 'live'],
        ['H2H Meeting Lead System', 'Original urgent tool for real-time lead capture during meetings.', 'Successfully captured initial batch of leads.', 'completed'],
        ['Payment Bridge (Secure Checkout)', ' bookanevent.in bridge with Direct WhatsApp payment fallback.', 'Tested with multiple transactions and Razorpay sync.', 'live'],
        ['Admin Coupon System', 'Complete backend and UI for managing discount and referral coupons.', 'Verified logic in Razorpay modal sync.', 'live'],
        ['High-Contrast Email Engine', 'Centralized template system with strict white-on-black accessibility for all notifications.', 'Confirmed readability across Gmail, Outlook, and mobile mail clients.', 'live'],
        ['SuperAdmin Stats Dashboard', 'Real-time monitoring of members, leads, and transaction velocity.', 'Verified data accuracy and UI responsiveness.', 'live'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO roadmap_modules (name, description, testing_notes, status) VALUES (?, ?, ?, ?)");
    foreach ($modules as $m) $stmt->execute($m);

    echo "SUCCESS: Roadmap history seeded.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
