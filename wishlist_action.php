<?php
// Start session for user authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get listing ID and action
    $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Validate listing ID
    if ($listing_id <= 0) {
        // Invalid listing ID
        header("Location: explore.php");
        exit();
    }
    
    // Check if listing exists
    $query = "SELECT id FROM listings WHERE id = $listing_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 0) {
        // Listing doesn't exist
        header("Location: explore.php");
        exit();
    }
    
    // Process action
    if ($action === 'add') {
        // Add to wishlist
        $query = "INSERT IGNORE INTO wishlist (user_id, listing_id) VALUES ($user_id, $listing_id)";
        mysqli_query($conn, $query);
    } elseif ($action === 'remove') {
        // Remove from wishlist
        $query = "DELETE FROM wishlist WHERE user_id = $user_id AND listing_id = $listing_id";
        mysqli_query($conn, $query);
    }
    
    // Check if request is AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'action' => $action]);
        exit();
    }
    
    // Redirect back to referring page
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'explore.php';
    header("Location: $redirect");
    exit();
}

// If not POST request, redirect to explore page
header("Location: explore.php");
exit();
?>

