<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/index.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM users WHERE id = $admin_id AND user_type = 'admin'";
$admin_result = mysqli_query($conn, $admin_query);
$admin = mysqli_fetch_assoc($admin_result);

// Get dashboard statistics
// Total users
$users_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'tenant'";
$users_result = mysqli_query($conn, $users_query);
$users_count = mysqli_fetch_assoc($users_result)['count'];

// Total PG owners
$owners_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'owner'";
$owners_result = mysqli_query($conn, $owners_query);
$owners_count = mysqli_fetch_assoc($owners_result)['count'];

// Total PG listings
$listings_query = "SELECT COUNT(*) as count FROM listings";
$listings_result = mysqli_query($conn, $listings_query);
$listings_count = mysqli_fetch_assoc($listings_result)['count'];

// Pending approvals
$pending_query = "SELECT COUNT(*) as count FROM listings WHERE is_verified = 0";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

// Total bookings
$bookings_query = "SELECT COUNT(*) as count FROM bookings";
$bookings_result = mysqli_query($conn, $bookings_query);
$bookings_count = mysqli_fetch_assoc($bookings_result)['count'];

// Recent PG listings
$recent_listings_query = "SELECT l.*, u.name as owner_name 
                         FROM listings l 
                         JOIN users u ON l.user_id = u.id 
                         ORDER BY l.created_at DESC 
                         LIMIT 5";
$recent_listings_result = mysqli_query($conn, $recent_listings_query);

// Recent bookings
$recent_bookings_query = "SELECT b.*, l.title as listing_title, u.name as user_name 
                         FROM bookings b 
                         JOIN listings l ON b.listing_id = l.id 
                         JOIN users u ON b.user_id = u.id 
                         ORDER BY b.created_at DESC 
                         LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);


?>
<link rel="stylesheet" href="../../assets/css/style.css">

<style>
    #main{
        
        display: flex;
        position: relative;
        position: absolute;
        position:inline-block;

    }
    .main-item{
        margin-top: 50px;
        /* margin-left:250px; */

        
    }
</style>
<div id="main">
    <?php
    // Include header
    include '../includes/admin_header.php';
    ?>

    
    <!-- Main Content -->
    <div class="flex-1 main-item p-8 overflow-auto">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-2">Admin Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($admin['name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex  items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Users</p>
                        <h3 class="text-2xl font-bold"><?php echo $users_count; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white  border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">PG Owners</p>
                        <h3 class="text-2xl font-bold"><?php echo $owners_count; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-home"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total PGs</p>
                        <h3 class="text-2xl font-bold"><?php echo $listings_count; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Pending Approvals</p>
                        <h3 class="text-2xl font-bold"><?php echo $pending_count; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Bookings</p>
                        <h3 class="text-2xl font-bold"><?php echo $bookings_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent PG Listings -->
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Recent PG Listings</h2>
                    <a href="pg-listings.php" class="text-blue-600 hover:underline text-sm">View All</a>
                </div>
                
                <?php if (mysqli_num_rows($recent_listings_result) > 0): ?>
                    <div class="space-y-4">
                        <?php while ($listing = mysqli_fetch_assoc($recent_listings_result)): ?>
                            <div class="flex items-center border-b pb-4 last:border-b-0 last:pb-0">
                                <div class="w-12 h-12 bg-gray-200 rounded-lg mr-4 flex-shrink-0">
                                    <?php
                                    // Get primary image
                                    $image_query = "SELECT image_url FROM listing_images WHERE listing_id = " . $listing['id'] . " AND is_primary = 1 LIMIT 1";
                                    $image_result = mysqli_query($conn, $image_query);
                                    if (mysqli_num_rows($image_result) > 0) {
                                        $image = mysqli_fetch_assoc($image_result)['image_url'];
                                        echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($listing['title']) . '" class="w-full h-full object-cover rounded-lg">';
                                    } else {
                                        echo '<div class="w-full h-full flex items-center justify-center text-gray-500"><i class="fas fa-home"></i></div>';
                                    }
                                    ?>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <span class="mr-3"><?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></span>
                                        <span>₹<?php echo number_format($listing['price']); ?>/month</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <?php if ($listing['is_verified']): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Approved</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent PG listings</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Bookings -->
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Recent Bookings</h2>
                    <a href="bookings.php" class="text-blue-600 hover:underline text-sm">View All</a>
                </div>
                
                <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                    <div class="space-y-4">
                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                            <div class="flex items-center border-b pb-4 last:border-b-0 last:pb-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-full mr-4 flex-shrink-0 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($booking['listing_title']); ?></h3>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <span class="mr-3">Booked by: <?php echo htmlspecialchars($booking['user_name']); ?></span>
                                        <span>Move-in: <?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
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
                                    <span class="px-2 py-1 <?php echo $status_class; ?> rounded-full text-xs">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent bookings</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/admin_footer.php';
?>

