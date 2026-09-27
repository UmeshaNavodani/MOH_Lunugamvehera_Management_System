<?php
session_start();
include "../config.php";

// Toastr Message Helper
function setMessage($type, $message)
{
    $_SESSION['staff_message_type'] = $type;
    $_SESSION['staff_message'] = $message;
}
// DELETE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $staff_id = intval($_POST['staff_id']);
    if ($staff_id > 0) {
        $stmt = $conn->prepare("SELECT user_id FROM staff WHERE staff_id = ? LIMIT 1");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();
        if ($staff) {
            $user_id = $staff['user_id'];
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                setMessage("success", "Staff member and login account deleted successfully.");
            } catch (Exception $e) {
                $conn->rollback();
                setMessage("error", "Unable to delete staff member.");
            }
        } else {
            setMessage("error", "Staff member not found.");
        }
    }
    header("Location: staff.php");
    exit;
}
// ADD STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staff_code = trim($_POST['staff_code'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $joining_date = $_POST['joining_date'] ?? '';
    $status = trim($_POST['status'] ?? 'Active');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // Validation
    if (empty($staff_code) || empty($full_name) || empty($nic) || empty($gender) || empty($date_of_birth) || empty($phone) || empty($address) || empty($designation) || empty($department) || empty($joining_date) || empty($username) || empty($password)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: staff.php");
        exit;
    }
    // Check username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "Username already exists.");
        header("Location: staff.php");
        exit;
    }
    $stmt->close();
    // Check Staff Code
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE staff_code = ? LIMIT 1");
    $stmt->bind_param("s", $staff_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "Staff ID already exists.");
        header("Location: staff.php");
        exit;
    }
    $stmt->close();
    // Check NIC
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE nic = ? LIMIT 1");
    $stmt->bind_param("s", $nic);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "NIC already exists.");
        header("Location: staff.php");
        exit;
    }
    $stmt->close();
    // Create User + Staff
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'staff')");
        $stmt->bind_param("ss", $username, $hashed_password);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO staff (user_id, staff_code, full_name, nic, gender, date_of_birth, phone, address, designation, department, joining_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssssss", $user_id, $staff_code, $full_name, $nic, $gender, $date_of_birth, $phone, $address, $designation, $department, $joining_date, $status);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        setMessage("success", "Staff member registered successfully.");
    } catch (Exception $e) {
        $conn->rollback();
        setMessage("error", "Failed to register staff member.");
    }
    header("Location: staff.php");
    exit;
}
// UPDATE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $staff_id = intval($_POST['staff_id']);
    $staff_code = trim($_POST['staff_code'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $joining_date = $_POST['joining_date'] ?? '';
    $status = trim($_POST['status'] ?? 'Active');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($staff_id <= 0 || empty($staff_code) || empty($full_name) || empty($nic) || empty($gender) || empty($date_of_birth) || empty($phone) || empty($address) || empty($designation) || empty($department) || empty($joining_date) || empty($username)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: staff.php");
        exit;
    }
    // Get existing user_id
    $stmt = $conn->prepare("SELECT user_id FROM staff WHERE staff_id = ? LIMIT 1");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_staff = $result->fetch_assoc();
    $stmt->close();
    if (!$existing_staff) {
        setMessage("error", "Staff member not found.");
        header("Location: staff.php");
        exit;
    }
    $user_id = $existing_staff['user_id'];
    // Check username belongs to another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "Username already belongs to another account.");
        header("Location: staff.php?edit=" . $staff_id);
        exit;
    }
    $stmt->close();
    // Transaction
    $conn->begin_transaction();
    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE staff SET staff_code = ?, full_name = ?, nic = ?, gender = ?, date_of_birth = ?, phone = ?, address = ?, designation = ?, department = ?, joining_date = ?, status = ? WHERE staff_id = ?");
        $stmt->bind_param("sssssssssssi", $staff_code, $full_name, $nic, $gender, $date_of_birth, $phone, $address, $designation, $department, $joining_date, $status, $staff_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        setMessage("success", "Staff member updated successfully.");
    } catch (Exception $e) {
        $conn->rollback();
        setMessage("error", "Failed to update staff member.");
    }
    header("Location: staff.php");
    exit;
}
// GET EDIT STAFF
$edit_staff = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT s.*, u.username FROM staff s INNER JOIN users u ON s.user_id = u.id WHERE s.staff_id = ? LIMIT 1");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_staff = $result->fetch_assoc();
        $stmt->close();
    }
}
// SEARCH
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $search_value = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT s.*, u.username FROM staff s INNER JOIN users u ON s.user_id = u.id WHERE s.staff_code LIKE ? OR s.full_name LIKE ? OR s.nic LIKE ? OR s.designation LIKE ? OR s.department LIKE ? ORDER BY s.staff_id DESC");
    $stmt->bind_param("sssss", $search_value, $search_value, $search_value, $search_value, $search_value);
    $stmt->execute();
    $staff_result = $stmt->get_result();
} else {
    $staff_result = $conn->query("SELECT s.*, u.username FROM staff s INNER JOIN users u ON s.user_id = u.id ORDER BY s.staff_id DESC");
}
// Counts
$total_staff = 0;
$active_staff = 0;
$inactive_staff = 0;
$count_result = $conn->query("SELECT COUNT(*) AS total, SUM(status = 'Active') AS active, SUM(status = 'Inactive') AS inactive FROM staff");
if ($count_result) {
    $count_data = $count_result->fetch_assoc();
    $total_staff = $count_data['total'] ?? 0;
    $active_staff = $count_data['active'] ?? 0;
    $inactive_staff = $count_data['inactive'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - RuralCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>

<body class="bg-gray-100">
    <!-- NAVIGATION -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i data-lucide="shield" class="w-8 h-8 text-blue-600"></i>
                    <span class="ml-2 text-xl font-bold text-gray-800">Admin Panel</span>
                </div>
            </div>
        </div>
    </nav>
    <!-- MAIN -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Staff Management</h1>
                <p class="text-gray-600 mt-1">Register and manage healthcare staff</p>
            </div>
            <a href="./dashboard.php" class="mt-4 md:mt-0 inline-flex items-center bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition">
                <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Back
            </a>
        </div>
        <!-- STATISTICS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i data-lucide="users" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Staff</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_staff; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i data-lucide="user-check" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Active Staff</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $active_staff; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i data-lucide="user-x" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Inactive Staff</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $inactive_staff; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- ADD / EDIT FORM -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i data-lucide="<?php echo $edit_staff ? 'edit' : 'user-plus'; ?>" class="w-7 h-7 text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-bold text-gray-800"><?php echo $edit_staff ? 'Update Staff Member' : 'Register New Staff'; ?></h2>
                    <p class="text-sm text-gray-500"><?php echo $edit_staff ? 'Update staff and login account information' : 'Create staff member and login account'; ?></p>
                </div>
            </div>
            <form method="POST" action="staff.php<?php echo $edit_staff ? '?edit=' . $edit_staff['staff_id'] : ''; ?>" class="space-y-6">
                <?php if ($edit_staff): ?>
                    <input type="hidden" name="staff_id" value="<?php echo $edit_staff['staff_id']; ?>">
                <?php endif; ?>
                <!-- LOGIN INFORMATION -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Login Account</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" required value="<?php echo htmlspecialchars($edit_staff['username'] ?? ''); ?>" placeholder="Enter username" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password <?php if (!$edit_staff): ?>*<?php endif; ?></label>
                            <input type="password" name="password" <?php echo !$edit_staff ? 'required' : ''; ?> placeholder="<?php echo $edit_staff ? 'Leave empty to keep current password' : 'Enter password'; ?>" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                    </div>
                </div>
                <hr>
                <!-- STAFF INFORMATION -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Staff Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Staff ID *</label>
                            <input type="text" name="staff_code" required value="<?php echo htmlspecialchars($edit_staff['staff_code'] ?? ''); ?>" placeholder="STF-0001" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($edit_staff['full_name'] ?? ''); ?>" placeholder="Full name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIC *</label>
                            <input type="text" name="nic" required value="<?php echo htmlspecialchars($edit_staff['nic'] ?? ''); ?>" placeholder="NIC number" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                            <select name="gender" required class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (($edit_staff['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($edit_staff['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (($edit_staff['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                            <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($edit_staff['date_of_birth'] ?? ''); ?>" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($edit_staff['phone'] ?? ''); ?>" placeholder="07XXXXXXXX" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Designation *</label>
                            <select name="designation" required class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Select Designation</option>
                                <option value="Doctor" <?php echo (($edit_staff['designation'] ?? '') === 'Doctor') ? 'selected' : ''; ?>>Doctor</option>
                                <option value="Nurse" <?php echo (($edit_staff['designation'] ?? '') === 'Nurse') ? 'selected' : ''; ?>>Nurse</option>
                                <option value="PHI" <?php echo (($edit_staff['designation'] ?? '') === 'PHI') ? 'selected' : ''; ?>>PHI</option>
                                <option value="Midwife" <?php echo (($edit_staff['designation'] ?? '') === 'Midwife') ? 'selected' : ''; ?>>Midwife</option>
                                <option value="Other" <?php echo (($edit_staff['designation'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other Healthcare Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <input type="text" name="department" required value="<?php echo htmlspecialchars($edit_staff['department'] ?? ''); ?>" placeholder="MOH / Clinic / Public Health" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Joining Date *</label>
                            <input type="date" name="joining_date" required value="<?php echo htmlspecialchars($edit_staff['joining_date'] ?? ''); ?>" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="Active" <?php echo (($edit_staff['status'] ?? 'Active') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (($edit_staff['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                        <textarea name="address" rows="3" required placeholder="Enter staff address" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300"><?php echo htmlspecialchars($edit_staff['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <?php if ($edit_staff): ?>
                        <a href="staff.php" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Cancel</a>
                        <button type="submit" name="update_staff" class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                            <i data-lucide="save" class="w-5 h-5 inline mr-1"></i> Update Staff
                        </button>
                    <?php else: ?>
                        <button type="reset" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Clear</button>
                        <button type="submit" name="add_staff" class="px-5 py-3 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                            <i data-lucide="user-plus" class="w-5 h-5 inline mr-1"></i> Register Staff
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- STAFF LIST -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <!-- List Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Staff List</h2>
                    <p class="text-sm text-gray-500">View and manage registered healthcare staff</p>
                </div>
                <!-- Search -->
                <form method="GET" action="staff.php" class="mt-4 md:mt-0 flex">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search staff..." class="p-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <button type="submit" class="bg-blue-600 text-white px-4 rounded-r-lg hover:bg-blue-700">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Staff ID</th>
                            <th class="px-4 py-3">Staff</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">NIC</th>
                            <th class="px-4 py-3">Designation</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                            <?php while ($row = $staff_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 font-semibold text-blue-600"><?php echo htmlspecialchars($row['staff_code']); ?></td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['gender']); ?></div>
                                    </td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['nic']); ?></td>
                                    <td class="px-4 py-4"><?php echo htmlspecialchars($row['designation']); ?></td>
                                    <td class="px-4 py-4"><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td class="px-4 py-4"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-4 py-4">
                                        <?php if ($row['status'] === 'Active'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="staff.php?edit=<?php echo $row['staff_id']; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg" title="Edit">
                                                <i data-lucide="edit" class="w-5 h-5"></i>
                                            </a>
                                            <form method="POST" action="staff.php" onsubmit="return confirm('Are you sure you want to delete this staff member and their login account?');">
                                                <input type="hidden" name="staff_id" value="<?php echo $row['staff_id']; ?>">
                                                <button type="submit" name="delete_staff" class="p-2 text-red-600 hover:bg-red-100 rounded-lg" title="Delete">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="users" class="w-12 h-12 text-gray-300 mb-3"></i>
                                        <p class="font-medium">No staff members found</p>
                                        <p class="text-sm mt-1">Register your first healthcare staff member.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- JAVASCRIPT -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        lucide.createIcons();
        // Toastr Settings
        toastr.options = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            positionClass: "toast-top-right",
            timeOut: 2000,
            extendedTimeOut: 500,
            preventDuplicates: true
        };
        // PHP Messages
        <?php if (isset($_SESSION['staff_message'])): ?>
            <?php if ($_SESSION['staff_message_type'] === 'success'): ?>
                toastr.success(<?php echo json_encode($_SESSION['staff_message']); ?>, "Success");
            <?php else: ?>
                toastr.error(<?php echo json_encode($_SESSION['staff_message']); ?>, "Error");
            <?php endif; ?>
            <?php unset($_SESSION['staff_message']);
            unset($_SESSION['staff_message_type']); ?>
        <?php endif; ?>
    </script>
</body>

</html>