<?php
// Start session for user authentication
session_start();

// Database connection
require_once 'includes/db_connect.php';

// Fetch featured listings
$featured_query = "SELECT l.*, 
                    (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 3 LIMIT 3) as primary_image 
                  FROM listings l 
                  WHERE l.is_featured = 1 
                  LIMIT 6";
$featured_result = mysqli_query($conn, $featured_query);

// Fetch popular cities
$cities_query = "SELECT * FROM cities WHERE is_popular = 1 ORDER BY name ASC LIMIT 8";
$cities_result = mysqli_query($conn, $cities_query);

// Include header
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/styles.css">

<!-- Hero Section -->
<section class="relative w-full Hero-Section bg-cover bg-center" style="background-image: url('assets/images/hero-bg.jpg');">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="container mx-auto px-4 h-full flex flex-col justify-center items-center text-center relative z-10">
        <p class="text-white text-xl mb-2">Room for rent that fit your timeline</p>
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-8">Discover a place you'll love</h1>
        
        <!-- Search Bar -->
        <div class="w-full max-w-3xl bg-white border-top rounded-lg shadow-lg overflow-hidden">
            <form action="explore.php" method="GET" class="flex flex-col md:flex-row">
                <div class="flex-1 p-3 border-b md:border-b-0 md:border-r border-gray-200">
                    <div class="flex items-center">
                        <div class="w-10 text-center">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <select name="type" class="w-full p-2 focus:outline-none text-gray-700">
                                <option value="">Search</option>
                                <option value="room">Room</option>
                                <option value="roommate">Roommate</option>
                                <option value="pg">PG</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex-1 p-3 border-b md:border-b-0 md:border-r border-gray-200">
                    <div class="flex items-center">
                        <div class="w-10 text-center">
                            <i class="fas fa-map-marker-alt text-gray-400"></i>
                        </div>
                        <input type="text" name="location" placeholder="City/Locality" class="w-full p-2 focus:outline-none text-gray-700">
                    </div>
                </div>
                <div class="p-3">
                    <button type="submit" class="w-full md:w-auto bg-blue-600 btn-bg hover:bg-blue-700 text-white px-8 py-2 rounded-md transition duration-300">
                        Search
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Quick Links -->
        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <a href="explore.php?type=room" class="bg-white bg-opacity-20 border-top hover:bg-opacity-30 text-white px-6 py-2 rounded-full transition duration-300">
                Rooms
            </a>
            <a href="explore.php?type=roommate" class="bg-white bg-opacity-20 border-top hover:bg-opacity-30 text-white px-6 py-2 rounded-full transition duration-300">
                Roommates
            </a>
            <a href="explore.php?type=pg" class="bg-white bg-opacity-20 border-top hover:bg-opacity-30 text-white px-6 py-2 rounded-full transition duration-300">
                PG Accommodations
            </a>
        </div>
    </div>
</section>

<!-- Rental Agreement Section -->
<section class="py-16 bg-gray-50 m-l-r">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold mb-4">Getting Rental Agreement made easy, quick and affordable</h2>
                <p class="text-gray-600 mb-6">
                    Save time and hassle with our streamlined rental agreement process. 
                    Create legally binding documents in minutes.
                </p>
                <a href="rental-agreement.php" class="inline-block btn-bg bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition duration-300">
                    Create Now →
                </a>
            </div>
            <div class="md:w-1/2">
                <img src="assets/images/Why-Choose-Signin-App-1024x832-1.png" alt="Rental Agreement" class="w-full max-w-md mx-auto">
            </div>
        </div>
    </div>
</section>

