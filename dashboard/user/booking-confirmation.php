<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/booking-confirmation.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get booking ID
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid ID
if ($booking_id <= 0) {
    header("Location: bookings.php");
    exit();
}

// Get booking details
$booking_query = "SELECT b.*, l.title, l.locality, l.city, l.price, l.security_deposit, u.name as owner_name, u.phone as owner_phone, u.email as owner_email,
                (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM bookings b
                JOIN listings l ON b.listing_id = l.id
                JOIN users u ON l.user_id = u.id
                WHERE b.id = $booking_id AND b.user_id = $user_id";
$booking_result = mysqli_query($conn, $booking_query);

// Check if booking exists
if (mysqli_num_rows($booking_result) == 0) {
    header("Location: bookings.php");
    exit();
}

$booking = mysqli_fetch_assoc($booking_result);

// Get payment details
$payment_query = "SELECT * FROM payments WHERE booking_id = $booking_id ORDER BY created_at DESC LIMIT 1";
$payment_result = mysqli_query($conn, $payment_query);
$payment = mysqli_fetch_assoc($payment_result);

// Include header
include '../includes/user_header.php';
?>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Booking Confirmation</h1>
            <p class="text-gray-600">Your booking request has been submitted successfully</p>
        </div>
        <a href="bookings.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-list mr-2"></i> View All Bookings
        </a>
    </div>
    
    <!-- Success Message -->
    <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 flex items-center">
        <div class="mr-4 text-green-500">
            <i class="fas fa-check-circle text-3xl"></i>
        </div>
        <div>
            <h3 class="font-bold">Booking Request Submitted!</h3>
            <p>Your booking request has been sent to the PG owner. You will be notified once it's confirmed.</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Booking Details -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Booking Details</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex flex-col md:flex-row mb-6">
                        <div class="md:w-1/3 mb-4 md:mb-0">
                            <img 
                                src="<?php echo !empty($booking['primary_image']) ? htmlspecialchars($booking['primary_image']) : '../../assets/images/placeholder.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($booking['title']); ?>" 
                                class="w-full h-40 object-cover rounded-lg"
                            >
                        </div>
                        <div class="md:w-2/3 md:pl-6">
                            <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($booking['title']); ?></h3>
                            <p class="text-gray-600 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars($booking['locality'] . ', ' . $booking['city']); ?>
                            </p>
                            
                            <div class="flex flex-wrap mt-4">
                                <div class="w-full md:w-1/2 mb-4">
                                    <span class="text-gray-600">Booking ID:</span>
                                    <span class="font-semibold ml-2">#<?php echo $booking_id; ?></span>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <span class="text-gray-600">Booking Date:</span>
                                    <span class="font-semibold ml-2"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></span>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <span class="text-gray-600">Status:</span>
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
                                    <span class="ml-2 px-2 py-1 <?php echo $status_class; ?> rounded-full text-xs font-semibold">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                <div class="w-full md:w-1/2 mb-4">
                                    <span class="text-gray-600">Payment Status:</span>
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
                                    <span class="ml-2 px-2 py-1 <?php echo $payment_class; ?> rounded-full text-xs font-semibold">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6">
                        <h3 class="font-semibold mb-4">Stay Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-600">Check-in Date:</span>
                                <span class="font-semibold ml-2"><?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-semibold ml-2"><?php echo $booking['duration']; ?> Month(s)</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Number of Occupants:</span>
                                <span class="font-semibold ml-2"><?php echo $booking['occupants']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Monthly Rent:</span>
                                <span class="font-semibold ml-2">₹<?php echo number_format($booking['price']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6 mt-6">
                        <h3 class="font-semibold mb-4">Payment Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-semibold ml-2"><?php echo ucfirst($payment['payment_method']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Transaction ID:</span>
                                <span class="font-semibold ml-2"><?php echo $payment['transaction_id']; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">First Payment Amount:</span>
                                <span class="font-semibold ml-2">₹<?php echo number_format($payment['amount']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Total Contract Value:</span>
                                <span class="font-semibold ml-2">₹<?php echo number_format($booking['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking['message'])): ?>
                        <div class="border-t pt-6 mt-6">
                            <h3 class="font-semibold mb-2">Your Message to Owner</h3>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Next Steps</h2>
                </div>
                
                <div class="p-6">
                    <div class="space-y-6">
                        <div class="flex">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-semibold">Await Owner Confirmation</h3>
                                <p class="text-gray-600">The PG owner will review your booking request and confirm your booking.</p>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-semibold">Owner Contact</h3>
                                <p class="text-gray-600">The owner may contact you to discuss your booking details.</p>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-semibold">Payment</h3>
                                <p class="text-gray-600">Make the payment as per the agreed method at the time of check-in.</p>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-semibold">Move In</h3>
                                <p class="text-gray-600">Move into your new PG on the scheduled check-in date.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Owner Contact -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-24">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Owner Contact</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-4">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($booking['owner_name']); ?></h3>
                            <p class="text-gray-600">PG Owner</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <span class="text-gray-600">Phone:</span>
                            <a href="tel:<?php echo htmlspecialchars($booking['owner_phone']); ?>" class="font-semibold ml-2 text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($booking['owner_phone']); ?>
                            </a>
                        </div>
                        <div>
                            <span class="text-gray-600">Email:</span>
                            <a href="mailto:<?php echo htmlspecialchars($booking['owner_email']); ?>" class="font-semibold ml-2 text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($booking['owner_email']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t">
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md mb-3">
                            <i class="fas fa-phone-alt mr-2"></i> Call Owner
                        </button>
                        <button class="w-full border border-blue-600 text-blue-600 hover:bg-blue-50 py-2 rounded-md">
                            <i class="fas fa-envelope mr-2"></i> Message Owner
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/user_footer.php';
?>

