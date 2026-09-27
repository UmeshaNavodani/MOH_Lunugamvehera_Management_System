<?php

session_start();

include "./config.php";

$role = $_GET['role'] ?? $_POST['role'] ?? 'moh_officer';

$allowed_roles = [
    'admin',
    'moh_officer',
    'staff'
];

if (!in_array($role, $allowed_roles)) {
    $role = 'moh_officer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter username and password.";
        header("Location: login.php?role=" . urlencode($role));
        exit;
    }

    // Using MySQLi prepared statement
    $sql = "SELECT *
            FROM users
            WHERE username = ?
            AND role = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_success'] = "Welcome, " . $user['full_name'] . "!";

        // Set redirect URL based on role
        if ($user['role'] === 'admin') {
            $_SESSION['redirect_url'] = "admin/dashboard.php";
        } elseif ($user['role'] === 'moh_officer') {
            $_SESSION['redirect_url'] = "moh/dashboard.php";
        } elseif ($user['role'] === 'staff') {
            $_SESSION['redirect_url'] = "staff/dashboard.php";
        } else {
            session_destroy();
            header("Location: login.php?role=" . urlencode($role));
            exit;
        }

        // Redirect back to login page to show success message
        header("Location: login.php?role=" . urlencode($role) . "&success=1");
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid username or password.";
        header("Location: login.php?role=" . urlencode($role));
        exit;
    }
}

// Handle success redirect
if (isset($_GET['success']) && isset($_SESSION['login_success']) && isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    $success_message = $_SESSION['login_success'];
    // Don't unset yet - we need it for the toast display
} else {
    $redirect_url = '';
    $success_message = '';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo ucfirst(str_replace('_', ' ', $role)); ?> Login
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /* Ensure toastr displays properly */
        #toast-container {
            z-index: 99999 !important;
        }

        .toast {
            opacity: 1 !important;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-100">

    <!-- Background -->
    <div class="absolute inset-0">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSoa3xoRYETYWzqncwF2lpPZkKDgOOc9EUGqDlsMC6bruTEmIh5GGW9We8&s=10"
            class="w-full h-full object-cover" />
    </div>

    <!-- Light Glass Overlay -->
    <div class="absolute inset-0 backdrop-blur-sm bg-black/20"></div>

    <!-- LOGIN CARD -->
    <div class="relative z-10 w-full max-w-md p-8 rounded-2xl bg-white/70 backdrop-blur-xl shadow-2xl border border-white/40">

        <!-- Header -->
        <div class="text-center mb-6">
            <div class="flex justify-center mb-2">
                <?php if ($role == 'admin') { ?>
                    <i data-lucide="shield" class="w-10 h-10 text-blue-600"></i>
                <?php } elseif ($role == 'moh_officer') { ?>
                    <i data-lucide="stethoscope" class="w-10 h-10 text-green-600"></i>
                <?php } else { ?>
                    <i data-lucide="user" class="w-10 h-10 text-pink-600"></i>
                <?php } ?>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 capitalize">
                <?php echo str_replace('_', ' ', $role); ?> Login
            </h2>
            <p class="text-gray-500 text-sm">
                Welcome to MOH Lunugamvehera Healthcare System
            </p>
        </div>

        <!-- FORM -->
        <form method="POST" action="login.php?role=<?php echo htmlspecialchars($role); ?>" class="space-y-4">

            <!-- Role -->
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Username
                </label>
                <input type="text"
                    name="username"
                    autocomplete="username"
                    placeholder="Enter username"
                    required
                    class="w-full p-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input type="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="Enter password"
                    required
                    class="w-full p-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">
                Login as <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
            </button>

        </form>

        <!-- BACK -->
        <div class="text-center mt-6">
            <a href="index.php" class="text-xs text-gray-500 hover:text-gray-700">
                ← Back to Home
            </a>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Lucide -->
    <script>
        lucide.createIcons();
    </script>

    <!-- Toastr Configuration & Display -->
    <script>
        $(document).ready(function() {
            // Configure toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "3000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            <?php if (isset($_SESSION['login_error'])) { ?>
                // Display error toast
                toastr.error(
                    <?php echo json_encode($_SESSION['login_error']); ?>,
                    "Login Failed"
                );
                <?php unset($_SESSION['login_error']); ?>
            <?php } ?>

            <?php if (isset($_GET['success']) && isset($_SESSION['login_success']) && isset($_SESSION['redirect_url'])) {
                $redirect_url = $_SESSION['redirect_url'];
                $success_message = $_SESSION['login_success'];
            ?>
                // Display success toast
                toastr.success(
                    <?php echo json_encode($success_message); ?>,
                    "Login Successful! 🎉"
                );

                <?php if (isset($_SESSION['logout_success'])) { ?>
                    toastr.success(
                        <?php echo json_encode($_SESSION['logout_success']); ?>,
                        "Logged Out"
                    );
                    <?php unset($_SESSION['logout_success']); ?>
                <?php } ?>

                // Clear session data after displaying
                <?php
                unset($_SESSION['login_success']);
                unset($_SESSION['redirect_url']);
                ?>

                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = <?php echo json_encode($redirect_url); ?>;
                }, 2000);
            <?php } ?>
        });
    </script>

</body>

</html>