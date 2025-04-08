<?php
// Start session
session_start();

// Include database connection and access control
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../../login.php");
    exit();
}

// Get booking ID from URL parameter
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    // Invalid ID provided
    header("Location: booking-history.php?error=invalid_id");
    exit();
}

// Check if booking exists and get owner ID
$checkSql = "SELECT listing_id, status FROM bookings WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $bookingId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    // Booking not found
    header("Location: booking-history.php?error=not_found");
    exit();
}

$bookingData = $checkResult->fetch_assoc();

// Check if owner has permission to cancel this booking
if (!hasBookingPermission('cancel', $_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['user_id'])) {
    // Log unauthorized access attempt
    error_log("Unauthorized cancel attempt by user {$_SESSION['user_id']} for booking $bookingId");
    
    // Redirect with error
    header("Location: booking-history.php?error=unauthorized");
    exit();
}

// Check if booking is in a cancellable state
if ($bookingData['status'] !== 'pending' && $bookingData['status'] !== 'confirmed') {
    // Cannot cancel a booking that's already completed or cancelled
    header("Location: booking-history.php?error=invalid_status");
    exit();
}

// Update booking status to cancelled
$updateSql = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $bookingId);

if ($updateStmt->execute()) {
    // Successfully cancelled
    header("Location: booking-history.php?success=cancelled");
    exit();
} else {
    // Error cancelling
    header("Location: booking-history.php?error=cancel_failed");
    exit();
}

// Close connection
$checkStmt->close();
$updateStmt->close();
$conn->close();
?>