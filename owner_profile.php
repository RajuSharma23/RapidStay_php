<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'includes/db_connect.php';

// Check if owner ID is provided in URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to error page or show error message
    header("Location: error.php?message=No owner ID specified");
    exit();
}

$owner_id = intval($_GET['id']);

// Fetch owner information from database
try {
    $stmt = $conn->prepare("SELECT id, username, name as full_name, email, bio, profile_image, 
                           created_at as date_joined, location, website, social_media_links 
                           FROM users WHERE id = ? AND user_type = 'owner'");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Owner not found
        header("Location: error.php?message=Owner not found");
        exit();
    }
    
    $owner = $result->fetch_assoc();
    
    // Fetch owner's listings or items
    $listings_stmt = $conn->prepare("SELECT id, title, description, 
                                    (SELECT image_url FROM listing_images WHERE listing_id = listings.id LIMIT 1) as image, 
                                    price, created_at 
                                    FROM listings WHERE user_id = ? ORDER BY created_at DESC");
    $listings_stmt->bind_param("i", $owner_id);
    $listings_stmt->execute();
    $listings_result = $listings_stmt->get_result();
    $listings = [];
    
    while ($row = $listings_result->fetch_assoc()) {
        $listings[] = $row;
    }
    
    // Check if current user is following this owner
    $is_following = false;
    if (isset($_SESSION['user_id'])) {
        $follow_stmt = $conn->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
        $follow_stmt->bind_param("ii", $_SESSION['user_id'], $owner_id);
        $follow_stmt->execute();
        $follow_result = $follow_stmt->get_result();
        $is_following = ($follow_result->num_rows > 0);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Database error: " . $e->getMessage());
    header("Location: error.php?message=Database error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($owner['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-image">
                <?php if (!empty($owner['profile_image'])): ?>
                    <img src="uploads/profiles/<?php echo htmlspecialchars($owner['profile_image']); ?>" alt="Profile Image">
                <?php else: ?>
                    <img src="images/default-profile.png" alt="Default Profile Image">
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($owner['full_name']); ?> 
                    <span class="username">@<?php echo htmlspecialchars($owner['username']); ?></span>
                </h1>
                
                <?php if (!empty($owner['location'])): ?>
                    <p class="location">
                        <i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($owner['location']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($owner['website'])): ?>
                    <p class="website">
                        <i class="fa fa-link"></i> 
                        <a href="<?php echo htmlspecialchars($owner['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($owner['website']); ?>
                        </a>
                    </p>
                <?php endif; ?>
                
                <p class="joined-date">Member since <?php echo date('F Y', strtotime($owner['date_joined'])); ?></p>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $owner_id): ?>
                    <div class="action-buttons">
                        <?php if ($is_following): ?>
                            <form action="unfollow.php" method="post">
                                <input type="hidden" name="owner_id" value="<?php echo $owner_id; ?>">
                                <button type="submit" class="btn btn-secondary">Unfollow</button>
                            </form>
                        <?php else: ?>
                            <form action="follow.php" method="post">
                                <input type="hidden" name="owner_id" value="<?php echo $owner_id; ?>">
                                <button type="submit" class="btn btn-primary">Follow</button>
                            </form>
                        <?php endif; ?>
                        <a href="message.php?to=<?php echo $owner_id; ?>" class="btn btn-outline">Message</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-bio">
            <?php if (!empty($owner['bio'])): ?>
                <h2>About</h2>
                <p><?php echo nl2br(htmlspecialchars($owner['bio'])); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($owner['social_media_links'])): ?>
            <div class="social-links">
                <h2>Connect</h2>
                <?php 
                    $social_links = json_decode($owner['social_media_links'], true);
                    if ($social_links && is_array($social_links)):
                        foreach ($social_links as $platform => $url):
                            if (!empty($url)):
                ?>
                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="social-icon <?php echo strtolower($platform); ?>">
                        <i class="fa fa-<?php echo strtolower($platform); ?>"></i> <?php echo htmlspecialchars($platform); ?>
                    </a>
                <?php 
                            endif;
                        endforeach;
                    endif;
                ?>
            </div>
        <?php endif; ?>
        
        <div class="owner-listings">
            <h2>Listings</h2>
            
            <?php if (empty($listings)): ?>
                <p class="no-listings">This owner has no listings yet.</p>
            <?php else: ?>
                <div class="listings-grid">
                    <?php foreach ($listings as $listing): ?>
                        <div class="listing-card">
                            <a href="listing.php?id=<?php echo $listing['id']; ?>">
                                <?php if (!empty($listing['image'])): ?>
                                    <img src="uploads/listings/<?php echo htmlspecialchars($listing['image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                <?php else: ?>
                                    <img src="images/default-listing.png" alt="Default Listing Image">
                                <?php endif; ?>
                                
                                <div class="listing-details">
                                    <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                                    <p class="price">$<?php echo number_format($listing['price'], 2); ?></p>
                                    <p class="date"><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($listings) > 10): ?>
                    <div class="view-more">
                        <a href="owner_listings.php?id=<?php echo $owner_id; ?>" class="btn btn-secondary">View All Listings</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>