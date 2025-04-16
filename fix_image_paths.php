<?php
// Script to fix incorrect image paths in the database
require_once 'includes/db_connect.php';

// Get all listing images
$query = "SELECT id, image_url FROM listing_images";
$result = mysqli_query($conn, $query);

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $image_id = $row['id'];
    $current_url = $row['image_url'];
    
    // If URL contains /htdocs/, fix it
    if (strpos($current_url, '/htdocs/') !== false) {
        // Replace /htdocs/ with /Rapidstay1/
        $fixed_url = str_replace('/htdocs/', '/Rapidstay1/', $current_url);
        
        // Update the database
        $update_query = "UPDATE listing_images SET image_url = '$fixed_url' WHERE id = $image_id";
        if (mysqli_query($conn, $update_query)) {
            $count++;
            echo "Fixed image URL: $current_url -> $fixed_url<br>";
        }
    }
}

echo "<p>Fixed $count image URLs in database.</p>";
?>