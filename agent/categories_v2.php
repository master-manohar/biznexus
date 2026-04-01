<?php
// agent/categories_v2.php
require_once __DIR__ . '/../includes/db.php';

$cats = [
    // IT & Software (Expanded)
    'Web Development', 'Mobile App Development', 'Cloud Computing', 'Cybersecurity', 'Data Analytics', 
    'AI & Machine Learning', 'Blockchain Development', 'IT Consulting', 'UI/UX Design', 'E-commerce Solutions', 
    'ERP Systems', 'CRM Software', 'Managed IT Services', 'Software Testing Services', 'Game Development',
    'SaaS Development', 'DevOps Consulting', 'Network Infrastructure', 'Database Management', 'API Integration',
    
    // Digital Marketing & Media (Expanded)
    'SEO Services', 'Social Media Marketing', 'Content Writing', 'Video Production', 'Graphic Design', 
    'Brand Identity', 'PPC Management', 'Public Relations', 'Influencer Marketing', 'Email Marketing',
    '3D Animation', 'Podcast Production', 'Virtual Reality Services', 'Market Research', 'Copywriting',
    
    // Healthcare & Medical (Expanded)
    'Pharmaceuticals', 'Biotechnology', 'Diagnostic Centers', 'General Hospitals', 'Dental Clinics', 
    'Medical Equipment', 'Health Insurance', 'Ayurvedic Medicine', 'Telemedicine', 'Veterinary Clinics',
    'Physiotherapy', 'Mental Health Counseling', 'Ophthalmology', 'Pathology Labs', 'Pharmacy Retail',
    
    // Real Estate & Construction (Expanded)
    'Residential Real Estate', 'Commercial Property', 'Industrial Real Estate', 'Interior Design', 'Home Automation', 
    'Property Management', 'Construction Consulting', 'Civil Engineering', 'Structural Design', 'Architecture', 
    'Electrical Contracting', 'Plumbing Services', 'Building Materials Silk', 'Ready Mix Concrete', 'Solar Panel Installation',
    'Flooring & Tiling', 'Painting Services', 'Waterproofing Solutions', 'Kitchen Remodeling', 'Landscape Architecture',
    
    // Manufacturing & Industrial (Expanded)
    'Textile Manufacturing', 'Automotive Parts', 'Industrial Chemicals', 'Metal Fabrication', 'Plastic Packaging', 
    'Electronics Assembly', 'Furniture Manufacturing', 'Jewelry Manufacturing', 'Printing Services', 'Solar Manufacturing', 
    'Waste Management', 'Electric Appliances', 'Leather Goods', 'Rubber Products', 'Paper & Pulp',
    'Machinery Parts', 'Hydraulic Systems', 'CNC Machining', 'Injection Molding', 'Glass Manufacturing',
    'Steel Pre-fabrication', 'Foundry Services', 'Pump & Valve Manufacturing', 'Electronic Components', 'Tool & Die Making',
    
    // Professional Services (Expanded)
    'Chartered Accountants', 'Corporate Law', 'Business Consulting', 'ISO Certification', 'Company Registration', 
    'Intellectual Property', 'Recruitment Agency', 'Corporate Training', 'Security Services', 'Translation Services',
    'Financial Auditing', 'GST Consultants', 'Payroll Outsourcing', 'Direct Marketing', 'Risk Management',
    'Legal Documentation', 'Private Investigation', 'Detective Agency', 'Asset Management', 'Debt Collection',
    
    // Education & Training (Expanded)
    'K-12 Tutoring', 'Exam Coaching', 'Skill Training', 'Language Classes', 'Learning Management', 
    'Study Abroad', 'Corporate Workshops', 'Higher Education', 'Vocational Training', 'Music Schools',
    'Dance Academies', 'Yoga Certification', 'Coding Bootcamps', 'Montessori Schools', 'Nursing Education',
    
    // Logistics & Transport (Expanded)
    'Warehousing', 'Courier Services', 'Freight Forwarding', 'Supply Chain', 'Last-Mile Delivery', 
    'Fleet Management', 'Customs Brokerage', 'E-commerce Shipping', 'Cold Chain Logistics', 'Trucking Services',
    'Relocation Services', 'Packers and Movers', 'Vehicle Transport', 'Air Freight', 'Sea Cargo',
    
    // Hospitality, Retail & Lifestyle (Expanded)
    'Event Planning', 'Catering', 'Hotels & Resorts', 'Travel Agency', 'Apparel Wholesale', 'Retail Software',
    'Wedding Planning', 'Gym & Fitness Centers', 'Beauty Parlors', 'Spa & Wellness', 'Dry Cleaning',
    'Pet Grooming', 'Florist Services', 'Photography Studio', 'Bakery Wholesale', 'Furniture Retail',
    'Home Decor', 'Gadget Repair', 'Mobile Retail', 'Organic Food Stores', 'Supermarket Supplies',
    
    // Specialized Niches
    'Solar Energy Solutions', 'Electric Vehicle Charging', 'Drip Irrigation', 'Greenhouse Construction',
    'Aquaponics Systems', 'Hydroponics Supplies', 'Aviation Consultancy', 'Maritime Services',
    'Fire Safety Equipment', 'Elevator Maintenance', 'Air Conditioning Repair', 'Generator Rental',
    'STP Plant Maintenance', 'Borewell Drilling', 'Pest Control Services'
];

echo "Total Categories Planned: " . count($cats) . "\n";

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    $added = 0;
    foreach ($cats as $name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $stmt->execute([$name, $slug]);
        if ($stmt->rowCount() > 0) $added++;
    }
    echo "SUCCESS: $added New Categories Added to BizNexus.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
