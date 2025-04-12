<?php
// Create all necessary directories if they don't exist
$upload_dirs = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/listings',
    __DIR__ . '/../uploads/profiles'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}