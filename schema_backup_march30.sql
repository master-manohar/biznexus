-- BizNexus End-to-End Fix Backup (March 30, 2026)
-- This file documents the manual database schema changes for restoration purposes.

-- 1. Addition of referral_source to users table
-- This was missing and caused onboarding/admin crashes.
ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_source VARCHAR(50) DEFAULT NULL AFTER refer_code;

-- 2. Referral logic table audit
-- Ensure these supporting tables exist for the rewards system.
CREATE TABLE IF NOT EXISTS coin_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    description TEXT,
    created_at DATETIME
);

-- 3. Agent Task System
-- Ensure mappings for 'general' and 'outreach_marketing' are respected.
-- (Note: These are code-level mappings in agent/runner.php)

-- 4. Sample Verification Query
-- SELECT id, business_name, email, referral_source FROM users ORDER BY id DESC LIMIT 5;
