<?php
include "../config.php";
include "../auth/admin.php";

// Toastr Message Helper
function setMessage($type, $message)
{
    $_SESSION['patient_message_type'] = $type;
    $_SESSION['patient_message'] = $message;
}

// Generate Patient ID
function generatePatientID($conn)
{
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(patient_id, 5) AS UNSIGNED)) as max_id FROM patients");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return "PAT-" . str_pad($next_id, 6, "0", STR_PAD_LEFT);
}

// DELETE PATIENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    if ($patient_id > 0) {
        $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            setMessage("success", "Patient deleted successfully.");
        } else {
            setMessage("error", "Unable to delete patient.");
        }
        $stmt->close();
    }
    header("Location: patient.php");
    exit;
}

// ADD PATIENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $patient_id = generatePatientID($conn);
    $nic = trim($_POST['nic'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gn_division = trim($_POST['gn_division'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $registration_date = date('Y-m-d');

    // Validation
    if (empty($nic) || empty($full_name) || empty($date_of_birth) || empty($gender) || empty($address) || empty($phone)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: patient.php");
        exit;
    }

    // Check NIC
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE nic = ? LIMIT 1");
    $stmt->bind_param("s", $nic);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "NIC already exists.");
        header("Location: patient.php");
        exit;
    }
    $stmt->close();

    // Check Phone
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE phone = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "Phone number already exists.");
        header("Location: patient.php");
        exit;
    }
    $stmt->close();

    // Insert Patient
    $stmt = $conn->prepare("INSERT INTO patients (patient_id, nic, full_name, date_of_birth, gender, address, phone, email, gn_division, occupation, blood_group, emergency_contact, registration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssss", $patient_id, $nic, $full_name, $date_of_birth, $gender, $address, $phone, $email, $gn_division, $occupation, $blood_group, $emergency_contact, $registration_date);

    if ($stmt->execute()) {
        setMessage("success", "Patient registered successfully. Patient ID: " . $patient_id);
    } else {
        setMessage("error", "Failed to register patient.");
    }
    $stmt->close();
    header("Location: patient.php");
    exit;
}

// UPDATE PATIENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $nic = trim($_POST['nic'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gn_division = trim($_POST['gn_division'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');

    if ($patient_id <= 0 || empty($nic) || empty($full_name) || empty($date_of_birth) || empty($gender) || empty($address) || empty($phone)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: patient.php");
        exit;
    }

    // Check NIC belongs to another patient
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE nic = ? AND patient_id != ? LIMIT 1");
    $stmt->bind_param("si", $nic, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "NIC already belongs to another patient.");
        header("Location: patient.php?edit=" . $patient_id);
        exit;
    }
    $stmt->close();

    // Check Phone belongs to another patient
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE phone = ? AND patient_id != ? LIMIT 1");
    $stmt->bind_param("si", $phone, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        setMessage("error", "Phone number already belongs to another patient.");
        header("Location: patient.php?edit=" . $patient_id);
        exit;
    }
    $stmt->close();

    // Update Patient
    $stmt = $conn->prepare("UPDATE patients SET nic = ?, full_name = ?, date_of_birth = ?, gender = ?, address = ?, phone = ?, email = ?, gn_division = ?, occupation = ?, blood_group = ?, emergency_contact = ? WHERE patient_id = ?");
    $stmt->bind_param("sssssssssssi", $nic, $full_name, $date_of_birth, $gender, $address, $phone, $email, $gn_division, $occupation, $blood_group, $emergency_contact, $patient_id);

    if ($stmt->execute()) {
        setMessage("success", "Patient updated successfully.");
    } else {
        setMessage("error", "Failed to update patient.");
    }
    $stmt->close();
    header("Location: patient.php");
    exit;
}

// GET EDIT PATIENT
$edit_patient = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ? LIMIT 1");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_patient = $result->fetch_assoc();
        $stmt->close();
    }
}

// GET VIEW PATIENT
$view_patient = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    if ($view_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ? LIMIT 1");
        $stmt->bind_param("i", $view_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $view_patient = $result->fetch_assoc();
        $stmt->close();
    }
}

// SEARCH
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $search_value = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id LIKE ? OR nic LIKE ? OR full_name LIKE ? OR phone LIKE ? ORDER BY patient_id DESC");
    $stmt->bind_param("ssss", $search_value, $search_value, $search_value, $search_value);
    $stmt->execute();
    $patient_result = $stmt->get_result();
} else {
    $patient_result = $conn->query("SELECT * FROM patients ORDER BY patient_id DESC");
}

