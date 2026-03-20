<?php
// /agent/master_fix2.php
// Generates 400 realistic demo member accounts across various categories
require_once '../includes/db.php';
require_once '../includes/functions.php';

// $pdo is provided globally by db.php
global $pdo;

if (!$pdo) die("DB Connection Failed");

$categories = [
    'Real Estate', 'Construction', 'Food and Beverage', 'Healthcare',
    'Education', 'Retail', 'IT Services', 'Finance', 'Legal',
    'Event Management', 'Photography', 'Fashion', 'Manufacturing', 'Other'
];

$cities = [
    'Hyderabad', 'Secunderabad', 'Madhapur', 'Banjara Hills',
    'Jubilee Hills', 'Gachibowli', 'Kukatpally', 'Ameerpet',
    'Begumpet', 'Kondapur'
];

$firstNames = [
    'Rahul', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anjali', 'Ramesh', 'Sita',
    'Kiran', 'Pooja', 'Suresh', 'Kavita', 'Ravi', 'Deepa', 'Mahesh', 'Divya',
    'Ajay', 'Neha', 'Sanjay', 'Swati', 'Vijay', 'Meena', 'Prakash', 'Sunita',
    'Arjun', 'Asha', 'Rohit', 'Geeta', 'Anil', 'Rekha'
];

$lastNames = [
    'Reddy', 'Rao', 'Sharma', 'Patel', 'Kumar', 'Singh', 'Gupta', 'Verma',
    'Nair', 'Menon', 'Iyer', 'Pillai', 'Joshi', 'Desai', 'Choudhary', 'Shah',
    'Tiwari', 'Yadav', 'Mishra', 'Pandey'
];

$businessTypes = [
    'Real Estate' => ['Properties', 'Realty', 'Estates', 'Builders', 'Developers'],
    'Construction' => ['Constructions', 'Infrastructure', 'Contractors', 'Builders'],
    'Food and Beverage' => ['Catering', 'Foods', 'Cafe', 'Restaurant', 'Bakers'],
    'Healthcare' => ['Clinic', 'Pharma', 'Healthcare', 'Hospitals', 'Diagnostics'],
    'Education' => ['Academy', 'Institute', 'Tutorials', 'Education', 'School'],
    'Retail' => ['Mart', 'Stores', 'Bazaar', 'Retailers', 'Supermarket'],
    'IT Services' => ['Technologies', 'Software', 'Solutions', 'Systems', 'IT'],
    'Finance' => ['Finserve', 'Investments', 'Capital', 'Finance', 'Wealth'],
    'Legal' => ['Associates', 'Law', 'Legal', 'Advocates', 'Chambers'],
    'Event Management' => ['Events', 'Planners', 'Weddings', 'Celebrations', 'Decorators'],
    'Photography' => ['Studios', 'Photography', 'Captures', 'Lens', 'Visuals'],
    'Fashion' => ['Boutique', 'Apparels', 'Wear', 'Fashion', 'Designs'],
    'Manufacturing' => ['Industries', 'Manufacturing', 'Works', 'Makers', 'Enterprises'],
    'Other' => ['Ventures', 'Consulting', 'Services', 'Traders', 'Corporation']
];

$defaultPassword = password_hash('Demo@2024', PASSWORD_BCRYPT);
$defaultPhone = '7569755529';
$totalToGenerate = 400;

try {
    $pdo->beginTransaction();

    // Optionally clear previous demo accounts? Uncomment if you want fresh each time
    // $pdo->exec("DELETE FROM users WHERE email LIKE 'demo_%@biznexus.in'");
    // $pdo->exec("DELETE FROM business_profiles WHERE whatsapp = '7569755529'");

    $count = 0;
    while ($count < $totalToGenerate) {
        $category = $categories[array_rand($categories)];
        $city = $cities[array_rand($cities)];
        $fName = $firstNames[array_rand($firstNames)];
        $lName = $lastNames[array_rand($lastNames)];
        $name = "$fName $lName";
        $email = "demo_" . strtolower($fName . rand(1000, 9999)) . "@biznexus.in";
        
        $bizSuffix = $businessTypes[$category][array_rand($businessTypes[$category])];
        $bizName = "$lName $bizSuffix";

        // Insert User
        $stmtUser = $pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password, role, status, plan, created_at) VALUES (?, ?, ?, ?, 'member', 'active', 'free', NOW())");
        $stmtUser->execute([$name, $email, $defaultPhone, $defaultPassword]);
        $userId = $pdo->lastInsertId();

        if ($userId) {
            // Give exactly 100 Voocoins (100 earned, 0 spent)
            $stmtCoins = $pdo->prepare("INSERT IGNORE INTO voocoin_balances (user_id, balance, total_earned, total_spent) VALUES (?, 100, 100, 0)");
            $stmtCoins->execute([$userId]);

            // Create Business Profile
            $stmtProfile = $pdo->prepare("
                INSERT IGNORE INTO business_profiles 
                (user_id, business_name, category, city, whatsapp, description, is_verified, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $desc = "Leading provider of $category services in $city. Contact us for the best B2B rates and quality assurance.";
            $stmtProfile->execute([$userId, $bizName, $category, $city, $defaultPhone, $desc]);
            $count++;
        }
    }

    $pdo->commit();
    echo "<h1>✅ Successfully generated $totalToGenerate demo accounts!</h1>";
    echo "<p>Categories distributed evenly. Voocoins allocated: 100 each.</p>";
    echo "<p>Test Lead Login: use any email generated (e.g., demo_name@biznexus.in) and password: Demo@2024</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h1>❌ Error Generation Failed</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
