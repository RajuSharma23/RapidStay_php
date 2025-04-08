<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/bookings.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get active bookings
$active_query = "SELECT b.*, l.title, l.locality, l.city, l.price, 
                (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                WHERE b.user_id = $user_id AND b.status IN ('pending', 'confirmed')
                ORDER BY b.created_at DESC";
$active_result = mysqli_query($conn, $active_query);

// Get past bookings
$past_query = "SELECT b.*, l.title, l.locality, l.city, l.price, 
              (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
              FROM bookings b
              JOIN listings l ON b.listing_id = l.id
              WHERE b.user_id = $user_id AND b.status IN ('completed', 'cancelled')
              ORDER BY b.created_at DESC";
$past_result = mysqli_query($conn, $past_query);

// Include header
include '../includes/user_header.php';
?>
<style>
    .main{
        margin-left:250px;
    }
</style>
<!-- Main Content -->
<div class=" main flex-1 p-8 overflow-auto">
    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold">My Bookings</h1>
        <a href="../../explore.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-search mr-2"></i> Find New PG
        </a>
    </div>
    
    <!-- Active Bookings -->
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">Active Bookings</h2>
        
        <?php if (mysqli_num_rows($active_result) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($booking = mysqli_fetch_assoc($active_result)): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="relative">
                            <img 
                                src="<?php echo !empty($booking['primary_image']) ? htmlspecialchars($booking['primary_image']) : '../../assets/images/placeholder.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($booking['title']); ?>" 
                                class="w-full h-48 object-cover"
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
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo $booking['duration']; ?> Month(s)
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mb-4">
                                <div class="font-bold text-blue-600">
                                    ₹<?php echo number_format($booking['price']); ?>/month
                                </div>
                                <div class="text-sm">
                                    <?php
                                    $payment_class = '';
                                    switch ($booking['payment_status']) {
                                        case 'pending':
                                            $payment_class = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'partial':
                                            $payment_class = 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'completed':
                                            $payment_class = 'bg-green-100 text-green-800';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 <?php echo $payment_class; ?> rounded-full text-xs">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-md">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-calendar-times fa-4x"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Active Bookings</h3>
                <p class="text-gray-600 mb-6">
                    You don't have any active bookings at the moment. Explore our listings to find your perfect PG!
                </p>
                <a href="../../explore.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                    Find a PG
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Past Bookings -->
    <div>
        <h2 class="text-xl font-bold mb-4">Booking History</h2>
        
        <?php if (mysqli_num_rows($past_result) > 0): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PG Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stay Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($booking = mysqli_fetch_assoc($past_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($booking['primary_image'])): ?>
                                                    <img class="h-10 w-10 rounded-md object-cover" src="<?php echo htmlspecialchars($booking['primary_image']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded-md bg-blue-100 flex items-center justify-center text-blue-600">
                                                        <i class="fas fa-home"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['title']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['locality'] . ', ' . $booking['city']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($booking['move_in_date'] . " +" . $booking['duration'] . " months")); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ₹<?php echo number_format($booking['total_amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = '';
                                        switch ($booking['status']) {
                                            case 'completed':
                                                $status_class = 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900">View Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-history fa-4x"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Booking History</h3>
                <p class="text-gray-600">
                    You don't have any past bookings. Your booking history will appear here once you complete or cancel a booking.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../includes/user_footer.php';
?>

