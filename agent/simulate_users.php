<?php
// /agent/simulate_users.php
require_once dirname(__DIR__) . '/includes/db.php';

$categories = [
    'Technology', 'Marketing', 'Accounting', 'Legal Services', 'Consulting',
    'Real Estate', 'Logistics', 'Event Management', 'Catering', 'Interior Design',
    'Healthcare Services', 'Fitness & Wellness', 'Education', 'Construction'
];

$cities = [
    'Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata',
    'Surat', 'Pune', 'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Indore', 'Thane',
    'Bhopal', 'Visakhapatnam', 'Pimpri-Chinchwad', 'Patna', 'Vadodara', 'Ghaziabad',
    'Ludhiana', 'Agra', 'Nashik', 'Faridabad', 'Meerut', 'Rajkot', 'Kalyan-Dombivli',
    'Vasai-Virar', 'Varanasi', 'Srinagar', 'Aurangabad', 'Dhanbad', 'Amritsar',
    'Navi Mumbai', 'Allahabad', 'Howrah', 'Ranchi', 'Gwalior', 'Jabalpur', 'Coimbatore',
    'Vijayawada', 'Jodhpur', 'Madurai', 'Raipur', 'Kota', 'Chandigarh', 'Guwahati', 'Solapur'
];

$plans = ['free', 'silver', 'gold', 'platinum'];

$count = 0;
for($i = 1; $i <= 100; $i++) {
    $cat = $categories[array_rand($categories)];
    $city = $cities[array_rand($cities)];
    $plan = $plans[array_rand($plans)];
    
    // Weighted verification
    $is_verified = (rand(1, 100) > 40) ? 1 : 0; 
    
    // Generate realistic names
    $name = "Simulated User " . $i;
    $business_name = "Simulated " . $cat . " " . rand(100, 999) . " " . $city;
    $email = "sim" . $i . "@example.com";
    
    $password = password_hash('Test@123', PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("INSERT INTO users (name, business_name, email, phone, password, role, status, plan, is_verified, trust_score, created_at) VALUES (?, ?, ?, ?, ?, 'user', 'active', ?, ?, ?, NOW())")
            ->execute([$name, $business_name, $email, '9999999999', $password, $plan, $is_verified, rand(20, 95)]);
            
        $uid = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO business_profiles (user_id, business_name, category, city, description, whatsapp) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$uid, $business_name, $cat, $city, "We are a top-rated " . strtolower($cat) . " agency proudly serving " . $city . ".", '9999999999']);
            
        $pdo->commit();
        $count++;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error on loop $i: " . $e->getMessage() . "\n";
    }
}

echo "Simulation Engine complete: Successfully injected $count realistic business profiles into the ecosystem to seed the SEO dynamic paths.";
?>
