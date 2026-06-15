<?php
/*----------------------------------------------------------------------
  init_db.php  —  One-time database setup script
  Creates the MySQL database (if it does not exist), then creates every
  table defined in the schema and inserts seed data if the DB is empty.
  Access this script via a browser to run the initial setup.
----------------------------------------------------------------------*/

// Database credentials — same fallback logic as config/database.php.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'rms_portal';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Rental Management System - Setup Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-2xl w-full border border-gray-200">
        <h1 class="text-2xl font-bold text-center text-gray-900 mb-6">Setup Result</h1>
        <div class="space-y-2">
<?php
try {
    // Connect to MySQL server without specifying a database so we can
    // issue a CREATE DATABASE statement if needed.
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create the application database if it does not yet exist.
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg text-sm'>✓ Database '{$DB_NAME}' created or already exists.</div>";

    // Switch to the newly created / existing database.
    $pdo->exec("USE `$DB_NAME`");

    // Define all application tables with their schemas.
    // Each entry is a pair of table name and CREATE TABLE statement.
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` VARCHAR(20) PRIMARY KEY,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin','tenant','landlord') NOT NULL DEFAULT 'tenant',
            `tenant_id` VARCHAR(20) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'properties' => "CREATE TABLE IF NOT EXISTS `properties` (
            `id` VARCHAR(20) PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `address` TEXT NOT NULL,
            `rent_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `status` ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'tenants' => "CREATE TABLE IF NOT EXISTS `tenants` (
            `id` VARCHAR(20) PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'contracts' => "CREATE TABLE IF NOT EXISTS `contracts` (
            `id` VARCHAR(20) PRIMARY KEY,
            `property_id` VARCHAR(20) NOT NULL,
            `tenant_id` VARCHAR(20) NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `status` ENUM('active','terminated') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'payments' => "CREATE TABLE IF NOT EXISTS `payments` (
            `id` VARCHAR(20) PRIMARY KEY,
            `contract_id` VARCHAR(20) NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `date` DATE NOT NULL,
            `method` VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
            `status` ENUM('completed','pending') NOT NULL DEFAULT 'pending',
            `control_number` VARCHAR(50) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'service_requests' => "CREATE TABLE IF NOT EXISTS `service_requests` (
            `id` VARCHAR(20) PRIMARY KEY,
            `tenant_id` VARCHAR(20) NOT NULL,
            `contract_id` VARCHAR(20) NOT NULL,
            `type` ENUM('maintenance','move_out','extension') NOT NULL,
            `description` TEXT NOT NULL,
            `date` DATE NOT NULL,
            `status` ENUM('pending','approved','resolved','rejected') NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    // Loop through each table definition and execute the CREATE TABLE.
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "<div class='bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg text-sm'>✓ Table '{$name}' ready.</div>";
    }

    // Check if any users already exist. If the database is empty, insert
    // seed data so the application has sample records to display.
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    if ($stmt->fetch()['cnt'] == 0) {
        // Insert seed admin and tenant users.
        $pdo->exec("INSERT INTO `users` (`id`, `username`, `password`, `role`, `tenant_id`) VALUES
            ('u1', 'admin', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin', NULL),
            ('u2', 'johndoe', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'tenant', 't1'),
            ('u3', 'landlord', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'landlord', NULL)");
        // Insert seed properties with varying rent amounts.
        $pdo->exec("INSERT INTO `properties` (`id`, `title`, `address`, `rent_amount`, `status`) VALUES
            ('p1', 'Sunset Apartment A1', '123 Arusha Way', 500000, 'rented'),
            ('p2', 'Sunset Apartment A2', '125 Arusha Way', 550000, 'available'),
            ('p3', 'Downtown Commercial Hub', '99 Business Ave, Dar es Salaam', 1200000, 'rented')");
        // Insert seed tenant records.
        $pdo->exec("INSERT INTO `tenants` (`id`, `name`, `email`, `phone`) VALUES
            ('t1', 'John Doe', 'john@example.com', '0712345678'),
            ('t2', 'Jane Smith', 'jane@example.com', '0787654321')");
        // Insert seed contracts linking tenants to properties.
        $pdo->exec("INSERT INTO `contracts` (`id`, `property_id`, `tenant_id`, `start_date`, `end_date`, `status`) VALUES
            ('c1', 'p1', 't1', '2025-01-01', '2026-12-31', 'active'),
            ('c2', 'p3', 't2', '2025-03-01', '2026-02-28', 'active')");
        // Insert seed payments for the active contracts.
        $pdo->exec("INSERT INTO `payments` (`id`, `contract_id`, `amount`, `date`, `method`, `status`) VALUES
            ('pay1', 'c1', 500000, '2025-01-05', 'bank_transfer', 'completed'),
            ('pay2', 'c1', 500000, '2025-02-05', 'mobile_money', 'completed'),
            ('pay3', 'c2', 1200000, '2025-03-02', 'bank_transfer', 'completed')");
        echo "<div class='bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg text-sm'>✓ Seed data inserted.</div>";
    } else {
        echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded-lg text-sm'>ℹ Seed data skipped (database already populated).</div>";
    }

    echo "<div class='bg-green-100 border border-green-300 text-green-800 p-4 rounded-lg text-sm font-semibold mt-4'>✓ Database initialized successfully!</div>";
} catch (PDOException $e) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-sm'>✗ Error: " . hsc($e->getMessage()) . "</div>";
}

/**
 * hsc()  —  Shortcut for htmlspecialchars() to safely escape output.
 */
function hsc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
        </div>
        <div class="mt-8 text-center">
            <a href="../login.php" class="inline-block bg-[#7B5CFA] hover:bg-[#6849E3] text-white font-medium rounded-xl px-6 py-3 transition-colors shadow-lg shadow-[#7B5CFA]/20">
                Go to Login
            </a>
        </div>
    </div>
</body>
</html>
