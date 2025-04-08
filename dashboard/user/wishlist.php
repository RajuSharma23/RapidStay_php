<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/wishlist.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get wishlist items
$wishlist_query = "SELECT w.*, l.title, l.price, l.type, l.locality, l.city, l.is_active,
                  (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                  FROM wishlist w
                  JOIN listings l ON w.listing_id = l.id
                  WHERE w.user_id = $user_id
                  ORDER BY w.created_at DESC";
$wishlist_result = mysqli_query($conn, $wishlist_query);

// Include header
include '../includes/user_header.php';
?>
<style>
    .main{
        margin-left:250px;
        
    }
    .header{
        margin-top:100px;
    }
</style>

<!-- Main Content -->
<div class="main flex-1 p-8 overflow-auto">
    <div class="mb-8 flex header justify-between items-center">
        <h1 class="text-2xl font-bold">My Wishlist</h1>
        <a href="../../explore.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-search mr-2"></i> Explore More PGs
        </a>
    </div>
    
    <?php if (mysqli_num_rows($wishlist_result) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($item = mysqli_fetch_assoc($wishlist_result)): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="relative">
                        <img 
                            src="<?php echo !empty($item['primary_image']) ? htmlspecialchars($item['primary_image']) : '../../assets/images/placeholder.jpg'; ?>" 
                            alt="<?php echo htmlspecialchars($item['title']); ?>" 
                            class="w-full h-48 object-cover"
                        >
                        
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-1 bg-white text-xs font-medium rounded-full">
                                <?php echo htmlspecialchars(ucfirst($item['type'])); ?>
                            </span>
                        </div>
                        
                        <form action="../../wishlist_action.php" method="POST" class="absolute bottom-3 right-3">
                            <input type="hidden" name="listing_id" value="<?php echo $item['listing_id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center">
                                <i class="fas fa-heart text-red-500"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="p-4">
                        <div class="flex items-center text-sm text-gray-500 mb-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <span><?php echo htmlspecialchars($item['locality'] . ', ' . $item['city']); ?></span>
                        </div>
                        
                        <h3 class="font-bold text-lg mb-2">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        
                        <div class="flex justify-between items-end">
                            <div class="font-bold text-lg text-blue-600">
                                ₹<?php echo number_format($item['price']); ?>
                                <span class="text-xs font-normal text-gray-500">/month</span>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="../../listing.php?id=<?php echo $item['listing_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($item['is_active']): ?>
                                    <a href="booking-form.php?id=<?php echo $item['listing_id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                                        Book Now
                                    </a>
                                <?php else: ?>
                                    <span class="bg-gray-300 text-gray-600 px-3 py-1 rounded-md text-sm cursor-not-allowed">
                                        Unavailable
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="far fa-heart fa-4x"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Your wishlist is empty</h3>
            <p class="text-gray-600 mb-6">
                You haven't added any PGs to your wishlist yet. Explore our listings and add your favorites!
            </p>
            <a href="../../explore.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                Explore PGs
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../includes/user_footer.php';
?>

