<?php
// Script to create default images for the application

// 1. Create directory structure if it doesn't exist
$dirs = [
    'assets',
    'assets/images',
    'assets/css',
    'assets/js',
    'uploads',
    'uploads/users',
    'uploads/listings'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir<br>";
    }
}

// 2. Create default user profile image if it doesn't exist
$default_user_image = 'assets/images/default-user.png';

if (!file_exists($default_user_image)) {
    // Use GD to create a simple default user avatar
    $size = 200;
    $img = imagecreatetruecolor($size, $size);
    
    // Colors
    $bg = imagecolorallocate($img, 240, 240, 240); // Light gray
    $fg = imagecolorallocate($img, 180, 180, 180); // Darker gray
    
    // Fill background
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);
    
    // Draw a simple avatar shape (circle for head)
    $center = $size/2;
    $radius = $size/4;
    imagefilledellipse($img, $center, $center - $radius/2, $radius*1.8, $radius*1.8, $fg);
    
    // Draw body
    imagefilledellipse($img, $center, $center + $radius*1.5, $radius*2.2, $radius*2.8, $fg);
    
    // Save the image
    imagepng($img, $default_user_image);
    imagedestroy($img);
    
    echo "Created default user image at: $default_user_image<br>";
}

// 3. Create default placeholder image if it doesn't exist
$placeholder_image = 'assets/images/placeholder.jpg';

if (!file_exists($placeholder_image)) {
    // Create a placeholder image
    $width = 800;
    $height = 600;
    $img = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg = imagecolorallocate($img, 240, 240, 240); // Light gray
    $text = imagecolorallocate($img, 150, 150, 150); // Medium gray
    
    // Fill background
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);
    
    // Add text
    $font_size = 5; // Built-in font size (1-5)
    $text_string = "No Image Available";
    
    // Calculate position to center the text
    $text_width = imagefontwidth($font_size) * strlen($text_string);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($img, $font_size, $x, $y, $text_string, $text);
    
    // Save the image
    imagejpeg($img, $placeholder_image, 90); // 90% quality
    imagedestroy($img);
    
    echo "Created placeholder image at: $placeholder_image<br>";
}

echo "<p>All default images have been created successfully!</p>";
echo "<p><a href='index.php'>Return to homepage</a></p>";
?>