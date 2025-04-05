<?php
// Start session for user authentication
session_start();

// Database connection
require_once 'includes/db_connect.php';

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 100000;
$amenities = isset($_GET['amenities']) ? $_GET['amenities'] : [];
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$premium = isset($_GET['premium']) ? intval($_GET['premium']) : 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT l.*, 
            (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
            u.name as owner_name
          FROM listings l
          JOIN users u ON l.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM listings l WHERE 1=1";

// Add filters
if (!empty($type)) {
    $query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
    $count_query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
}

if (!empty($location)) {
    $query .= " AND (l.city LIKE '%" . mysqli_real_escape_string($conn, $location) . "%' OR l.locality LIKE '%" . mysqli_real_escape_string($conn, $location) . "%')";
    $count_query .= " AND (l.city LIKE '%" . mysqli_real_escape_string($conn, $location) . "%' OR l.locality LIKE '%" . mysqli_real_escape_string($conn, $location) . "%')";
}

if ($premium) {
    $query .= " AND l.is_premium = 1";
    $count_query .= " AND l.is_premium = 1";
}

$query .= " AND l.price BETWEEN $min_price AND $max_price";
$count_query .= " AND l.price BETWEEN $min_price AND $max_price";

// Add amenities filter if selected
if (!empty($amenities) && is_array($amenities)) {
    $query .= " AND l.id IN (
                SELECT listing_id FROM listing_amenities 
                WHERE amenity_id IN (" . implode(',', array_map('intval', $amenities)) . ")
                GROUP BY listing_id
                HAVING COUNT(DISTINCT amenity_id) = " . count($amenities) . "
              )";
    $count_query .= " AND l.id IN (
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
        <h1 class="text-3xl font-bold">
            <?php
            if (!empty($type)) {
                echo ucfirst(htmlspecialchars($type)) . 's';
            } elseif (!empty($location)) {
                echo 'Accommodations in ' . htmlspecialchars($location);
            } elseif ($premium) {
                echo 'Premium Properties';
            } else {
                echo 'Explore All Listings';
            }
            ?>
        </h1>
        <p class="text-gray-600 mt-2">
            <?php echo $total_items; ?> results found
        </p>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-xl font-bold mb-4">Filters</h2>
                
                <form action="explore.php" method="GET" id="filter-form">
                    <!-- Preserve existing query parameters -->
                    <?php if (!empty($type)): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($location)): ?>
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <?php endif; ?>
                    
                    <?php if ($premium): ?>
                        <input type="hidden" name="premium" value="1">
                    <?php endif; ?>
                    
                    <!-- Property Type -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Property Type</h3>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="type" value="" class="mr-2" <?php echo empty($type) ? 'checked' : ''; ?>>
                                All Types
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="type" value="room" class="mr-2" <?php echo $type === 'room' ? 'checked' : ''; ?>>
                                Rooms
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="type" value="roommate" class="mr-2" <?php echo $type === 'roommate' ? 'checked' : ''; ?>>
                                Roommates
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="type" value="pg" class="mr-2" <?php echo $type === 'pg' ? 'checked' : ''; ?>>
                                PG Accommodations
                            </label>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Price Range</h3>
                        <div class="flex items-center">
                            <input 
                                type="number" 
                                name="min_price" 
                                placeholder="Min" 
                                class="w-1/2 p-2 border rounded-md mr-2" 
                                value="<?php echo $min_price > 0 ? $min_price : ''; ?>"
                            >
                            <span class="mx-2">-</span>
                            <input 
                                type="number" 
                                name="max_price" 
                                placeholder="Max" 
                                class="w-1/2 p-2 border rounded-md" 
                                value="<?php echo $max_price < 100000 ? $max_price : ''; ?>"
                            >
                        </div>
                    </div>
                    
                    <!-- Amenities -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Amenities</h3>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
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
                    
                    <a href="explore.php" class="block text-center mt-4 text-blue-600 hover:underline">
                        Clear All Filters
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Listings Grid -->
        <div class="lg:w-3/4">
            <!-- Category Tabs -->
            <div class="mb-6 border-b">
                <nav class="flex space-x-8">
                    <a href="explore.php" class="<?php echo empty($type) ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> py-4 px-1 font-medium">
                        All Listings
                    </a>
                    <a href="explore.php?type=room" class="<?php echo $type === 'room' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> py-4 px-1 font-medium">
                        Rooms
                    </a>
                    <a href="explore.php?type=roommate" class="<?php echo $type === 'roommate' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> py-4 px-1 font-medium">
                        Roommates
                    </a>
                    <a href="explore.php?type=pg" class="<?php echo $type === 'pg' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> py-4 px-1 font-medium">
                        PG Accommodations
                    </a>
                </nav>
            </div>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <!-- Listings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($listing = mysqli_fetch_assoc($result)): ?>
                        <?php include 'includes/listing-card.php'; ?>
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
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-search fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No listings found</h3>
                    <p class="text-gray-600 mb-6">
                        We couldn't find any listings matching your search criteria.
                    </p>
                    <a href="explore.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                        Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

