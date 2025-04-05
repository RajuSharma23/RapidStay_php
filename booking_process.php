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
    // Get form data
    $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
    $move_in_date = isset($_POST['move_in_date']) ? $_POST['move_in_date'] : '';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    $occupants = isset($_POST['occupants']) ? intval($_POST['occupants']) : 1;
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, $_POST['message']) : '';
    
    // Validate data
    $errors = [];
    
    if ($listing_id <= 0) {
        $errors[] = "Invalid listing ID.";
    }
    
    if (empty($move_in_date) || !strtotime($move_in_date)) {
        $errors[] = "Please select a valid move-in date.";
    }
    
    if ($duration <= 0) {
        $errors[] = "Please select a valid duration.";
    }
    
    if ($occupants <= 0) {
        $errors[] = "Please select a valid number of occupants.";
    }
    
    // Check if listing exists and is available
    if (empty($errors)) {
        $query = "SELECT * FROM listings WHERE id = $listing_id AND is_active = 1";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 0) {
            $errors[] = "The listing is no longer available.";
        } else {
            $listing = mysqli_fetch_assoc($result);
            
            // Check if move-in date is valid
            $available_from = strtotime($listing['available_from']);
            $selected_date = strtotime($move_in_date);
            
            if ($selected_date < $available_from) {
                $errors[] = "The selected move-in date is before the listing's availability date.";
            }
            
            // Check if occupants count is valid
            if ($occupants > $listing['max_occupants']) {
                $errors[] = "The number of occupants exceeds the maximum allowed for this listing.";
            }
            
            // Calculate total amount
            $total_amount = $listing['price'] * $duration;
        }
    }
    
    // Process booking if no errors
    if (empty($errors)) {
        // Insert booking
        $query = "INSERT INTO bookings (listing_id, user_id, move_in_date, duration, occupants, total_amount, message, status, created_at) 
                  VALUES ($listing_id, $user_id, '$move_in_date', $duration, $occupants, $total_amount, '$message', 'pending', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $booking_id = mysqli_insert_id($conn);
            
            // Send notification to listing owner
            $owner_id = $listing['user_id'];
            $notification_message = "New booking request for your listing: " . $listing['title'];
            
            $query = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                      VALUES ($user_id, $owner_id, $listing_id, '$notification_message', NOW())";
            mysqli_query($conn, $query);
            
            // Redirect to booking confirmation page
            header("Location: booking_confirmation.php?id=$booking_id");
            exit();
        } else {
            $errors[] = "Failed to process your booking. Please try again.";
        }
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        header("Location: listing.php?id=$listing_id");
        exit();
    }
}

// If not POST request, redirect to explore page
header("Location: explore.php");
exit();
?>

