<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// CHECK LOGIN

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../login.php?role=moh_officer");
    exit();
}

// CHECK MOH OFFICER ROLE
if ($_SESSION['role'] !== 'moh_officer') {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: ../staff/dashboard.php");
    } else {
        session_unset();
        session_destroy();
        header("Location: ../login.php");
    }

    exit();
}
