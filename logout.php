<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set logout success message
session_start(); // Start a new session for the message
$_SESSION['logout_success'] = "You have been logged out successfully.";

// Redirect to login page
header("Location: login.php");
exit;
