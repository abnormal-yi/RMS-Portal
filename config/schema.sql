-- ====================================================================
-- Online Rental Management System - Database Schema
-- Run this on your hosting database (phpMyAdmin or CLI) to create all
-- required tables and seed initial data (admin user + sample records).
-- ====================================================================

-- Users table: stores login credentials and role assignments.
-- Every user is either 'admin' or 'tenant'; tenants may also have a
-- linked tenant_id pointing to the tenants table.
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(20) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    nida VARCHAR(50) DEFAULT NULL,
    role ENUM('admin','tenant','landlord') NOT NULL DEFAULT 'tenant',
    approved TINYINT(1) NOT NULL DEFAULT 1,
    property_address TEXT DEFAULT NULL,
    tenant_id VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Properties table: real-estate units managed by the system.
-- Tracks the monthly rent amount and current occupancy status.
CREATE TABLE IF NOT EXISTS properties (
    id VARCHAR(20) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    rent_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenants table: personal details of each renter in the system.
-- Tenants are linked to users via the foreign-key-like tenant_id field.
CREATE TABLE IF NOT EXISTS tenants (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contracts table: rental agreements linking a tenant to a property.
-- Start/end dates define the lease period; status shows if it is active
-- or has been terminated.
CREATE TABLE IF NOT EXISTS contracts (
    id VARCHAR(20) PRIMARY KEY,
    property_id VARCHAR(20) NOT NULL,
    tenant_id VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','terminated') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table: rent payments recorded against contracts.
-- Includes the amount, payment method, and a unique control number for
-- tracking bank/mobile-money transactions.
CREATE TABLE IF NOT EXISTS payments (
    id VARCHAR(20) PRIMARY KEY,
    contract_id VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
    status ENUM('completed','pending') NOT NULL DEFAULT 'pending',
    control_number VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service requests table: issues raised by tenants such as maintenance,
-- move-out notices, or contract extensions.
CREATE TABLE IF NOT EXISTS service_requests (
    id VARCHAR(20) PRIMARY KEY,
    tenant_id VARCHAR(20) NOT NULL,
    contract_id VARCHAR(20) NOT NULL,
    type ENUM('maintenance','move_out','extension') NOT NULL,
    description TEXT NOT NULL,
    date DATE NOT NULL,
    status ENUM('pending','approved','resolved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data: pre-populate the database with sample records so the
-- application has data to display immediately after setup.
-- Password for both seed users is: password
INSERT INTO users (id, username, password, full_name, phone, email, nida, role, approved, property_address, tenant_id) VALUES
('u1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', NULL, NULL, NULL, 'admin', 1, NULL, NULL),
('u2', 'johndoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '0712345678', 'john@example.com', NULL, 'tenant', 1, NULL, 't1'),
('u3', 'landlord', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Property Owner', '0765432100', 'landlord@example.com', '19800101123456', 'landlord', 1, '123 Landlord Ave, Dar es Salaam', NULL)
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- Seed a few properties with different rent amounts and statuses.
INSERT INTO properties (id, title, address, rent_amount, status) VALUES
('p1', 'Sunset Apartment A1', '123 Arusha Way', 500000, 'rented'),
('p2', 'Sunset Apartment A2', '125 Arusha Way', 550000, 'available'),
('p3', 'Downtown Commercial Hub', '99 Business Ave, Dar es Salaam', 1200000, 'rented')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- Seed sample tenants who will be linked to contracts.
INSERT INTO tenants (id, name, email, phone) VALUES
('t1', 'John Doe', 'john@example.com', '0712345678'),
('t2', 'Jane Smith', 'jane@example.com', '0787654321')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed active rental contracts linking tenants to properties.
INSERT INTO contracts (id, property_id, tenant_id, start_date, end_date, status) VALUES
('c1', 'p1', 't1', '2025-01-01', '2026-12-31', 'active'),
('c2', 'p3', 't2', '2025-03-01', '2026-02-28', 'active')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Seed completed payments for the sample contracts.
INSERT INTO payments (id, contract_id, amount, date, method, status) VALUES
('pay1', 'c1', 500000, '2025-01-05', 'bank_transfer', 'completed'),
('pay2', 'c1', 500000, '2025-02-05', 'mobile_money', 'completed'),
('pay3', 'c2', 1200000, '2025-03-02', 'bank_transfer', 'completed')
ON DUPLICATE KEY UPDATE status=VALUES(status);
