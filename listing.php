<?php
// Start session for user authentication
session_start();

// Database connection
require_once 'includes/db_connect.php';

// Get listing ID
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid ID
if ($listing_id <= 0) {
    header("Location: explore.php");
    exit();
}

// Fetch listing details
$listing_query = "SELECT l.*, u.name as owner_name, u.profile_image as owner_image, u.created_at as owner_joined
                 FROM listings l
                 JOIN users u ON l.user_id = u.id
                 WHERE l.id = $listing_id";
$listing_result = mysqli_query($conn, $listing_query);

// Check if listing exists
if (mysqli_num_rows($listing_result) == 0) {
    header("Location: explore.php");
    exit();
}

$listing = mysqli_fetch_assoc($listing_result);

// Fetch listing images
$images_query = "SELECT * FROM listing_images WHERE listing_id = $listing_id ORDER BY is_primary DESC";
$images_result = mysqli_query($conn, $images_query);

// Fetch listing amenities
$amenities_query = "SELECT a.* 
                   FROM amenities a
                   JOIN listing_amenities la ON a.id = la.amenity_id
                   WHERE la.listing_id = $listing_id";
$amenities_result = mysqli_query($conn, $amenities_query);

// Fetch similar listings
$similar_query = "SELECT l.*, 
                  (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                  FROM listings l
                  WHERE l.type = '" . mysqli_real_escape_string($conn, $listing['type']) . "'
                  AND l.city = '" . mysqli_real_escape_string($conn, $listing['city']) . "'
                  AND l.id != $listing_id
                  LIMIT 3";
$similar_result = mysqli_query($conn, $similar_query);

// Fetch reviews
$reviews_query = "SELECT r.*, u.name, u.profile_image
                 FROM reviews r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.listing_id = $listing_id
                 ORDER BY r.created_at DESC
                 LIMIT 5";
$reviews_result = mysqli_query($conn, $reviews_query);

// Check if user has this listing in wishlist
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $wishlist_query = "SELECT * FROM wishlist 
                      WHERE user_id = " . intval($_SESSION['user_id']) . " 
                      AND listing_id = $listing_id";
    $wishlist_result = mysqli_query($conn, $wishlist_query);
    $in_wishlist = mysqli_num_rows($wishlist_result) > 0;
}

// Include header
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-4">
        <div class="flex items-center text-sm">
            <a href="index.php" class="text-gray-600 hover:text-blue-600">Home</a>
            <span class="mx-2">/</span>
            <a href="explore.php" class="text-gray-600 hover:text-blue-600">Explore</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900"><?php echo htmlspecialchars($listing['title']); ?></span>
        </div>
    </div>
</div>

