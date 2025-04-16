<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['toggle_2fa'])) {
    // Get the enabled status from the form
    $enable = isset($_POST['enable_2fa']) ? 1 : 0;
    
    // Check if two_factor_enabled column exists
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'two_factor_enabled'");
    if(mysqli_num_rows($column_check) == 0) {
        // Column doesn't exist, add it
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0");
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(64) DEFAULT NULL");
    }
    
    // Update the 2FA status in the database
    $update_query = "UPDATE users SET two_factor_enabled = $enable WHERE id = $user_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['profile_message'] = $enable ? "Two-factor authentication enabled." : "Two-factor authentication disabled.";
    } else {
        $_SESSION['profile_message'] = "Error updating two-factor authentication status.";
    }
}

// Redirect back to profile page
header("Location: profile.php");
exit();
?>