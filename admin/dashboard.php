<?php
session_start();

include "../config.php";


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RuralCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i data-lucide="shield" class="w-8 h-8 text-blue-600"></i>
                    <span class="ml-2 text-xl font-bold text-gray-800">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 transition">
                        <i data-lucide="log-out" class="w-5 h-5 inline"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600">Welcome to the admin panel</p>
        </div>

        <!-- Quick Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="users.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i data-lucide="user-plus" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Patient Management</h3>
                        <p class="text-sm text-gray-500">Create, edit, and manage users</p>
                    </div>
                </div>
            </a>

            <a href="staff.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i data-lucide="users" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Staff Management</h3>
                        <p class="text-sm text-gray-500">Register and manage staff</p>
                    </div>
                </div>
            </a>

            <a href="settings.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i data-lucide="settings" class="w-8 h-8 text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">System Settings</h3>
                        <p class="text-sm text-gray-500">Configure system options</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        lucide.createIcons();

        <?php if (isset($_SESSION['login_success'])): ?>
            toastr.success(<?php echo json_encode($_SESSION['login_success']); ?>, "Welcome!");
            <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>
    </script>
</body>

</html>