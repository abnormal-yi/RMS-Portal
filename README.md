# Online Rental Management System

PHP + MySQL rental property management system for landlords and tenants.

## Features

- **Admin Dashboard** — overview stats (properties, tenants, contracts, payments)
- **Properties** — CRUD with status (available/rented/maintenance)
- **Tenants** — tenant management
- **Contracts** — create/terminate, auto-updates property status, PDF download
- **Payments** — record payments with control number, mark pending as completed
- **Service Requests** — maintenance, move-out, extension requests with approval flow
- **Reports** — 6 metric cards, Chart.js bar chart, Print & PDF export
- **Tenant Portal** — view rental details, payment history, submit requests

## Requirements

- PHP 8.0+
- MySQL 8+ / MariaDB 10+
- PDO MySQL extension
- Composer (for TCPDF)

## Setup

1. Clone the repo
2. Run `composer install` to install TCPDF
3. Create a MySQL database and import `config/schema.sql`
4. Update `config/database.php` with your database credentials
5. Deploy to your web server (Apache/Nginx with PHP)

### Login Credentials

| Role   | Username | Password   |
|--------|----------|------------|
| Admin  | admin    | password   |
| Tenant | johndoe  | password   |