// Counts
$total_patients = 0;
$pregnant_mothers = 0;
$active_patients = 0;
$count_result = $conn->query("SELECT COUNT(*) AS total FROM patients");
if ($count_result) {
    $count_data = $count_result->fetch_assoc();
    $total_patients = $count_data['total'] ?? 0;
}

// For demo purposes - showing pregnant mothers (you can adjust this logic)
$pregnant_mothers = 135; // This would come from a pregnancies table in real implementation

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - RuralCare</title>
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
                <h1 class="text-3xl font-bold text-gray-800">Patient Management</h1>
                <p class="text-gray-600 mt-1">Register and manage patient records</p>
            </div>
            <a href="./dashboard.php" class="mt-4 md:mt-0 inline-flex items-center bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition">
                <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back
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
                        <p class="text-sm text-gray-500">Total Patients</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i data-lucide="user-check" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Active Patients</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- ADD / EDIT FORM -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i data-lucide="<?php echo $edit_patient ? 'edit' : 'user-plus'; ?>" class="w-7 h-7 text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-bold text-gray-800"><?php echo $edit_patient ? 'Update Patient Record' : 'Register New Patient'; ?></h2>
                    <p class="text-sm text-gray-500"><?php echo $edit_patient ? 'Update patient information' : 'Register a new patient with unique ID'; ?></p>
                </div>
            </div>
            <form method="POST" action="patient.php<?php echo $edit_patient ? '?edit=' . $edit_patient['patient_id'] : ''; ?>" class="space-y-6">
                <?php if ($edit_patient): ?>
                    <input type="hidden" name="patient_id" value="<?php echo $edit_patient['patient_id']; ?>">
                <?php endif; ?>
                <!-- PATIENT INFORMATION -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if ($edit_patient): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Patient ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($edit_patient['patient_id']); ?>" disabled class="w-full p-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500">
                            </div>
                        <?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIC *</label>
                            <input type="text" name="nic" required value="<?php echo htmlspecialchars($edit_patient['nic'] ?? ''); ?>" placeholder="NIC number" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($edit_patient['full_name'] ?? ''); ?>" placeholder="Full name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                            <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($edit_patient['date_of_birth'] ?? ''); ?>" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                            <select name="gender" required class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (($edit_patient['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($edit_patient['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (($edit_patient['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($edit_patient['phone'] ?? ''); ?>" placeholder="07XXXXXXXX" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_patient['email'] ?? ''); ?>" placeholder="email@example.com" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Blood Group</label>
                            <select name="blood_group" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo (($edit_patient['blood_group'] ?? '') === 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo (($edit_patient['blood_group'] ?? '') === 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo (($edit_patient['blood_group'] ?? '') === 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo (($edit_patient['blood_group'] ?? '') === 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo (($edit_patient['blood_group'] ?? '') === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo (($edit_patient['blood_group'] ?? '') === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo (($edit_patient['blood_group'] ?? '') === 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo (($edit_patient['blood_group'] ?? '') === 'O-') ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <!-- ADDITIONAL INFORMATION -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">GN Division</label>
                            <input type="text" name="gn_division" value="<?php echo htmlspecialchars($edit_patient['gn_division'] ?? ''); ?>" placeholder="GN Division" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Occupation</label>
                            <input type="text" name="occupation" value="<?php echo htmlspecialchars($edit_patient['occupation'] ?? ''); ?>" placeholder="Occupation" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact</label>
                            <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($edit_patient['emergency_contact'] ?? ''); ?>" placeholder="Emergency contact number" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                        <textarea name="address" rows="3" required placeholder="Enter patient address" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300"><?php echo htmlspecialchars($edit_patient['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <?php if ($edit_patient): ?>
                        <a href="patient.php" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Cancel</a>
                        <button type="submit" name="update_patient" class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                            <i data-lucide="save" class="w-5 h-5 inline mr-1"></i> Update Patient
                        </button>
                    <?php else: ?>
                        <button type="reset" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Clear</button>
                        <button type="submit" name="add_patient" class="px-5 py-3 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                            <i data-lucide="user-plus" class="w-5 h-5 inline mr-1"></i> Register Patient
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- PATIENT LIST -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <!-- List Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Patient List</h2>
                    <p class="text-sm text-gray-500">View and manage registered patients</p>
                </div>
                <!-- Search -->
                <form method="GET" action="patient.php" class="mt-4 md:mt-0 flex">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by ID, NIC, Name or Phone..." class="p-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-300 w-64">
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
                            <th class="px-4 py-3">Patient ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">NIC</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3">DOB</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">GN Division</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($patient_result && $patient_result->num_rows > 0): ?>
                            <?php while ($row = $patient_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 font-semibold text-blue-600"><?php echo htmlspecialchars($row['patient_id']); ?></td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['occupation'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['nic']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($row['date_of_birth'])); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($row['gn_division'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="patient.php?view=<?php echo $row['patient_id']; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg" title="View Profile">
                                                <i data-lucide="user" class="w-5 h-5"></i>
                                            </a>
                                            <a href="patient.php?edit=<?php echo $row['patient_id']; ?>" class="p-2 text-yellow-600 hover:bg-yellow-100 rounded-lg" title="Edit">
                                                <i data-lucide="edit" class="w-5 h-5"></i>
                                            </a>
                                            <form method="POST" action="patient.php" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                                <input type="hidden" name="patient_id" value="<?php echo $row['patient_id']; ?>">
                                                <button type="submit" name="delete_patient" class="p-2 text-red-600 hover:bg-red-100 rounded-lg" title="Delete">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="users" class="w-12 h-12 text-gray-300 mb-3"></i>
                                        <p class="font-medium">No patients found</p>
                                        <p class="text-sm mt-1">Register your first patient.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- VIEW PATIENT MODAL -->
    <?php if ($view_patient): ?>
        <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i data-lucide="user" class="w-7 h-7 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold text-gray-800">Patient Profile</h2>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($view_patient['patient_id']); ?></p>
                        </div>
                    </div>
                    <a href="patient.php" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </a>
                </div>
                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Patient Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i data-lucide="clipboard" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Patient Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 rounded-lg p-6">
                            <div>
                                <p class="text-sm text-gray-500">Full Name</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['full_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">NIC</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['nic']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date of Birth</p>
                                <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($view_patient['date_of_birth'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Gender</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['gender']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['phone']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['email'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Blood Group</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['blood_group'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">GN Division</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['gn_division'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Occupation</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['occupation'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Emergency Contact</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['emergency_contact'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($view_patient['address']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Registration Date</p>
                                <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($view_patient['registration_date'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Records Sections -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-purple-100 p-2 rounded-lg">
                                        <i data-lucide="syringe" class="w-5 h-5 text-purple-600"></i>
                                    </div>
                                    <span class="ml-3 font-semibold text-gray-800">Vaccination History</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">View all vaccinations and immunizations</p>
                        </div>

                        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-green-100 p-2 rounded-lg">
                                        <i data-lucide="hospital" class="w-5 h-5 text-green-600"></i>
                                    </div>
                                    <span class="ml-3 font-semibold text-gray-800">Clinic Visits</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">View all clinic visit records</p>
                        </div>

                        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-pink-100 p-2 rounded-lg">
                                        <i data-lucide="baby" class="w-5 h-5 text-pink-600"></i>
                                    </div>
                                    <span class="ml-3 font-semibold text-gray-800">Maternal Records</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">View pregnancy and maternal health records</p>
                        </div>

                        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-2 rounded-lg">
                                        <i data-lucide="heart" class="w-5 h-5 text-blue-600"></i>
                                    </div>
                                    <span class="ml-3 font-semibold text-gray-800">Child Health Records</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">View child health and development records</p>
                        </div>

                        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition md:col-span-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-red-100 p-2 rounded-lg">
                                        <i data-lucide="activity" class="w-5 h-5 text-red-600"></i>
                                    </div>
                                    <span class="ml-3 font-semibold text-gray-800">Disease Records</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">View disease diagnosis and treatment records</p>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end gap-3">
                        <a href="patient.php" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Close</a>
                        <a href="patient.php?edit=<?php echo $view_patient['patient_id']; ?>" class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                            <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i> Edit Patient
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
        <?php if (isset($_SESSION['patient_message'])): ?>
            <?php if ($_SESSION['patient_message_type'] === 'success'): ?>
                toastr.success(<?php echo json_encode($_SESSION['patient_message']); ?>, "Success");
            <?php else: ?>
                toastr.error(<?php echo json_encode($_SESSION['patient_message']); ?>, "Error");
            <?php endif; ?>
            <?php unset($_SESSION['patient_message']);
            unset($_SESSION['patient_message_type']); ?>
        <?php endif; ?>
    </script>
</body>

</html>