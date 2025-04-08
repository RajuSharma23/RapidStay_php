<?php
// Start session
session_start();

// Include database connection and access control
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page or show error
    header("Location: ../../login.php?redirect=dashboard/admin/manage-bookings.php");
    exit();
}

// Get booking ID from URL parameter
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    // Invalid ID provided
    header("Location: manage-bookings.php?error=invalid_id");
    exit();
}

// Verify admin has permission to delete
if (!hasBookingPermission('delete', $_SESSION['user_type'])) {
    // Log unauthorized access attempt
    error_log("Unauthorized delete attempt by user {$_SESSION['user_id']} for booking $bookingId");
    
    // Redirect with error
    header("Location: manage-bookings.php?error=unauthorized");
    exit();
}

// Delete the booking
$deleteSql = "DELETE FROM bookings WHERE id = ?";
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param("i", $bookingId);

if ($stmt->execute()) {
    // Successfully deleted
    header("Location: manage-bookings.php?success=deleted");
    exit();
} else {
    // Error deleting
    header("Location: manage-bookings.php?error=delete_failed");
    exit();
}

// Close connection
$stmt->close();
$conn->close();
?>