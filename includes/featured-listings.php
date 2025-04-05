<?php
// Database connection
require_once 'includes/db_connect.php';

// Fetch featured listings
$sql = "SELECT * FROM listings WHERE is_featured = 1 LIMIT 3";
$result = mysqli_query($conn, $sql);
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
  <?php
  if (mysqli_num_rows($result) > 0) {
    while ($listing = mysqli_fetch_assoc($result)) {
      include 'listing-card.php';
    }
  } else {
    // If no featured listings in database, show placeholders
    $placeholders = [
      [
        'id' => 1,
        'title' => 'Modern Studio Apartment',
        'location' => 'Downtown, New York',
        'price' => 1200,
        'type' => 'Room',
        'rating' => 4.8,
        'reviews_count' => 124,
        'image_url' => 'assets/images/placeholder.jpg',
      ],
      [
        'id' => 2,
        'title' => 'Shared 2BHK with Balcony',
        'location' => 'Silicon Valley, CA',
        'price' => 850,
        'type' => 'Roommate',
        'rating' => 4.6,
        'reviews_count' => 98,
        'image_url' => 'assets/images/placeholder.jpg',
      ],
      [
        'id' => 3,
        'title' => 'Luxury PG Accommodation',
        'location' => 'Central London, UK',
        'price' => 950,
        'type' => 'PG',
        'rating' => 4.9,
        'reviews_count' => 156,
        'image_url' => 'assets/images/placeholder.jpg',
      ],
    ];
    
    foreach ($placeholders as $listing) {
      include 'listing-card.php';
    }
  }
  ?>
</div>

