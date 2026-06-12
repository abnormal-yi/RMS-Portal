<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- setup.php: One-click database initialisation and seeding page. Presents setup instructions and a button to trigger init_db.php. -->
    <title>Online Rental Management System - Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full border border-gray-200">
        <h1 class="text-2xl font-bold text-center text-gray-900 mb-2">Online Rental Management System Setup</h1>
        <p class="text-sm text-center text-gray-500 mb-6">Initialize the database and seed data</p>
        <!-- Setup instructions: prerequisites and configuration steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-semibold text-blue-800 mb-2">Before you begin:</h3>
            <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
                <li>Ensure MySQL is running</li>
                <li>Set your DB credentials in <code class="bg-blue-100 px-1 rounded">config/database.php</code></li>
                <li>Or set environment variables: DB_HOST, DB_NAME, DB_USER, DB_PASS</li>
            </ol>
        </div>
        <!-- Form that POSTs to init_db.php to create tables and seed initial data -->
        <form method="post" action="config/init_db.php">
            <button type="submit" class="w-full bg-[#7B5CFA] hover:bg-[#6849E3] text-white font-medium rounded-xl px-6 py-3 transition-colors shadow-lg shadow-[#7B5CFA]/20">
                Initialize Database &amp; Seed Data
            </button>
        </form>
        <p class="text-xs text-gray-400 text-center mt-6">This will create the database and tables if they don't exist.</p>
    </div>
</body>
</html>
