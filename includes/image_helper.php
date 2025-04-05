<?php
define('UPLOAD_BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
define('LISTINGS_UPLOAD_PATH', UPLOAD_BASE_PATH . DIRECTORY_SEPARATOR . 'listings');
define('PROFILES_UPLOAD_PATH', UPLOAD_BASE_PATH . DIRECTORY_SEPARATOR . 'profiles');

function getSafeImagePath($image_path, $type = 'listing') {
    if (empty($image_path)) {
        return $type === 'profile' ? '../../assets/images/default-avatar.jpg' : '../../assets/images/placeholder.jpg';
    }
    
    // Clean the filename
    $safe_filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', basename($image_path));
    $parent_id = basename(dirname($image_path));
    
    return "../../uploads/{$type}s/{$parent_id}/{$safe_filename}";
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