<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS roadmap_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        description TEXT,
        testing_notes TEXT,
        status ENUM('planned','wip','testing','completed','live') DEFAULT 'planned',
        run_request BOOLEAN DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Seed initial Roadmap for BizNexus 2.0
    $modules = [
        ['BizFeed (B2B Social Social Platform)', 'Instagram/LinkedIn style feed for business achievements and lead wins.', 'Check post creation, comments, and image uploading.'],
        ['BizCard (Digital Visiting Card)', 'QR-based professional profile with integrated lead capture.', 'Verify QR scanning and automatic CRM entry.'],
        ['Enterprise SMTP (Gold/Plat)', 'Personal domain emailing for premium users.', 'Test custom SMTP handshake and deliverability.'],
        ['WhatsApp Cloud API Hub', 'Centralized inbox for Meta messaging within BizNexus CRM.', 'Verify two-way messaging and webhook stability.'],
        ['AI Web Scout Agents', 'Tier-based website re-builder agents (Silver, Gold, Platinum).', 'Check design quality and Tier-based constraint enforcement.'],
        ['Security & Scaling (5k+ Users)', 'Redis caching and security hardening for high traffic.', 'Perform load testing and rate-limit verification.'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO roadmap_modules (name, description, testing_notes) VALUES (?, ?, ?)");
    foreach ($modules as $m) $stmt->execute($m);

    echo "SUCCESS: roadmap_modules table created and seeded.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
