<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/index.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';
require_once '../../includes/image_helper.php';

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id AND user_type = 'tenant'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get dashboard statistics
// Wishlist count
$wishlist_query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id";
$wishlist_result = mysqli_query($conn, $wishlist_query);
$wishlist_count = mysqli_fetch_assoc($wishlist_result)['count'];

// Active bookings
$active_bookings_query = "SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id AND status IN ('pending', 'confirmed')";
$active_bookings_result = mysqli_query($conn, $active_bookings_query);
$active_bookings_count = mysqli_fetch_assoc($active_bookings_result)['count'];

// Recent bookings
$recent_bookings_query = "SELECT b.*, l.title, l.locality, l.city, l.price, 
                        (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                        FROM bookings b
                        JOIN listings l ON b.listing_id = l.id
                        WHERE b.user_id = $user_id
                        ORDER BY b.created_at DESC
                        LIMIT 3";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);

// Recommended PGs
$recommended_query = "SELECT l.*, 
                    li.image_url as primary_image,
                    COUNT(r.id) as reviews_count,
                    COALESCE(AVG(r.rating), 0) as rating
                    FROM listings l
                    LEFT JOIN listing_images li ON l.id = li.listing_id AND li.is_primary = 1
                    LEFT JOIN reviews r ON l.id = r.listing_id
                    WHERE l.is_verified = 1 AND l.is_active = 1
                    GROUP BY l.id
                    ORDER BY l.rating DESC, l.created_at DESC
                    LIMIT 3";
$recommended_result = mysqli_query($conn, $recommended_query);

// Replace the direct image path with a proper structure
$upload_directory = '../../uploads/listings/';

// Include header
include '../includes/user_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="main"></div>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-2">User Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-heart"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Wishlist</p>
                    <h3 class="text-2xl font-bold"><?php echo $wishlist_count; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Active Bookings</p>
                    <h3 class="text-2xl font-bold"><?php echo $active_bookings_count; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Find Roommate</p>
                    <a href="roommates.php" class="text-blue-600 hover:underline">Browse Now</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold mb-4">Quick Actions</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="../../explore.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-2">
                    <i class="fas fa-search"></i>
                </div>
                <span class="font-medium">Find PG</span>
            </a>
            
            <a href="wishlist.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 mb-2">
                    <i class="fas fa-heart"></i>
                </div>
                <span class="font-medium">My Wishlist</span>
            </a>
            
            <a href="bookings.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-2">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span class="font-medium">My Bookings</span>
            </a>
            
            <a href="profile.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-2">
                    <i class="fas fa-user-circle"></i>
                </div>
                <span class="font-medium">My Profile</span>
            </a>
        </div>
    </div>
    
    <!-- Recent Bookings -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="font-bold">Recent Bookings</h2>
            <a href="bookings.php" class="text-blue-600 hover:underline text-sm">View All</a>
        </div>
        
        <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                        <div class="border rounded-lg overflow-hidden">
                            <div class="relative">
                                <img 
                                    src="<?php echo getSafeImagePath($booking['primary_image']); ?>" 
                                    alt="<?php echo htmlspecialchars($booking['title']); ?>" 
                                    class="w-full h-40 object-cover"
                                >
                                
                                <?php
                                $status_class = '';
                                switch ($booking['status']) {
                                    case 'pending':
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'confirmed':
                                        $status_class = 'bg-green-100 text-green-800';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-red-100 text-red-800';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        break;
                                }
                                ?>
                                <div class="absolute top-3 right-3">
                                    <span class="px-2 py-1 <?php echo $status_class; ?> rounded-full text-xs font-medium">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($booking['title']); ?></h3>
                                <p class="text-gray-600 mb-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <?php echo htmlspecialchars($booking['locality'] . ', ' . $booking['city']); ?>
                                </p>
                                
                                <div class="flex justify-between items-center mb-2">
                                    <div class="text-sm text-gray-600">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?>
                                    </div>
                                    <div class="font-bold text-blue-600">
                                        ₹<?php echo number_format($booking['price']); ?>/month
                                    </div>
                                </div>
                                
                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-md">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="p-6 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-calendar-times fa-3x"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Bookings Yet</h3>
                <p class="text-gray-600 mb-4">
                    You haven't made any bookings yet. Start exploring PGs to find your perfect stay!
                </p>
                <a href="../../explore.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                    Explore PGs
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recommended PGs -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="font-bold">Recommended PGs</h2>
            <a href="../../explore.php" class="text-blue-600 hover:underline text-sm">View All</a>
        </div>
        
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php while ($listing = mysqli_fetch_assoc($recommended_result)): ?>
                    <div class="border rounded-lg overflow-hidden">
                        <div class="relative">
                            <img 
                                src="<?php echo getSafeImagePath($listing['primary_image'], 'listing'); ?>" 
                                alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                class="w-full h-48 object-cover"
                                onerror="this.src='../../assets/images/placeholder.jpg';"
                            >
                            
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-1 bg-white text-xs font-medium rounded-full">
                                    <?php echo htmlspecialchars(ucfirst($listing['type'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($listing['title']); ?></h3>
                            <p class="text-gray-600 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?>
                            </p>
                            
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400 mr-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= round($listing['rating'])): ?>
                                            <i class="fas fa-star text-xs"></i>
                                        <?php elseif ($i - 0.5 <= $listing['rating']): ?>
                                            <i class="fas fa-star-half-alt text-xs"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-xs"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-sm text-gray-500">(<?php echo $listing['reviews_count']; ?>)</span>
                            </div>
                            
                            <div class="flex justify-between items-center mb-4">
                                <div class="font-bold text-blue-600">
                                    ₹<?php echo number_format($listing['price']); ?>/month
                                </div>
                                <div class="text-sm text-gray-600">
                                    Available: <?php echo date('M d', strtotime($listing['available_from'])); ?>
                                </div>
                            </div>
                            
                            <a href="../../listing.php?id=<?php echo $listing['id']; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-md">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/user_footer.php';
?>
</body>
</html>
