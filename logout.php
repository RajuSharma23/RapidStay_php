<?php
// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
    
    // Remove token from database
    if (isset($_COOKIE['remember_user'])) {
        require_once 'includes/db_connect.php';
        $user_id = intval($_COOKIE['remember_user']);
        $query = "DELETE FROM remember_tokens WHERE user_id = $user_id";
        mysqli_query($conn, $query);
    }
}

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?>

