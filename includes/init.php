// Add this to your initialization file
define('UPLOAD_DIR', __DIR__ . '/../uploads/listings/');

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}