<!-- Premium Properties Section -->
<section class="py-16 m-l-r">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="md:w-1/2 order-2 md:order-1">
                <div class="grid grid-cols-2 gap-4">
                    <img src="assets/images/premium-property.jpg" alt="Premium Property" class="w-full h-48 object-cover rounded-lg">
                    <img src="assets/images/premium-property2.jpg" alt="Premium Property" class="w-full h-48 object-cover rounded-lg">
                </div>
            </div>
            <div class="md:w-1/2 order-1 md:order-2">
                <h2 class="text-3xl font-bold mb-4">Are you looking for Premium Properties?</h2>
                <p class="text-gray-600 mb-6">
                    Discover our exclusive selection of high-end rental properties, 
                    featuring premium amenities and prime locations.
                </p>
                <a href="explore.php?premium=1" class="inline-block btn-bg bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition duration-300">
                    View Properties
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Listings Section -->
<section class="py-16 bg-gray-50 m-l-r">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Featured Properties</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Explore our handpicked selection of the best rental properties available right now.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2  lg:grid-cols-3 gap-8">
            <?php
            if (mysqli_num_rows($featured_result) > 0) {
                while ($listing = mysqli_fetch_assoc($featured_result)) {
                    include 'includes/listing-card.php';
                }
            } else {
                echo '<div class="col-span-3 text-center py-8">';
                echo '<p class="text-gray-500">No featured listings available at the moment.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-10">
            <a href="explore.php" class="inline-block bg-blue-600 btn-bg hover:bg-blue-700 text-white px-8 py-3 rounded-md transition duration-300">
                Explore All Listings
            </a>
        </div>
    </div>
</section>

<!-- Popular Cities Section -->
<section class="py-16 m-l-r">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">View rooms in Popular Cities</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Find the perfect accommodation in these top destinations
            </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            if (mysqli_num_rows($cities_result) > 0) {
                while ($city = mysqli_fetch_assoc($cities_result)) {
                    ?>
                    <a href="explore.php?location=<?php echo urlencode($city['name']); ?>" class="relative group overflow-hidden rounded-lg">
                        <img 
                            src="<?php echo htmlspecialchars($city['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($city['name']); ?>" 
                            class="w-full h-40 object-cover group-hover:scale-110 transition duration-500"
                        >
                        <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                            <span class="text-white font-bold text-lg uppercase"><?php echo htmlspecialchars($city['name']); ?></span>
                        </div>
                    </a>
                    <?php
                }
            } else {
                // Fallback cities if none in database
                $fallback_cities = ['PUNJAB', 'DELHI', 'BANGALORE', 'KOLKATA', 'GURGAON', 'CHENNAI', 'CHANDIGARH', 'MUMBAI'];
                foreach ($fallback_cities as $city) {
                    ?>
                    <a href="explore.php?location=<?php echo urlencode($city); ?>" class="relative group overflow-hidden rounded-lg">
                        <img 
                            src="assets/images/cities/<?php echo strtolower($city); ?>.jpg" 
                            alt="<?php echo $city; ?>" 
                            class="w-full h-40 object-cover group-hover:scale-110 transition duration-500"
                        >
                        <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                            <span class="text-white font-bold text-lg uppercase"><?php echo $city; ?></span>
                        </div>
                    </a>
                    <?php
                }
            }
            ?>
        </div>
        
        <div class="text-center mt-10 m-l-r view-btn">
            <a href="cities.php" class="inline-block btn-bg bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md transition duration-300">
                View All Cities
            </a>
        </div>
    </div>
</section>

<!-- Mobile App Section -->
<section class="py-16 bg-gray-50 m-l-r">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold mb-4">Connect with us from anywhere</h2>
                <p class="text-gray-600 mb-6">
                    Download the mobile app to search for rooms, connect with roommates, 
                    and manage your bookings on the go.
                </p>
                <a href="#" class="inline-block btn-bg bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition duration-300">
                    Download Now
                </a>
            </div>
            <div class="md:w-1/2">
                <img src="assets/images/rapidstay-app.jpg" alt="Mobile App" class="w-full max-w-md mx-auto">
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-16  ">
    <div class="container  m-l-r mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">How RapidStay Works</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Find your perfect accommodation in just a few simple steps
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Search</h3>
                <p class="text-gray-600">
                    Browse through our extensive listings of rooms, roommates, and PG accommodations.
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comments text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Connect</h3>
                <p class="text-gray-600">
                    Contact property owners or potential roommates directly through our platform.
                </p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-home text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Move In</h3>
                <p class="text-gray-600">
                    Book your chosen accommodation and get ready to move into your new home.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-16 bg-gray-50 m-l-r">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">What Our Users Say</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Hear from people who found their perfect accommodation through RapidStay
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg border-top shadow-md">
                <div class="flex items-center mb-4">
                    <img src="assets/images/testimonial-1.jpg" alt="User" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <h4 class="font-bold">Priya Sharma</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600">
                    "I found my perfect PG accommodation through RapidStay. The process was smooth and the platform was very user-friendly."
                </p>
            </div>
            
            <div class="bg-white p-6 border-top rounded-lg shadow-md">
                <div class="flex items-center mb-4">
                    <img src="assets/images/testimonial-2.jpg" alt="User" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <h4 class="font-bold">Rahul Verma</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600">
                    "RapidStay helped me find a compatible roommate in a new city. The detailed profiles and verification system gave me peace of mind."
                </p>
            </div>
            
            <div class="bg-white p-6 border-top rounded-lg shadow-md">
                <div class="flex items-center mb-4">
                    <img src="assets/images/testimonial-3.jpg" alt="User" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <h4 class="font-bold">Ananya Patel</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600">
                    "As a property owner, listing my rooms on RapidStay has been a great experience. I found reliable tenants quickly and easily."
                </p>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>

