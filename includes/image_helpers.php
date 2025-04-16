<?php
/**
 * Helper functions for image handling
 */

/**
 * Formats image URLs consistently across the site
 * 
 * @param string $path The image path from database
 * @param string $default Default image if path is empty
 * @return string Properly formatted URL
 */
function getImageUrl($path, $default = 'assets/images/placeholder.jpg') {
    // Return default image if path is empty
    if (empty($path)) {
        return 'https://localhost/rapidstay1/' . $default;
    }
    
    // If it's already a full URL, return as is
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // For listing images with specific format
    if (preg_match('/listings\/(\d+)\/([^\/]+)$/', $path, $matches)) {
        $listing_id = $matches[1];
        $filename = $matches[2];
        return 'https://localhost/rapidstay1/uploads/listings/' . $listing_id . '/' . $filename;
    }
    
    // For uploads with standard path format
    if (strpos($path, 'uploads/') !== false) {
        // Extract the path after uploads/
        $path_parts = explode('uploads/', $path);
        if (count($path_parts) > 1) {
            return 'https://localhost/rapidstay1/uploads/' . $path_parts[1];
        }
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // Return full URL
    return 'https://localhost/rapidstay1/' . $path;
}