<?php
// agent/categories_setup.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';

echo "STARTING CATEGORY MIGRATION...\n";

try {
    // 1. Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        icon VARCHAR(50) DEFAULT 'Circle',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "TABLE 'categories' READY.\n";

    $cats = [
        // IT & Technology
        'Web Development', 'Mobile App Development', 'Cloud Computing Services', 'Cybersecurity Solutions', 'Data Analytics', 
        'Artificial Intelligence (AI)', 'Blockchain Technology', 'IT Consulting', 'UI/UX Design', 'E-commerce Solutions', 
        'ERP Implementation', 'CRM Development', 'Managed IT Services', 'Software Testing', 'Game Development',
        // Digital & Media
        'SEO Services', 'Social Media Marketing', 'Content Writing', 'Video Production', 'Graphic Design', 
        'Brand Identity & Logo', 'PPC Advertising', 'Public Relations (PR)', 'Influencer Marketing', 'Digital Publishing',
        // Healthcare & Pharma
        'Pharmaceutical Manufacturing', 'Biotechnology', 'Diagnostic Centers', 'General Hospitals', 'Dental Clinics', 
        'Medical Equipment Supply', 'Health Insurance Services', 'Ayurvedic & Herbal Products', 'Telemedicine', 'Veterinary Services',
        // Real Estate & Construction
        'Residential Development', 'Commercial Real Estate', 'Industrial Properties', 'Interior Design', 'Home Automation', 
        'Property Management', 'Real Estate Consulting', 'Civil Construction', 'Structural Engineering', 'Architecture Services', 
        'Electrical & Plumbing', 'Cement & Building Materials',
        // Agriculture & Food
        'Agri-tech Solutions', 'Food Processing', 'Dairy Products', 'Cold Storage Logistics', 'Organic Farming', 
        'Fertilizers & Pesticides', 'Irrigation Systems', 'Poultry & Livestock', 'Seed Production', 'Packaged Foods', 
        'Beverages', 'Spice Processing',
        // Manufacturing & Industrial
        'Textile Manufacturing', 'Auto Components', 'Chemicals & Industrial Gases', 'Steel & Metal Fabrication', 'Plastic & Packaging', 
        'Electronics Manufacturing', 'Furniture Manufacturing', 'Gem & Jewelry Manufacturing', 'Printing & Stationery', 'Solar Panel Manufacturing', 
        'Recycling & Waste Management', 'Electrical Appliances', 'Leather Products', 'Rubber & Tyre Industries', 'Paper & Pulp',
        // Professional Services
        'Chartered Accountants (CA)', 'Legal & Corporate Law', 'Business Strategy Consulting', 'ISO Certification Services', 'Company Secretary (CS)', 
        'Intellectual Property (IPR)', 'Human Resources (HR) & Recruitment', 'Corporate Training', 'Private Security Services', 'Translation Services',
        // Education
        'K-12 Tutoring', 'Competitive Exam Coaching', 'Professional Skill Training', 'Language Schools', 'E-learning Platforms', 
        'Study Abroad Consulting', 'Corporate Workshops', 'Higher Education Services',
        // Logistics
        'Warehousing Solutions', 'Courier & Parcel Services', 'Freight Forwarding', 'Supply Chain Management', 'Last-Mile Delivery', 
        'Fleet Management', 'Customs Clearance', 'E-commerce Logistics',
        // Hospitality & Retail
        'Event Management', 'Catering Services', 'Hotels & Resorts', 'Travel & Tourism', 'Apparel Wholesale', 'Retail Tech'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
    $count = 0;
    foreach ($cats as $name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $stmt->execute([$name, $slug]);
        $count++;
    }

    echo "SUCCESS: $count Categories processed.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
