<?php
require_once dirname(__DIR__) . '/includes/db.php';

$city_coords = [
    'Hyderabad' => [17.3850, 78.4867],
    'Mumbai'    => [19.0760, 72.8777],
    'Delhi'     => [28.6139, 77.2090],
    'Bangalore' => [12.9716, 77.5946],
    'Pune'      => [18.5204, 73.8567],
    'Chennai'   => [13.0827, 80.2707],
    'Kolkata'   => [22.5726, 88.3639],
    'Ahmedabad' => [23.0225, 72.5714],
    'Surat'     => [21.1702, 72.8311],
    'Visakhapatnam' => [17.6868, 83.2185]
];

echo "Geocoding Baseline Data...\n";

// Update Users
$users = $pdo->query("SELECT id, city FROM users WHERE city IS NOT NULL AND city != ''")->fetchAll();
foreach ($users as $u) {
    foreach ($city_coords as $name => $coords) {
        if (stripos($u['city'], $name) !== false) {
            $pdo->prepare("UPDATE users SET lat = ?, lng = ? WHERE id = ?")->execute([$coords[0], $coords[1], $u['id']]);
            echo "Updated User #{$u['id']} ({$u['city']}) -> {$name}\n";
            continue 2;
        }
    }
}

// Update Leads
$leads = $pdo->query("SELECT id, city FROM public_leads WHERE city IS NOT NULL AND city != ''")->fetchAll();
foreach ($leads as $l) {
    foreach ($city_coords as $name => $coords) {
        if (stripos($l['city'], $name) !== false) {
            $pdo->prepare("UPDATE public_leads SET lat = ?, lng = ? WHERE id = ?")->execute([$coords[0], $coords[1], $l['id']]);
            echo "Updated Lead #{$l['id']} ({$l['city']}) -> {$name}\n";
            continue 2;
        }
    }
}

echo "Geocoding Complete.\n";
