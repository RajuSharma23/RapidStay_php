<?php
// Start session for user authentication
session_start();

// Database connection
require_once 'includes/db_connect.php';

// Pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Filter parameters
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : 100000;
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$amenities = isset($_GET['amenities']) ? $_GET['amenities'] : [];

// Count query for pagination
$count_query = "SELECT COUNT(*) as total FROM listings WHERE type = 'pg' AND is_active = 1 AND is_verified = 1";

// Main query for listings
$query = "SELECT l.*, 
          (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
          (SELECT COUNT(*) FROM reviews WHERE listing_id = l.id) as reviews_count
          FROM listings l
          WHERE l.type = 'pg' AND l.is_active = 1 AND l.is_verified = 1";

// Apply price filter
$query .= " AND l.price BETWEEN $price_min AND $price_max";

// Apply location filter if specified
if (!empty($location)) {
    $query .= " AND (l.city LIKE '%$location%' OR l.locality LIKE '%$location%')";
    $count_query .= " AND (city LIKE '%$location%' OR locality LIKE '%$location%')";
}

// Apply amenities filter if selected
if (!empty($amenities) && is_array($amenities)) {
    $query .= " AND l.id IN (
                    SELECT listing_id FROM listing_amenities 
                    WHERE amenity_id IN (" . implode(',', array_map('intval', $amenities)) . ")
                    GROUP BY listing_id
                    HAVING COUNT(DISTINCT amenity_id) = " . count($amenities) . "
                  )";
    $count_query .= " AND id IN (
                    SELECT listing_id FROM listing_amenities 
                    WHERE amenity_id IN (" . implode(',', array_map('intval', $amenities)) . ")
                    GROUP BY listing_id
                    HAVING COUNT(DISTINCT amenity_id) = " . count($amenities) . "
                  )";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY l.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY l.price DESC";
        break;
    case 'rating':
        $query .= " ORDER BY l.rating DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY l.created_at DESC";
        break;
}

// Add pagination
$query .= " LIMIT $offset, $items_per_page";

