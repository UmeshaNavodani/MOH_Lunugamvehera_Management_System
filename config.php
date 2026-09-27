<?php
// ---------------- DB CONNECTION ----------------
$conn = new mysqli("localhost", "root", "", "moh_system_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
