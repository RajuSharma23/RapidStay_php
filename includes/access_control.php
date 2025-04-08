<?php
/**
 * Booking history access control functions
 * Manages who can view, edit, and delete booking records
 */

/**
 * Check if user has permission to perform an action on booking records
 * 
 * @param string $action The action (view, edit, delete)
 * @param string $userType The user type (owner, admin)
 * @param int|null $bookingOwnerId The owner ID of the booking (or null)
 * @param int|null $currentUserId The ID of current user (or null)
 * @return bool True if allowed, false if denied
 */
function hasBookingPermission($action, $userType, $bookingOwnerId = null, $currentUserId = null) {
    // Admins have full permissions
    if ($userType === 'admin') {
        return true;
    }
    
    // Owners can view their own bookings but cannot delete any booking records
    if ($userType === 'owner') {
        // Owners can only view their own bookings
        if ($action === 'view' && $bookingOwnerId === $currentUserId) {
            return true;
        }
        
        // Owners can cancel (not delete) their own pending or confirmed bookings
        if ($action === 'cancel' && $bookingOwnerId === $currentUserId) {
            return true;
        }
        
        // Owners cannot delete any booking records
        if ($action === 'delete') {
            return false;
        }
        
        // Owners can only edit limited fields on their own bookings
        if ($action === 'edit' && $bookingOwnerId === $currentUserId) {
            // Limited edit access
            return true;
        }
    }
    
    // Default: deny access
    return false;
}