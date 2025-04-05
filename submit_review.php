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
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, $_POST['comment']) : '';
    
    // Validate data
    $errors = [];
    
    if ($listing_id <= 0) {
        $errors[] = "Invalid listing ID.";
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating between 1 and 5.";
    }
    
    if (empty($comment)) {
        $errors[] = "Please enter a review comment.";
    }
    
    // Check if listing exists
    if (empty($errors)) {
        $query = "SELECT * FROM listings WHERE id = $listing_id";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 0) {
            $errors[] = "The listing does not exist.";
        }
    }
    
    // Check if user has already reviewed this listing
    if (empty($errors)) {
        $query = "SELECT id FROM reviews WHERE listing_id = $listing_id AND user_id = $user_id";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing review
            $review_id = mysqli_fetch_assoc($result)['id'];
            $query = "UPDATE reviews SET rating = $rating, comment = '$comment', updated_at = NOW() WHERE id = $review_id";
            
            if (mysqli_query($conn, $query)) {
                // Update listing rating
                updateListingRating($conn, $listing_id);
                
                // Set success message
                $_SESSION['review_success'] = "Your review has been updated successfully.";
            } else {
                $errors[] = "Failed to update your review. Please try again.";
            }
        } else {
            // Insert new review
            $query = "INSERT INTO reviews (listing_id, user_id, rating, comment, created_at) 
                      VALUES ($listing_id, $user_id, $rating, '$comment', NOW())";
            
            if (mysqli_query($conn, $query)) {
                // Update listing rating
                updateListingRating($conn, $listing_id);
                
                // Set success message
                $_SESSION['review_success'] = "Your review has been submitted successfully.";
            } else {
                $errors[] = "Failed to submit your review. Please try again.";
            }
        }
    }
    
    // If there are errors, store them in session
    if (!empty($errors)) {
        $_SESSION['review_errors'] = $errors;
    }
    
    // Redirect back to listing page
    header("Location: listing.php?id=$listing_id");
    exit();
}

// Function to update listing rating
function updateListingRating($conn, $listing_id) {
    // Calculate average rating
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE listing_id = $listing_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    $avg_rating = round($data['avg_rating'], 2);
    $count = $data['count'];
    
    // Update listing
    $query = "UPDATE listings SET rating = $avg_rating, reviews_count = $count WHERE id = $listing_id";
    mysqli_query($conn, $query);
}

// If not POST request, redirect to explore page
header("Location: explore.php");
exit();
?>

