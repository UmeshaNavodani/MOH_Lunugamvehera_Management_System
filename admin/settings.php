<?php
include "../config.php";
include "../auth/admin.php";


// Toastr Message Helper
function setMessage($type, $message)
{
    $_SESSION['settings_message_type'] = $type;
    $_SESSION['settings_message'] = $message;
}

// GET SETTINGS BY TYPE
function getSettings($conn, $type, $activeOnly = true)
{
    $sql = "SELECT * FROM settings WHERE setting_type = ?";
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $sql .= " ORDER BY display_order ASC, setting_key ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }
    $stmt->close();
    return $settings;
}

// GET SETTING BY KEY
function getSetting($conn, $key)
{
    $stmt = $conn->prepare("SELECT * FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    $stmt->close();
    return $setting;
}

// ADD SETTING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_setting'])) {
    $setting_key = trim($_POST['setting_key'] ?? '');
    $setting_value = trim($_POST['setting_value'] ?? '');
    $setting_type = trim($_POST['setting_type'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);

    if (empty($setting_key) || empty($setting_value) || empty($setting_type)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: settings.php");
        exit;
    }

    // Check if key already exists
    $existing = getSetting($conn, $setting_key);
    if ($existing) {
        setMessage("error", "Setting key already exists. Please use a unique key.");
        header("Location: settings.php");
        exit;
    }

    // Sanitize key - only allow letters, numbers, underscores
    $setting_key = preg_replace('/[^a-zA-Z0-9_]/', '_', $setting_key);
    $setting_key = strtolower($setting_key);

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, display_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $setting_key, $setting_value, $setting_type, $display_order);

    if ($stmt->execute()) {
        setMessage("success", "Setting added successfully.");
    } else {
        setMessage("error", "Failed to add setting.");
    }
    $stmt->close();
    header("Location: settings.php");
    exit;
}

// UPDATE SETTING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_setting'])) {
    $setting_id = intval($_POST['setting_id']);
    $setting_value = trim($_POST['setting_value'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($setting_id <= 0 || empty($setting_value)) {
        setMessage("error", "Please fill in all required fields.");
        header("Location: settings.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, display_order = ?, is_active = ? WHERE setting_id = ?");
    $stmt->bind_param("siii", $setting_value, $display_order, $is_active, $setting_id);

    if ($stmt->execute()) {
        setMessage("success", "Setting updated successfully.");
    } else {
        setMessage("error", "Failed to update setting.");
    }
    $stmt->close();
    header("Location: settings.php");
    exit;
}

// DELETE SETTING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_setting'])) {
    $setting_id = intval($_POST['setting_id']);

    if ($setting_id > 0) {
        $stmt = $conn->prepare("DELETE FROM settings WHERE setting_id = ?");
        $stmt->bind_param("i", $setting_id);
        if ($stmt->execute()) {
            setMessage("success", "Setting deleted successfully.");
        } else {
            setMessage("error", "Unable to delete setting.");
        }
        $stmt->close();
    }
    header("Location: settings.php");
    exit;
}

// TOGGLE SETTING STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $setting_id = intval($_POST['setting_id']);
    $current_status = intval($_POST['current_status']);

    if ($setting_id > 0) {
        $new_status = $current_status ? 0 : 1;
        $stmt = $conn->prepare("UPDATE settings SET is_active = ? WHERE setting_id = ?");
        $stmt->bind_param("ii", $new_status, $setting_id);
        if ($stmt->execute()) {
            setMessage("success", "Setting status updated successfully.");
        } else {
            setMessage("error", "Unable to update status.");
        }
        $stmt->close();
    }
    header("Location: settings.php");
    exit;
}

// GET EDIT SETTING
$edit_setting = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM settings WHERE setting_id = ? LIMIT 1");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_setting = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get all settings by type for display
$moh_areas = getSettings($conn, 'moh_area');
$clinic_types = getSettings($conn, 'clinic_type');
$vaccine_types = getSettings($conn, 'vaccine_type');
$disease_types = getSettings($conn, 'disease_type');

