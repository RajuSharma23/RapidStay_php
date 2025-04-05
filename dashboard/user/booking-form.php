<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/booking-form.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get listing ID
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid ID
if ($listing_id <= 0) {
    header("Location: ../../explore.php");
    exit();
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

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

// Process form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $move_in_date = mysqli_real_escape_string($conn, $_POST['move_in_date']);
    $duration = intval($_POST['duration']);
    $occupants = intval($_POST['occupants']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Validate data
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($move_in_date)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($duration <= 0) {
        $error = "Please select a valid duration.";
    } elseif ($occupants <= 0 || $occupants > $listing['max_occupants']) {
        $error = "Please select a valid number of occupants.";
    } else {
        // Calculate total amount
        $total_amount = $listing['price'] * $duration;
        
        // Redirect to payment page
        $_SESSION['booking_data'] = [
            'listing_id' => $listing_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'move_in_date' => $move_in_date,
            'duration' => $duration,
            'occupants' => $occupants,
            'message' => $message,
            'total_amount' => $total_amount
        ];
        
        header("Location: payment.php");
        exit();
    }
}

// Include header
include '../includes/user_header.php';
?>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Book PG Accommodation</h1>
        <p class="text-gray-600">Fill in your details to book this PG</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Booking Form -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Booking Details</h2>
                </div>
                
                <form action="booking-form.php?id=<?php echo $listing_id; ?>" method="POST" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?php echo htmlspecialchars($user['name']); ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="address" class="block text-gray-700 font-medium mb-2">Current Address</label>
                            <input 
                                type="text" 
                                id="address" 
                                name="address" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="move_in_date" class="block text-gray-700 font-medium mb-2">Move-in Date</label>
                            <input 
                                type="date" 
                                id="move_in_date" 
                                name="move_in_date" 
                                min="<?php echo date('Y-m-d', strtotime($listing['available_from'])); ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="duration" class="block text-gray-700 font-medium mb-2">Duration</label>
                            <select 
                                id="duration" 
                                name="duration" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                                <option value="1">1 Month</option>
                                <option value="3">3 Months</option>
                                <option value="6">6 Months</option>
                                <option value="12" selected>12 Months</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="occupants" class="block text-gray-700 font-medium mb-2">Number of Occupants</label>
                            <select 
                                id="occupants" 
                                name="occupants" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                                <?php for ($i = 1; $i <= $listing['max_occupants']; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="message" class="block text-gray-700 font-medium mb-2">Message to Owner (Optional)</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="4" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                            Proceed to Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Listing Summary -->
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
                        <p class="text-gray-600 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Owner: <?php echo htmlspecialchars($listing['owner_name']); ?>
                        </p>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span>Monthly Rent:</span>
                            <span class="font-semibold">₹<?php echo number_format($listing['price']); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Security Deposit:</span>
                            <span class="font-semibold">₹<?php echo number_format($listing['security_deposit']); ?></span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm text-gray-600">
                            <span>Available From:</span>
                            <span><?php echo date('M d, Y', strtotime($listing['available_from'])); ?></span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm text-gray-600">
                            <span>Max Occupants:</span>
                            <span><?php echo $listing['max_occupants']; ?></span>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between font-bold text-lg">
                            <span>First Payment:</span>
                            <span class="text-blue-600">₹<?php echo number_format($listing['price'] + $listing['security_deposit']); ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Includes first month's rent and security deposit
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