<!-- Listing Details -->
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content -->
        <div class="lg:w-2/3">
            <!-- Title and Basic Info -->
            <div class="mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($listing['title']); ?></h1>
                        <div class="flex items-center text-gray-600 mb-2">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span><?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="Share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="wishlist_action.php" method="POST" class="inline">
                                <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                                <input type="hidden" name="action" value="<?php echo $in_wishlist ? 'remove' : 'add'; ?>">
                                <button type="submit" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="<?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                    <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart text-<?php echo $in_wishlist ? 'red' : 'gray'; ?>-500"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=listing.php?id=<?php echo $listing_id; ?>" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition" title="Add to Wishlist">
                                <i class="far fa-heart"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        <?php echo htmlspecialchars(ucfirst($listing['type'])); ?>
                    </span>
                    
                    <?php if ($listing['is_premium']): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                            Premium
                        </span>
                    <?php endif; ?>
                    
                    <div class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium flex items-center">
                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                        <span><?php echo number_format($listing['rating'], 1); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Image Gallery -->
            <div class="mb-8">
                <?php
                $images = [];
                while ($image = mysqli_fetch_assoc($images_result)) {
                    $images[] = $image;
                }
                
                if (count($images) > 0):
                ?>
                    <div class="grid grid-cols-12 gap-2">
                        <!-- Main Image -->
                        <div class="col-span-12 md:col-span-8 h-80 rounded-lg overflow-hidden">
                            <img 
                                src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" 
                                alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                class="w-full h-full object-cover"
                                id="main-image"
                            >
                        </div>
                        
                        <!-- Thumbnail Images -->
                        <div class="col-span-12 md:col-span-4 grid grid-rows-3 gap-2">
                            <?php for ($i = 1; $i < min(count($images), 4); $i++): ?>
                                <div class="h-24 rounded-lg overflow-hidden">
                                    <img 
                                        src="<?php echo htmlspecialchars($images[$i]['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                        class="w-full h-full object-cover cursor-pointer thumbnail-image"
                                        data-src="<?php echo htmlspecialchars($images[$i]['image_url']); ?>"
                                    >
                                </div>
                            <?php endfor; ?>
                            
                            <?php if (count($images) > 4): ?>
                                <div class="h-24 rounded-lg overflow-hidden relative cursor-pointer" id="view-all-images">
                                    <img 
                                        src="<?php echo htmlspecialchars($images[4]['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                        class="w-full h-full object-cover"
                                    >
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                        <span class="text-white font-medium">+<?php echo count($images) - 4; ?> more</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="h-80 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-500">No images available</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">About this place</h2>
                <div class="prose max-w-none text-gray-700">
                    <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                </div>
            </div>
            
            <!-- Amenities -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Amenities</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php
                    if (mysqli_num_rows($amenities_result) > 0) {
                        while ($amenity = mysqli_fetch_assoc($amenities_result)) {
                            ?>
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-<?php echo htmlspecialchars($amenity['icon']); ?> text-blue-600"></i>
                                </div>
                                <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="col-span-3 text-gray-500">No amenities listed</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Location -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Location</h2>
                <div class="h-64 bg-gray-200 rounded-lg mb-4">
                    <!-- Map would be integrated here -->
                    <div class="w-full h-full flex items-center justify-center">
                        <span class="text-gray-500">Map view of <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></span>
                    </div>
                </div>
                <p class="text-gray-700">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                    <?php echo htmlspecialchars($listing['address'] . ', ' . $listing['locality'] . ', ' . $listing['city']); ?>
                </p>
            </div>
            
            <!-- Reviews -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Reviews</h2>
                    <div class="flex items-center">
                        <div class="flex text-yellow-400 mr-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($listing['rating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $listing['rating']): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="font-bold"><?php echo number_format($listing['rating'], 1); ?></span>
                        <span class="text-gray-500 ml-1">(<?php echo $listing['reviews_count']; ?> reviews)</span>
                    </div>
                </div>
                
                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <div class="space-y-6">
                        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <div class="border-b pb-6 last:border-b-0 last:pb-0">
                                <div class="flex items-start">
                                    <img 
                                        src="<?php echo !empty($review['profile_image']) ? htmlspecialchars($review['profile_image']) : 'assets/images/default-user.png'; ?>" 
                                        alt="<?php echo htmlspecialchars($review['name']); ?>" 
                                        class="w-12 h-12 rounded-full mr-4 object-cover"
                                    >
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-bold"><?php echo htmlspecialchars($review['name']); ?></h4>
                                                <div class="flex text-yellow-400 text-sm mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <span class="text-gray-500 text-sm">
                                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($listing['reviews_count'] > 5): ?>
                        <div class="mt-6 text-center">
                            <a href="reviews.php?listing_id=<?php echo $listing_id; ?>" class="text-blue-600 hover:underline">
                                View all <?php echo $listing['reviews_count']; ?> reviews
                            </a>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <p class="text-gray-500">No reviews yet</p>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mt-8 pt-6 border-t">
                        <h3 class="font-bold mb-4">Write a Review</h3>
                        <form action="submit_review.php" method="POST">
                            <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Rating</label>
                                <div class="flex text-2xl text-gray-300 rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star cursor-pointer hover:text-yellow-400" data-rating="<?php echo $i; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="rating-input" value="5">
                            </div>
                            
                            <div class="mb-4">
                                <label for="comment" class="block text-gray-700 mb-2">Your Review</label>
                                <textarea 
                                    id="comment" 
                                    name="comment" 
                                    rows="4" 
                                    class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                ></textarea>
                            </div>
                            
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                                Submit Review
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Similar Listings -->
            <?php if (mysqli_num_rows($similar_result) > 0): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6">Similar Properties</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php while ($similar = mysqli_fetch_assoc($similar_result)): ?>
                            <a href="listing.php?id=<?php echo $similar['id']; ?>" class="block group">
                                <div class="bg-white rounded-lg overflow-hidden border group-hover:shadow-md transition duration-300">
                                    <div class="h-40 overflow-hidden">
                                        <img 
                                            src="<?php echo !empty($similar['primary_image']) ? htmlspecialchars($similar['primary_image']) : 'assets/images/placeholder.jpg'; ?>" 
                                            alt="<?php echo htmlspecialchars($similar['title']); ?>" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                        >
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-bold mb-1 group-hover:text-blue-600 transition duration-300">
                                            <?php echo htmlspecialchars($similar['title']); ?>
                                        </h3>
                                        <p class="text-gray-500 text-sm mb-2">
                                            <?php echo htmlspecialchars($similar['locality'] . ', ' . $similar['city']); ?>
                                        </p>
                                        <div class="font-bold text-lg">
                                            ₹<?php echo number_format($similar['price']); ?>
                                            <span class="text-sm font-normal text-gray-500">/month</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:w-1/3">
            <!-- Price Card -->
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <div class="mb-4">
                    <div class="text-3xl font-bold">
                        ₹<?php echo number_format($listing['price']); ?>
                        <span class="text-base font-normal text-gray-500">/month</span>
                    </div>
                    <div class="flex items-center mt-1">
                        <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>
                        <span class="text-gray-700">Available from: <?php echo date('M d, Y', strtotime($listing['available_from'])); ?></span>
                    </div>
                </div>
                
                <!-- Booking Form -->
                <form action="booking_process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                    
                    <div>
                        <label for="move_in_date" class="block text-gray-700 mb-1">Move-in Date</label>
                        <input 
                            type="date" 
                            id="move_in_date" 
                            name="move_in_date" 
                            min="<?php echo date('Y-m-d', strtotime($listing['available_from'])); ?>" 
                            class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="duration" class="block text-gray-700 mb-1">Duration</label>
                        <select 
                            id="duration" 
                            name="duration" 
                            class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12" selected>12 Months</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="occupants" class="block text-gray-700 mb-1">Number of Occupants</label>
                        <select 
                            id="occupants" 
                            name="occupants" 
                            class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <?php for ($i = 1; $i <= $listing['max_occupants']; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-gray-700 mb-1">Message to Owner (Optional)</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="3" 
                            class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md transition duration-300">
                            Book Now
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=listing.php?id=<?php echo $listing_id; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md text-center transition duration-300">
                            Login to Book
                        </a>
                    <?php endif; ?>
                </form>
                
                <div class="mt-6 pt-6 border-t">
                    <button class="w-full border border-blue-600 text-blue-600 hover:bg-blue-50 py-3 rounded-md transition duration-300 mb-4">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Owner
                    </button>
                    
                    <button class="w-full border border-gray-300 hover:bg-gray-50 py-3 rounded-md transition duration-300">
                        <i class="fas fa-flag mr-2"></i> Report Listing
                    </button>
                </div>
            </div>
            
            <!-- Owner Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <div class="flex items-center mb-4">
                    <img 
                        src="<?php echo !empty($listing['owner_image']) ? htmlspecialchars($listing['owner_image']) : 'assets/images/default-user.png'; ?>" 
                        alt="<?php echo htmlspecialchars($listing['owner_name']); ?>" 
                        class="w-16 h-16 rounded-full mr-4 object-cover"
                    >
                    <div>
                        <h3 class="font-bold"><?php echo htmlspecialchars($listing['owner_name']); ?></h3>
                        <p class="text-gray-500 text-sm">
                            Member since <?php echo date('M Y', strtotime($listing['owner_joined'])); ?>
                        </p>
                    </div>
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-gray-700">Identity verified</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-phone-alt text-blue-500 mr-2"></i>
                        <span class="text-gray-700">Phone verified</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-blue-500 mr-2"></i>
                        <span class="text-gray-700">Email verified</span>
                    </div>
                </div>
                
                <a href="owner_profile.php?id=<?php echo $listing['user_id']; ?>" class="block text-center text-blue-600 hover:underline">
                    View Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Image Gallery Modal -->
<div id="gallery-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
    <button id="close-gallery" class="absolute top-4 right-4 text-white text-2xl">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="w-full max-w-6xl px-4">
        <div class="relative">
            <button id="prev-image" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <button id="next-image" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <img id="gallery-image" src="/placeholder.svg" alt="Gallery Image" class="max-h-[80vh] mx-auto">
        </div>
        
        <div class="mt-4 flex justify-center">
            <div id="image-thumbnails" class="flex space-x-2 overflow-x-auto">
                <!-- Thumbnails will be added here via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
    // Image Gallery Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mainImage = document.getElementById('main-image');
        const thumbnailImages = document.querySelectorAll('.thumbnail-image');
        const viewAllImages = document.getElementById('view-all-images');
        const galleryModal = document.getElementById('gallery-modal');
        const closeGallery = document.getElementById('close-gallery');
        const galleryImage = document.getElementById('gallery-image');
        const prevImage = document.getElementById('prev-image');
        const nextImage = document.getElementById('next-image');
        const imageThumbnails = document.getElementById('image-thumbnails');
        
        // All image sources
        const images = [
            <?php foreach ($images as $image): ?>
                "<?php echo htmlspecialchars($image['image_url']); ?>",
            <?php endforeach; ?>
        ];
        
        let currentImageIndex = 0;
        
        // Change main image when clicking on thumbnails
        thumbnailImages.forEach(function(img) {
            img.addEventListener('click', function() {
                const src = this.getAttribute('data-src');
                mainImage.src = src;
            });
        });
        
        // Open gallery modal
        if (viewAllImages) {
            viewAllImages.addEventListener('click', openGallery);
        }
        
        mainImage.addEventListener('click', openGallery);
        
        function openGallery() {
            galleryModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Set current image
            currentImageIndex = 0;
            updateGalleryImage();
            
            // Create thumbnails
            imageThumbnails.innerHTML = '';
            images.forEach((src, index) => {
                const thumb = document.createElement('div');
                thumb.className = 'w-16 h-16 flex-shrink-0 cursor-pointer rounded overflow-hidden';
                thumb.innerHTML = `<img src="${src}" class="w-full h-full object-cover">`;
                thumb.addEventListener('click', () => {
                    currentImageIndex = index;
                    updateGalleryImage();
                });
                imageThumbnails.appendChild(thumb);
            });
        }
        
        // Close gallery modal
        closeGallery.addEventListener('click', function() {
            galleryModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });
        
        // Navigate through images
        prevImage.addEventListener('click', function() {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            updateGalleryImage();
        });
        
        nextImage.addEventListener('click', function() {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            updateGalleryImage();
        });
        
        function updateGalleryImage() {
            galleryImage.src = images[currentImageIndex];
            
            // Update active thumbnail
            const thumbnails = imageThumbnails.querySelectorAll('div');
            thumbnails.forEach((thumb, index) => {
                if (index === currentImageIndex) {
                    thumb.classList.add('ring-2', 'ring-blue-500');
                } else {
                    thumb.classList.remove('ring-2', 'ring-blue-500');
                }
            });
        }
        
        // Rating stars functionality
        const ratingStars = document.querySelectorAll('.rating-stars i');
        const ratingInput = document.getElementById('rating-input');
        
        if (ratingStars.length > 0) {
            ratingStars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    ratingInput.value = rating;
                    
                    // Update stars UI
                    ratingStars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('text-yellow-400');
                            s.classList.remove('text-gray-300');
                        } else {
                            s.classList.remove('text-yellow-400');
                            s.classList.add('text-gray-300');
                        }
                    });
                });
            });
        }
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>

