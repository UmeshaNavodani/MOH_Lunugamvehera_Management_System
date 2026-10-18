<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ========================================
// CHECK LOGIN
// ========================================

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {

    header("Location: ../login.php?role=staff");
    exit();
}


// ========================================
// CHECK STAFF ROLE
// ========================================

if ($_SESSION['role'] !== 'staff') {

    // Admin trying to access staff panel
    if ($_SESSION['role'] === 'admin') {

        header("Location: ../admin/dashboard.php");
        exit();
    }

    // MOH Officer trying to access staff panel
    elseif ($_SESSION['role'] === 'moh_officer') {

        header("Location: ../moh/dashboard.php");
        exit();
    }

    // Unknown/invalid role
    else {

        session_unset();
        session_destroy();

        header("Location: ../login.php?role=staff");
        exit();
    }
}


// ========================================
// STAFF IS AUTHORIZED
// ========================================

// Get staff information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$designation = $_SESSION['designation'] ?? '';
