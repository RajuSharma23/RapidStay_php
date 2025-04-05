<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/payment.php");
    exit();
}

// Check if booking data exists in session
if (!isset($_SESSION['booking_data'])) {
    // Redirect to explore page
    header("Location: ../../explore.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID and booking data
$user_id = $_SESSION['user_id'];
$booking_data = $_SESSION['booking_data'];
$listing_id = $booking_data['listing_id'];

// Get listing details
$listing_query = "SELECT l.*, u.name as owner_name 
                FROM listings l 
                JOIN users u ON l.user_id = u.id 
                WHERE l.id = $listing_id AND l.is_active = 1";
$listing_result = mysqli_query($conn, $listing_query);

// Check if listing exists and is available
if (mysqli_num_rows($listing_result) == 0) {
    header("Location: ../../explore.php");
    exit();
}

$listing = mysqli_fetch_assoc($listing_result);

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get payment method
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Insert booking
    $name = $booking_data['name'];
    $email = $booking_data['email'];
    $phone = $booking_data['phone'];
    $address = $booking_data['address'];
    $move_in_date = $booking_data['move_in_date'];
    $duration = $booking_data['duration'];
    $occupants = $booking_data['occupants'];
    $message = $booking_data['message'];
    $total_amount = $booking_data['total_amount'];
    
    $booking_query = "INSERT INTO bookings (listing_id, user_id, move_in_date, duration, occupants, total_amount, message, status, payment_status, created_at) 
                    VALUES ($listing_id, $user_id, '$move_in_date', $duration, $occupants, $total_amount, '$message', 'pending', 'pending', NOW())";
    
    if (mysqli_query($conn, $booking_query)) {
        $booking_id = mysqli_insert_id($conn);
        
        // Insert payment record
        $payment_amount = $listing['price'] + $listing['security_deposit']; // First month + security deposit
        $payment_query = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status, created_at) 
                        VALUES ($booking_id, $payment_amount, '$payment_method', 'CASH_" . time() . "', 'pending', NOW())";
        mysqli_query($conn, $payment_query);
        
        // Send notification to listing owner
        $owner_id = $listing['user_id'];
        $notification_message = "New booking request for your PG: " . $listing['title'];
        
        $notification_query = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                            VALUES ($user_id, $owner_id, $listing_id, '$notification_message', NOW())";
        mysqli_query($conn, $notification_query);
        
        // Clear booking data from session
        unset($_SESSION['booking_data']);
        
        // Redirect to confirmation page
        header("Location: booking-confirmation.php?id=$booking_id");
        exit();
    }
}

// Include header
include '../includes/user_header.php';
?>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Payment</h1>
        <p class="text-gray-600">Complete your booking by making a payment</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Payment Form -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Payment Method</h2>
                </div>
                
                <form action="payment.php" method="POST" class="p-6">
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-4">Select Payment Method</label>
                        
                        <div class="space-y-4">
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="cash" class="h-5 w-5 text-blue-600" checked>
                                <div class="ml-3">
                                    <span class="block font-medium">Cash Payment</span>
                                    <span class="text-sm text-gray-500">Pay in cash at the time of check-in</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border rounded-lg cursor-not-allowed opacity-50">
                                <input type="radio" name="payment_method" value="online" class="h-5 w-5 text-blue-600" disabled>
                                <div class="ml-3">
                                    <span class="block font-medium">Online Payment</span>
                                    <span class="text-sm text-gray-500">Pay securely online (Coming Soon)</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border rounded-lg cursor-not-allowed opacity-50">
                                <input type="radio" name="payment_method" value="bank_transfer" class="h-5 w-5 text-blue-600" disabled>
                                <div class="ml-3">
                                    <span class="block font-medium">Bank Transfer</span>
                                    <span class="text-sm text-gray-500">Pay via bank transfer (Coming Soon)</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6">
                        <div class="mb-6">
                            <h3 class="font-semibold mb-2">Payment Terms</h3>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li><i class="fas fa-check text-green-500 mr-2"></i> Your booking will be confirmed after the owner approves your request.</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i> For cash payment, you'll need to pay at the time of check-in.</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i> The security deposit is refundable at the end of your stay, subject to terms and conditions.</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i> Cancellation policy: Free cancellation up to 48 hours before check-in.</li>
                            </ul>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                            <label for="terms" class="ml-2 block text-sm text-gray-700">
                                I agree to the <a href="#" class="text-blue-600 hover:underline">Terms and Conditions</a> and <a href="#" class="text-blue-600 hover:underline">Cancellation Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md w-full">
                            Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Booking Summary -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-24">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Booking Summary</h2>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <?php
                        // Get primary image
                        $image_query = "SELECT image_url FROM listing_images WHERE listing_id = $listing_id AND is_primary = 1 LIMIT 1";
                        $image_result = mysqli_query($conn, $image_query);
                        if (mysqli_num_rows($image_result) > 0) {
                            $image = mysqli_fetch_assoc($image_result)['image_url'];
                            echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($listing['title']) . '" class="w-full h-40 object-cover rounded-lg mb-4">';
                        }
                        ?>
                        
                        <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <p class="text-gray-600 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?>
                        </p>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span>Check-in Date:</span>
                            <span class="font-semibold"><?php echo date('M d, Y', strtotime($booking_data['move_in_date'])); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Duration:</span>
                            <span class="font-semibold"><?php echo $booking_data['duration']; ?> Month(s)</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Occupants:</span>
                            <span class="font-semibold"><?php echo $booking_data['occupants']; ?></span>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between mb-2">
                            <span>Monthly Rent:</span>
                            <span>₹<?php echo number_format($listing['price']); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Security Deposit:</span>
                            <span>₹<?php echo number_format($listing['security_deposit']); ?></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg mt-4">
                            <span>First Payment:</span>
                            <span class="text-blue-600">₹<?php echo number_format($listing['price'] + $listing['security_deposit']); ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Includes first month's rent and security deposit
                        </p>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between font-bold">
                            <span>Total Contract Value:</span>
                            <span>₹<?php echo number_format($booking_data['total_amount']); ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            For the entire duration of <?php echo $booking_data['duration']; ?> month(s)
                        </p>
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

