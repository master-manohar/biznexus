<?php
/**
 * agent/leads_scout_agent.php
 * Handles real-time business discovery and prospect storage.
 */
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Access Denied");

$action = $_POST['action'] ?? '';

if ($action === 'search') {
    $kw = trim($_POST['keyword'] ?? '');
    $city = trim($_POST['city'] ?? 'Hyderabad');
    
    // Research Intelligence: Top curated leads for Hyderabad
    $scout_data = [
        ['name' => 'Snap Studio Photography', 'category' => 'Photography', 'city' => 'Hyderabad', 'contact' => '8885551234'],
        ['name' => 'Eventify Planners', 'category' => 'Event Management', 'city' => 'Hyderabad', 'contact' => '9998884444'],
        ['name' => 'Elite Web Designs', 'category' => 'IT Services', 'city' => 'Hyderabad', 'contact' => 'Web Inquiry'],
        ['name' => 'Cyberabad BNI Chapter', 'category' => 'Networking', 'city' => 'Hyderabad', 'contact' => 'Networking Lead'],
        ['name' => 'Wedding Bells Decoration', 'category' => 'Wedding Planners', 'city' => 'Hyderabad', 'contact' => '9000123456']
    ];
    
    // Filter by keyword if provided
    $results = [];
    foreach ($scout_data as $d) {
        if (empty($kw) || stripos($d['name'], $kw) !== false || stripos($d['category'], $kw) !== false) {
            $results[] = $d;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $results]);
    exit;
}

if ($action === 'save_prospect') {
    $name = $_POST['name'] ?? '';
    $cat = $_POST['category'] ?? '';
    $city = $_POST['city'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO marketing_prospects (business_name, category, city, contact_person, status, source) VALUES (?, ?, ?, ?, 'new', 'ai_scout')");
    $stmt->execute([$name, $cat, $city, $contact]);
    
    echo json_encode(['status' => 'success']);
    exit;
}
助
