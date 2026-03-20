<?php
// One-time script: Create 100 catchy BizNexus groups
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

$tiers = ['Nexus','Omkara','Diamond','Charminar','Tajmahal','Gold'];

$prefixes = [
    'Diamond','Infinity','Phoenix','Nexus','Pinnacle','Legacy','Empire','Sovereign','Apex','Titan',
    'Zenith','Luminary','Prestige','Vanguard','Ascent','Dynasty','Velocity','Radiance','Summit','Triumph',
    'Orbit','Spectrum','Momentum','Catalyst','Vision','Synergy','Horizon','Quantum','Stellar','Elevate',
    'Fusion','Magnify','Inspire','Ascend','Unity','Valor','Nova','Prime','Exceed','Impact',
    'Endeavour','Amplify','Evolve','Pioneer','Empower','Strive','Excel','Fortitude','Brilliance','Achieve'
];

$suffixes = [
    'Nexus','Circle','League','Hub','Alliance','Network','Connect','Forge','Guild','Collective',
    'Chamber','Council','Brigade','Union','Syndicate','Chapter','Forum','Clan','Society','Association',
    'Exchange','Club','Group','Community','Foundation','Institute','Assembly','Coalition','Consortium','Federation'
];

$cities = [
    'Hyderabad','Mumbai','Delhi','Bangalore','Chennai','Kolkata','Pune','Ahmedabad','Jaipur','Surat',
    'Vizag','Kochi','Coimbatore','Indore','Bhopal','Nagpur','Lucknow','Chandigarh','Vadodara','Nashik'
];

$created = 0;
$skipped = 0;
$groups_made = [];

// First batch — prefix + suffix style (like Diamond Nexus, Infinity Circle)
foreach ($prefixes as $pre) {
    foreach ($suffixes as $suf) {
        if ($created >= 100) break 2;
        $name = $pre . ' ' . $suf;
        if (in_array($name, $groups_made)) { $skipped++; continue; }
        $tier = $tiers[array_rand($tiers)];
        $cap  = [50,75,100,100,150,200][rand(0,5)];
        try {
            $pdo->prepare("INSERT INTO groups (name, tier, max_members, is_active, is_active_group, created_by, created_at)
                           VALUES (?, ?, ?, 1, 1, 1, NOW())")
                ->execute([$name, $tier, $cap]);
            $groups_made[] = $name;
            $created++;
        } catch(Exception $e) {
            $skipped++; // skip duplicates
        }
    }
}

// Fill remaining with city-based names if needed
if ($created < 100) {
    foreach ($cities as $city) {
        foreach (['Nexus','Circle','Hub','Alliance','League'] as $suf) {
            if ($created >= 100) break 2;
            $name = $city . ' ' . $suf;
            if (in_array($name, $groups_made)) continue;
            $tier = $tiers[array_rand($tiers)];
            try {
                $pdo->prepare("INSERT INTO groups (name, tier, max_members, is_active, is_active_group, created_by, created_at)
                               VALUES (?, ?, ?, 1, 1, 1, NOW())")
                    ->execute([$name, $tier, 100]);
                $groups_made[] = $name;
                $created++;
            } catch(Exception $e) { $skipped++; }
        }
    }
}

$total = $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn();
echo "✅ Created: $created groups\n";
echo "⚠️  Skipped: $skipped (duplicates)\n";
echo "📊 Total groups in DB: $total\n\n";

echo "Sample names created:\n";
foreach (array_slice($groups_made, 0, 20) as $n) {
    echo "  • $n\n";
}
?>
