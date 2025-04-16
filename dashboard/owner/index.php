<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a PG owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/index.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get owner info
$owner_id = $_SESSION['user_id'];
$owner_query = "SELECT * FROM users WHERE id = $owner_id AND user_type = 'owner'";
$owner_result = mysqli_query($conn, $owner_query);
$owner = mysqli_fetch_assoc($owner_result);

// Get dashboard statistics
// Total listings
$listings_query = "SELECT COUNT(*) as count FROM listings WHERE user_id = $owner_id";
$listings_result = mysqli_query($conn, $listings_query);
$listings_count = mysqli_fetch_assoc($listings_result)['count'];

// Active listings
$active_listings_query = "SELECT COUNT(*) as count FROM listings WHERE user_id = $owner_id AND is_active = 1";
$active_listings_result = mysqli_query($conn, $active_listings_query);
$active_listings_count = mysqli_fetch_assoc($active_listings_result)['count'];

// Pending approvals
$pending_query = "SELECT COUNT(*) as count FROM listings WHERE user_id = $owner_id AND is_verified = 0";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

// Total bookings
$bookings_query = "SELECT COUNT(*) as count FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE l.user_id = $owner_id";
$bookings_result = mysqli_query($conn, $bookings_query);
$bookings_count = mysqli_fetch_assoc($bookings_result)['count'];

// Pending bookings
$pending_bookings_query = "SELECT COUNT(*) as count FROM bookings b JOIN listings l ON b.listing_id = l.id WHERE l.user_id = $owner_id AND b.status = 'pending'";
$pending_bookings_result = mysqli_query($conn, $pending_bookings_query);
$pending_bookings_count = mysqli_fetch_assoc($pending_bookings_result)['count'];

// Recent bookings
$recent_bookings_query = "SELECT b.*, l.title as listing_title, u.name as user_name, u.phone as user_phone, u.email as user_email 
                       FROM bookings b 
                       JOIN listings l ON b.listing_id = l.id 
                       JOIN users u ON b.user_id = u.id 
                       WHERE l.user_id = $owner_id 
                       ORDER BY b.created_at DESC 
                       LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);


?>
<link rel="stylesheet" href="../../assets/css/style.css">

<style>
    #main{
        display: flex;
        position: relative;
    }
    .main-item{
        /* margin-top: 50px; */
        

        
    }
</style>
<div id="main">
    <?php 
    // Include header
    include '../includes/owner_header.php';
    ?>
    <!-- Main Content -->
    <div class=" main-item flex-1 p-8 overflow-auto">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-2">PG Owner Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($owner['name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
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
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Active PGs</p>
                        <h3 class="text-2xl font-bold"><?php echo $active_listings_count; ?></h3>
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
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Bookings</p>
                        <h3 class="text-2xl font-bold"><?php echo $bookings_count; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white border-top rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Pending Requests</p>
                        <h3 class="text-2xl font-bold"><?php echo $pending_bookings_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white border-top rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-lg font-bold mb-4">Quick Actions</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="add-listing.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-2">
                        <i class="fas fa-plus"></i>
                    </div>
                    <span class="font-medium">Add New PG</span>
                </a>
                
                <a href="booking-manage.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-2">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <span class="font-medium">Manage Bookings</span>
                </a>
                
                <a href="my-listings.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-2">
                        <i class="fas fa-home"></i>
                    </div>
                    <span class="font-medium">My PG Listings</span>
                </a>
                
                <a href="profile.php" class="flex flex-col items-center justify-center p-4 border rounded-lg hover:bg-gray-50 transition duration-300">
                    <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mb-2">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span class="font-medium">My Profile</span>
                </a>
            </div>
        </div>
        
        <!-- Recent Booking Requests -->
        <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden mb-8">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="font-bold">Recent Booking Requests</h2>
                <a href="bookings.php" class="text-green-600 hover:underline text-sm">View All</a>
            </div>
            
            <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PG Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['listing_title']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['user_phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $booking['duration']; ?> Month(s)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">View</a>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <a href="manage-booking.php?id=<?php echo $booking['id']; ?>&action=confirm" class="text-blue-600 hover:text-blue-900">Confirm</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-6 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-calendar-times fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No Recent Bookings</h3>
                    <p class="text-gray-600">
                        You don't have any recent booking requests. They will appear here when tenants book your PGs.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tips & Resources -->
        <div class="bg-white border-top rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">Tips & Resources</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3 class="font-semibold">Quality Photos</h3>
                    </div>
                    <p class="text-gray-600 text-sm">
                        High-quality photos can increase booking rates by up to 40%. Ensure good lighting and showcase all amenities.
                    </p>
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="font-semibold">Detailed Descriptions</h3>
                    </div>
                    <p class="text-gray-600 text-sm">
                        Be thorough in your PG descriptions. Mention all amenities, nearby landmarks, and house rules clearly.
                    </p>
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="font-semibold">Quick Responses</h3>
                    </div>
                    <p class="text-gray-600 text-sm">
                        Respond to booking requests promptly. Owners who respond within 24 hours have 40% higher booking rates.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
// Include footer
include '../includes/owner_footer.php';
?>

