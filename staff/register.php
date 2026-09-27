<?php
include "../config.php";

$success = "";
$error = "";

// FORM PROCESS 
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST['fullname'];
    $nic      = $_POST['nic'];
    $dob      = $_POST['dob'];
    $gender   = $_POST['gender'];
    $phone    = $_POST['phone'];
    $address  = $_POST['address'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check username exists
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {

        // DUPLICATE CHECK

        // Check username
        $checkUser = $conn->prepare("SELECT id FROM users WHERE username=?");
        $checkUser->bind_param("s", $username);
        $checkUser->execute();
        $checkUser->store_result();

        if ($checkUser->num_rows > 0) {
            $error = "Username already exists!";
        } else {

            // Check NIC or Phone
            $checkPatient = $conn->prepare("SELECT id FROM patients WHERE nic=? OR phone=?");
            $checkPatient->bind_param("ss", $nic, $phone);
            $checkPatient->execute();
            $checkPatient->store_result();

            if ($checkPatient->num_rows > 0) {
                $error = "Patient already exists (NIC or Phone duplicate)!";
            } else {

                // INSERT 

                // Insert into users
                $stmt = $conn->prepare("INSERT INTO users (username,password,role) VALUES (?,?, 'patient')");
                $stmt->bind_param("ss", $username, $password);

                if ($stmt->execute()) {

                    $user_id = $stmt->insert_id;

                    // Insert into patients
                    $stmt2 = $conn->prepare("INSERT INTO patients (user_id,fullname,nic,dob,gender,phone,address) VALUES (?,?,?,?,?,?,?)");
                    $stmt2->bind_param("issssss", $user_id, $fullname, $nic, $dob, $gender, $phone, $address);

                    if ($stmt2->execute()) {
                        $success = "Registration successful!";
                    } else {
                        $error = "Patient insert failed!";
                    }
                } else {
                    $error = "User insert failed!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>

    ```
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    ```

</head>

<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-100">

    ```
    <div class="absolute inset-0">
        <img src="./images/image_578de9f0.png" class="w-full h-full object-cover" />
    </div>

    <div class="absolute inset-0 backdrop-blur-sm bg-white/40"></div>

    <div class="relative z-10 w-full max-w-2xl p-8 rounded-2xl bg-white/70 backdrop-blur-xl shadow-2xl border border-white/40">

        <!-- Header -->
        <div class="text-center mb-6">
            <i data-lucide="user-plus" class="w-10 h-10 text-blue-600 mx-auto"></i>
            <h2 class="text-2xl font-bold text-gray-800 mt-2">Patient Registration</h2>
            <p class="text-gray-500 text-sm">Create your RuralCare account</p>
        </div>

        <!-- FORM -->
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <input type="text" name="fullname" placeholder="Full Name" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">
            <input type="text" name="nic" placeholder="NIC Number" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">
            <input type="date" name="dob" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">

            <select name="gender" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">
                <option value="">Select Gender</option>
                <option>Male</option>
                <option>Female</option>
            </select>

            <input type="text" name="phone" placeholder="Phone Number" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">
            <input type="text" name="address" placeholder="Address" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300 md:col-span-2">

            <input type="text" name="username" placeholder="Username" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">
            <input type="password" name="password" placeholder="Password" required class="p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-300">

            <div class="md:col-span-2">
                <button class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">
                    Register Patient
                </button>
            </div>

        </form>

        <div class="text-center mt-6">
            <a href="login.php?role=patient" class="text-sm text-gray-600 hover:underline">
                Already have an account? Login
            </a>
        </div>

    </div>

    <script>
        lucide.createIcons();

        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: "3000"
        };

        <?php if ($success) { ?>
            toastr.success("<?php echo $success; ?>");
            setTimeout(function() {
                window.location.href = "../login.php?role=patient";
            }, 3000);
        <?php } ?>

        <?php if ($error) { ?>
            toastr.error("<?php echo $error; ?>");
        <?php } ?>
    </script>
    ```

</body>

</html>