// Get all settings for the table (including inactive)
$all_settings = [];
$result = $conn->query("SELECT * FROM settings ORDER BY setting_type, display_order, setting_key");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_settings[] = $row;
    }
}

// Counts
$total_settings = count($all_settings);
$active_settings = 0;
foreach ($all_settings as $s) {
    if ($s['is_active']) $active_settings++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - RuralCare</title>
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
                <h1 class="text-3xl font-bold text-gray-800">System Settings</h1>
                <p class="text-gray-600 mt-1">Manage MOH areas, clinic types, vaccine types, and disease types</p>
            </div>
            <a href="./dashboard.php" class="mt-4 md:mt-0 inline-flex items-center bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition">
                <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back to Dashboard
            </a>
        </div>

        <!-- STATISTICS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i data-lucide="settings" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Settings</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_settings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i data-lucide="check-circle" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Active Settings</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $active_settings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i data-lucide="map-pin" class="w-8 h-8 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">MOH Areas</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo count($moh_areas); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center">
                    <div class="bg-pink-100 p-3 rounded-lg">
                        <i data-lucide="syringe" class="w-8 h-8 text-pink-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Vaccine Types</p>
                        <p class="text-2xl font-bold text-pink-600"><?php echo count($vaccine_types); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK STATS CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Clinic Types</h3>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo count($clinic_types); ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-lg">
                        <i data-lucide="hospital" class="w-6 h-6 text-indigo-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Disease Types</h3>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo count($disease_types); ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i data-lucide="activity" class="w-6 h-6 text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADD SETTING FORM -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i data-lucide="<?php echo $edit_setting ? 'edit' : 'plus-circle'; ?>" class="w-7 h-7 text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-bold text-gray-800"><?php echo $edit_setting ? 'Update Setting' : 'Add New Setting'; ?></h2>
                    <p class="text-sm text-gray-500"><?php echo $edit_setting ? 'Update existing setting details' : 'Add a new setting to the system'; ?></p>
                </div>
            </div>
            <form method="POST" action="settings.php<?php echo $edit_setting ? '?edit=' . $edit_setting['setting_id'] : ''; ?>" class="space-y-6">
                <?php if ($edit_setting): ?>
                    <input type="hidden" name="setting_id" value="<?php echo $edit_setting['setting_id']; ?>">
                    <input type="hidden" name="setting_key" value="<?php echo htmlspecialchars($edit_setting['setting_key']); ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!$edit_setting): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Setting Type *</label>
                            <select name="setting_type" required class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Select Type</option>
                                <option value="moh_area">MOH Area</option>
                                <option value="clinic_type">Clinic Type</option>
                                <option value="vaccine_type">Vaccine Type</option>
                                <option value="disease_type">Disease Type</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Setting Key *</label>
                            <input type="text" name="setting_key" required placeholder="e.g., moh_central" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <p class="text-xs text-gray-500 mt-1">Use lowercase with underscores (e.g., moh_central)</p>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Setting Type</label>
                            <input type="text" value="<?php echo ucwords(str_replace('_', ' ', htmlspecialchars($edit_setting['setting_type']))); ?>" disabled class="w-full p-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Setting Key</label>
                            <input type="text" value="<?php echo htmlspecialchars($edit_setting['setting_key']); ?>" disabled class="w-full p-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500">
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Value *</label>
                        <input type="text" name="setting_value" required value="<?php echo htmlspecialchars($edit_setting['setting_value'] ?? ''); ?>" placeholder="Display name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" name="display_order" value="<?php echo htmlspecialchars($edit_setting['display_order'] ?? 0); ?>" min="0" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    </div>
                    <?php if ($edit_setting): ?>
                        <div class="flex items-center space-x-2 pt-6">
                            <input type="checkbox" name="is_active" id="is_active" <?php echo ($edit_setting['is_active'] ?? 0) ? 'checked' : ''; ?> class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <?php if ($edit_setting): ?>
                        <a href="settings.php" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Cancel</a>
                        <button type="submit" name="update_setting" class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                            <i data-lucide="save" class="w-5 h-5 inline mr-1"></i> Update Setting
                        </button>
                    <?php else: ?>
                        <button type="reset" class="px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">Clear</button>
                        <button type="submit" name="add_setting" class="px-5 py-3 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                            <i data-lucide="plus" class="w-5 h-5 inline mr-1"></i> Add Setting
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- SETTINGS LIST -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <!-- List Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Settings List</h2>
                    <p class="text-sm text-gray-500">View and manage all system settings</p>
                </div>
            </div>

            <!-- Settings Cards by Type -->
            <div class="space-y-6">
                <!-- MOH Areas -->
                <?php if (!empty($moh_areas)): ?>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-purple-600"></i>
                            MOH Areas
                            <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($moh_areas); ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($moh_areas as $area): ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($area['setting_value']); ?></span>
                                        <div class="flex gap-1">
                                            <a href="settings.php?edit=<?php echo $area['setting_id']; ?>" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                <input type="hidden" name="setting_id" value="<?php echo $area['setting_id']; ?>">
                                                <button type="submit" name="delete_setting" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Key: <?php echo htmlspecialchars($area['setting_key']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Clinic Types -->
                <?php if (!empty($clinic_types)): ?>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i data-lucide="hospital" class="w-5 h-5 mr-2 text-indigo-600"></i>
                            Clinic Types
                            <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($clinic_types); ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($clinic_types as $clinic): ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($clinic['setting_value']); ?></span>
                                        <div class="flex gap-1">
                                            <a href="settings.php?edit=<?php echo $clinic['setting_id']; ?>" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                <input type="hidden" name="setting_id" value="<?php echo $clinic['setting_id']; ?>">
                                                <button type="submit" name="delete_setting" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Key: <?php echo htmlspecialchars($clinic['setting_key']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Vaccine Types -->
                <?php if (!empty($vaccine_types)): ?>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i data-lucide="syringe" class="w-5 h-5 mr-2 text-pink-600"></i>
                            Vaccine Types
                            <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($vaccine_types); ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($vaccine_types as $vaccine): ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vaccine['setting_value']); ?></span>
                                        <div class="flex gap-1">
                                            <a href="settings.php?edit=<?php echo $vaccine['setting_id']; ?>" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                <input type="hidden" name="setting_id" value="<?php echo $vaccine['setting_id']; ?>">
                                                <button type="submit" name="delete_setting" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Key: <?php echo htmlspecialchars($vaccine['setting_key']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Disease Types -->
                <?php if (!empty($disease_types)): ?>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i data-lucide="activity" class="w-5 h-5 mr-2 text-red-600"></i>
                            Disease Types
                            <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($disease_types); ?>)</span>
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($disease_types as $disease): ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($disease['setting_value']); ?></span>
                                        <div class="flex gap-1">
                                            <a href="settings.php?edit=<?php echo $disease['setting_id']; ?>" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                <input type="hidden" name="setting_id" value="<?php echo $disease['setting_id']; ?>">
                                                <button type="submit" name="delete_setting" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Key: <?php echo htmlspecialchars($disease['setting_key']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- No settings message -->
                <?php if (empty($moh_areas) && empty($clinic_types) && empty($vaccine_types) && empty($disease_types)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="settings" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">No settings found</p>
                        <p class="text-gray-400 text-sm mt-1">Add your first setting using the form above.</p>
                    </div>
                <?php endif; ?>
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
        <?php if (isset($_SESSION['settings_message'])): ?>
            <?php if ($_SESSION['settings_message_type'] === 'success'): ?>
                toastr.success(<?php echo json_encode($_SESSION['settings_message']); ?>, "Success");
            <?php else: ?>
                toastr.error(<?php echo json_encode($_SESSION['settings_message']); ?>, "Error");
            <?php endif; ?>
            <?php unset($_SESSION['settings_message']);
            unset($_SESSION['settings_message_type']); ?>
        <?php endif; ?>
    </script>
</body>

</html>