// Execute queries
$result = mysqli_query($conn, $query);
$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_items = $count_data['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get all amenities for filter
$amenities_query = "SELECT * FROM amenities ORDER BY name";
$amenities_result = mysqli_query($conn, $amenities_query);

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold">PG Accommodations</h1>
        <p class="text-gray-600 mt-2">
            <?php echo $total_items; ?> PG accommodations found
        </p>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-1/4">
            <div class="bg-white border-top rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-xl font-bold mb-4">Filters</h2>
                
                <form action="list.php" method="GET" id="filter-form">
                    <!-- Location Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Location</h3>
                        <input 
                            type="text" 
                            name="location" 
                            value="<?php echo htmlspecialchars($location); ?>" 
                            placeholder="City/Locality" 
                            class="w-full p-2 border rounded-md"
                        >
                    </div>
                    
                    <!-- Price Range Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Price Range (₹)</h3>
                        <div class="flex space-x-2">
                            <input 
                                type="number" 
                                name="price_min" 
                                value="<?php echo $price_min; ?>" 
                                placeholder="Min" 
                                class="w-1/2 p-2 border rounded-md"
                            >
                            <input 
                                type="number" 
                                name="price_max" 
                                value="<?php echo $price_max; ?>" 
                                placeholder="Max" 
                                class="w-1/2 p-2 border rounded-md"
                            >
                        </div>
                    </div>
                    
                    <!-- Amenities Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Amenities</h3>
                        <div class="space-y-2">
                            <?php 
                            if (mysqli_num_rows($amenities_result) > 0) {
                                while ($amenity = mysqli_fetch_assoc($amenities_result)) {
                                    $checked = in_array($amenity['id'], $amenities) ? 'checked' : '';
                                    ?>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            name="amenities[]" 
                                            value="<?php echo $amenity['id']; ?>" 
                                            class="mr-2"
                                            <?php echo $checked; ?>
                                        >
                                        <?php echo htmlspecialchars($amenity['name']); ?>
                                    </label>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Sort By -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Sort By</h3>
                        <select name="sort" class="w-full p-2 border rounded-md">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md transition duration-300">
                        Apply Filters
                    </button>
                    
                    <a href="list.php" class="block text-center mt-4 text-blue-600 hover:underline">
                        Clear All Filters
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Listings Grid -->
        <div class="lg:w-3/4">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <!-- Listings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($listing = mysqli_fetch_assoc($result)): ?>
                        <a href="listing.php?id=<?php echo $listing['id']; ?>" class="block group">
                            <div class="bg-white border-top rounded-lg overflow-hidden shadow-sm group-hover:shadow-md transition duration-300 h-full">
                                <div class="relative">
                                    <div class="h-48 overflow-hidden">
                                        <img 
                                            src="<?php echo !empty($listing['primary_image']) ? htmlspecialchars($listing['primary_image']) : 'assets/images/placeholder.jpg'; ?>" 
                                            alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                        >
                                    </div>
                                    
                                    <?php if ($listing['is_premium']): ?>
                                        <div class="absolute top-3 left-3">
                                            <span class="px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-medium rounded-full">
                                                Premium
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <?php
                                        // Check if in wishlist
                                        $user_id = $_SESSION['user_id'];
                                        $wishlist_check = "SELECT id FROM wishlist WHERE user_id = $user_id AND listing_id = " . $listing['id'];
                                        $wishlist_result = mysqli_query($conn, $wishlist_check);
                                        $in_wishlist = mysqli_num_rows($wishlist_result) > 0;
                                        ?>
                                        <button 
                                            class="absolute bottom-3 right-3 w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center wishlist-toggle"
                                            data-listing-id="<?php echo $listing['id']; ?>"
                                            data-in-wishlist="<?php echo $in_wishlist ? '1' : '0'; ?>"
                                        >
                                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart text-<?php echo $in_wishlist ? 'red' : 'gray'; ?>-500"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="p-4">
                                    <div class="flex items-center text-sm text-gray-500 mb-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <span><?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></span>
                                    </div>
                                    
                                    <h3 class="font-bold text-lg mb-1 group-hover:text-blue-600 transition duration-300">
                                        <?php echo htmlspecialchars($listing['title']); ?>
                                    </h3>
                                    
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
                                    
                                    <div class="flex justify-between items-end">
                                        <div class="font-bold text-lg text-blue-600">
                                            ₹<?php echo number_format($listing['price']); ?>
                                            <span class="text-xs font-normal text-gray-500">/month</span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M d', strtotime($listing['available_from'])); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Key Features -->
                                    <div class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-xs text-gray-500">
                                        <div class="flex items-center">
                                            <i class="fas fa-bed mr-1"></i>
                                            <?php echo $listing['max_occupants']; ?> Beds
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-bath mr-1"></i>
                                            <?php echo $listing['bathrooms']; ?> Bath
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-couch mr-1"></i>
                                            <?php echo ucfirst($listing['furnishing_type']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-4 py-2 border rounded-md <?php echo $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- No Results -->
                <div class="bg-white rounded-lg border-top shadow-md p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-search fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No PG accommodations found</h3>
                    <p class="text-gray-600 mb-6">
                        We couldn't find any PG listings matching your search criteria. Try adjusting your filters.
                    </p>
                    <a href="list.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Wishlist AJAX Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wishlistButtons = document.querySelectorAll('.wishlist-toggle');
        
        wishlistButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const listingId = this.getAttribute('data-listing-id');
                const inWishlist = this.getAttribute('data-in-wishlist') === '1';
                const icon = this.querySelector('i');
                
                // AJAX request to update wishlist
                fetch('wishlist_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `listing_id=${listingId}&action=${inWishlist ? 'remove' : 'add'}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Toggle wishlist state
                        this.setAttribute('data-in-wishlist', inWishlist ? '0' : '1');
                        
                        if (inWishlist) {
                            // Remove from wishlist
                            icon.classList.remove('fas', 'text-red-500');
                            icon.classList.add('far', 'text-gray-500');
                        } else {
                            // Add to wishlist
                            icon.classList.remove('far', 'text-gray-500');
                            icon.classList.add('fas', 'text-red-500');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>