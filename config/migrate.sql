-- ====================================================================
-- Migration: Add landlord registration fields to users table
-- Run this on your existing database to add new columns WITHOUT
-- losing any existing data.
-- ====================================================================

ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) DEFAULT NULL AFTER `password`;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL AFTER `full_name`;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL AFTER `phone`;
ALTER TABLE users ADD COLUMN IF NOT EXISTS nida VARCHAR(50) DEFAULT NULL AFTER `email`;
ALTER TABLE users ADD COLUMN IF NOT EXISTS approved TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`;
ALTER TABLE users ADD COLUMN IF NOT EXISTS property_address TEXT DEFAULT NULL AFTER `approved`;

ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER `tenant_id`;

ALTER TABLE tenants ADD COLUMN IF NOT EXISTS nida VARCHAR(50) DEFAULT NULL AFTER `phone`;
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS landlord_id VARCHAR(20) DEFAULT NULL AFTER `nida`;

ALTER TABLE properties ADD COLUMN IF NOT EXISTS landlord_id VARCHAR(20) DEFAULT NULL AFTER `address`;

-- Link existing seed data to the sample landlord (u3)
UPDATE properties SET landlord_id = 'u3' WHERE landlord_id IS NULL;
UPDATE tenants    SET landlord_id = 'u3' WHERE landlord_id IS NULL;

-- Seed users should NOT be forced to change password (use their default 'password')
UPDATE users SET must_change_password = 0 WHERE username IN ('admin', 'johndoe', 'landlord');

-- Set default values for existing users so they don't break
UPDATE users SET full_name = username, full_name = username WHERE full_name IS NULL;
UPDATE users SET approved = 1 WHERE approved IS NULL;
