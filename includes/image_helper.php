<?php
define('UPLOAD_BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
define('LISTINGS_UPLOAD_PATH', UPLOAD_BASE_PATH . DIRECTORY_SEPARATOR . 'listings');
define('PROFILES_UPLOAD_PATH', UPLOAD_BASE_PATH . DIRECTORY_SEPARATOR . 'profiles');

/**
 * Get a safe image path with proper fallbacks
 * 
 * @param string $image_path The original image path
 * @param string $type The type of image (user, listing, etc.)
 * @return string A valid image path
 */
function getSafeImagePath($image_path, $type = 'listing') {
    // Base directory for uploads
    $base_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Rapidstay1/uploads/';
    
    // Default placeholder images by type
    $placeholders = [
        'listing' => '/Rapidstay1/assets/images/placeholder-listing.jpg',
        'user' => '/Rapidstay1/assets/images/default-user.png',
        'profile' => '/Rapidstay1/assets/images/default-user.png',
        'roommate' => '/Rapidstay1/assets/images/default-roommate.jpg',
    ];
    
    // If image path is empty, return placeholder
    if (empty($image_path)) {
        return $placeholders[$type] ?? $placeholders['listing'];
    }
    
    // If path is a URL, return it directly
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    // Handle relative paths that might be stored in DB
    if (strpos($image_path, '/') === 0) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
    } else {
        $full_path = $base_upload_dir . $type . 's/' . $image_path;
    }
    
    // Check if file exists
    if (file_exists($full_path)) {
        // Convert to web path (from server path)
        $web_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $full_path);
        return $web_path;
    }
    
    // Return appropriate placeholder if file doesn't exist
    return $placeholders[$type] ?? $placeholders['listing'];
}

// Helper function to ensure upload directories exist
function ensureUploadDirectories() {
    $directories = [UPLOAD_BASE_PATH, LISTINGS_UPLOAD_PATH, PROFILES_UPLOAD_PATH];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $errors = [];
    
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = 'Invalid file type. Only JPG, PNG and GIF are allowed.';
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = 'File size too large. Maximum size is 5MB.';
    }
    
    return $errors;
}

function generateUniqueFilename($original_name, $prefix = '') {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
}