<?php
// Start session
session_start();

// Include database connection - Fixed path
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the roommate card functions - Fixed path
require_once 'dashboard/user/roommates.php';

// Get current user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get filter parameters
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$max_rent = isset($_GET['max_rent']) ? intval($_GET['max_rent']) : 0;
$gender_preference = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : '';

// Build query to find potential roommates
$query = "SELECT 
            u.id, 
            u.name, 
            l.locality, 
            l.city, 
            l.price, 
            u.gender,
            l.id as listing_id,
            l.type,
            u.profile_image
          FROM 
            listings l
          JOIN 
            users u ON l.user_id = u.id
          WHERE 
            l.type = 'roommate' 
            AND l.is_active = 1
            AND u.id != $user_id";

// Add filters if provided
if (!empty($location)) {
    $query .= " AND (l.locality LIKE '%$location%' OR l.city LIKE '%$location%')";
}

if ($max_rent > 0) {
    $query .= " AND l.price <= $max_rent";
}

if (!empty($gender_preference) && $gender_preference != 'any') {
    $query .= " AND u.gender = '$gender_preference'";
}

// Execute query
$result = mysqli_query($conn, $query);

// Include header
include '../includes/user_header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Find Roommates</h1>
        <p class="text-gray-600">Connect with potential roommates who match your preferences</p>
    </div>

    <!-- Add the "List as Roommate" button -->
    <div class="mb-8 flex justify-end">
        <button id="listAsRoommateBtn" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition duration-300 flex items-center">
            <i class="fas fa-plus mr-2"></i> List as Roommate
        </button>
    </div>

    <!-- Search filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold mb-4">Filter Roommates</h2>
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="location" class="block text-gray-700 mb-2">Location</label>
                <input 
                    type="text" 
                    id="location" 
                    name="location" 
                    placeholder="City or locality" 
                    value="<?php echo htmlspecialchars($location); ?>" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            
            <div>
                <label for="max_rent" class="block text-gray-700 mb-2">Maximum Rent (₹)</label>
                <input 
                    type="number" 
                    id="max_rent" 
                    name="max_rent" 
                    placeholder="Enter max rent" 
                    value="<?php echo $max_rent > 0 ? $max_rent : ''; ?>" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            
            <div>
                <label for="gender" class="block text-gray-700 mb-2">Looking For</label>
                <select 
                    id="gender" 
                    name="gender" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="any" <?php echo $gender_preference == 'any' ? 'selected' : ''; ?>>Any</option>
                    <option value="male" <?php echo $gender_preference == 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $gender_preference == 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition duration-300">
                    Apply Filters
                </button>
                <a href="?clear=1" class="ml-4 text-gray-600 px-6 py-2 rounded-md border hover:bg-gray-50 transition duration-300">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Roommate listings -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        // Add CSS styles for the cards
        add_roommate_card_styles();
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate rough distance (simplified for demo)
                $distance = rand(1, 15) . '.' . rand(1, 9) . ' km';
                
                // Prepare listing data for the card
                $listing = [
                    'id' => $row['listing_id'],
                    'name' => $row['name'],
                    'location' => $row['locality'] . ', ' . $row['city'],
                    'rent' => $row['price'],
                    'looking_for_type' => ucfirst($row['gender'] ?? 'Any'),
                    'looking_for_roommates' => 'Roommates',
                    'distance' => $distance,
                    'profile_img' => !empty($row['profile_image']) ? $row['profile_image'] : 'assets/images/default-user.png'
                ];
                
                // Generate and display the card
                echo generate_roommate_card($listing);
            }
        } else {
            echo '<div class="col-span-full text-center py-16 bg-gray-50 rounded-lg">';
            echo '<i class="fas fa-user-friends text-gray-300 text-5xl mb-4"></i>';
            echo '<h3 class="text-xl font-bold text-gray-700 mb-2">No roommates found</h3>';
            echo '<p class="text-gray-500">Try adjusting your filters to see more results</p>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Roommate Listing Form Modal -->
<div id="roommateFormModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 overflow-y-auto max-h-screen">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-800">List Yourself as a Roommate</h3>
            <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="roommateListingForm" action="process-roommate-listing.php" method="POST" class="px-6 py-4">
            <div class="mb-4">
                <label for="listing_title" class="block text-gray-700 mb-2 font-medium">Listing Title</label>
                <input type="text" id="listing_title" name="listing_title" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., Looking for roommate in Indirapuram">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="locality" class="block text-gray-700 mb-2 font-medium">Locality</label>
                    <input type="text" id="locality" name="locality" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Indirapuram">
                </div>
                
                <div>
                    <label for="city" class="block text-gray-700 mb-2 font-medium">City</label>
                    <input type="text" id="city" name="city" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Ghaziabad">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="price" class="block text-gray-700 mb-2 font-medium">Monthly Rent (₹)</label>
                    <input type="number" id="price" name="price" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., 7000">
                </div>
                
                <div>
                    <label for="looking_for" class="block text-gray-700 mb-2 font-medium">Looking For</label>
                    <select id="looking_for" name="looking_for" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="any">Any</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="description" class="block text-gray-700 mb-2 font-medium">Description</label>
                <textarea id="description" name="description" rows="4" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Describe your place, yourself, and what you're looking for in a roommate..."></textarea>
            </div>
            
            <div class="mb-4">
                <label for="amenities" class="block text-gray-700 mb-2 font-medium">Amenities</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="wifi" class="mr-2">
                        WiFi
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="ac" class="mr-2">
                        AC
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="parking" class="mr-2">
                        Parking
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="washing_machine" class="mr-2">
                        Washing Machine
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="kitchen" class="mr-2">
                        Kitchen
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="amenities[]" value="tv" class="mr-2">
                        TV
                    </label>
                </div>
            </div>
            
            <div class="border-t pt-4 flex justify-end">
                <button type="button" id="cancelBtn" class="px-6 py-2 border rounded-md mr-2">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition duration-300">Submit Listing</button>
            </div>
        </form>
    </div>
</div>

<!-- Script for action buttons -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modal = document.getElementById('roommateFormModal');
    const openModalBtn = document.getElementById('listAsRoommateBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Open modal
    openModalBtn.addEventListener('click', function() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    });
    
    // Close modal functions
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Form submission
    const roommateForm = document.getElementById('roommateListingForm');
    roommateForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // You can use AJAX to submit the form data
        const formData = new FormData(roommateForm);
        
        fetch('process-roommate-listing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Your listing has been submitted successfully!');
                closeModal();
                // Optionally refresh the page to show the new listing
                window.location.reload();
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<?php
// Include footer
include '../includes/user_footer.php';
?>