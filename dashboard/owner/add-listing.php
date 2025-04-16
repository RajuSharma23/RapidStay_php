<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a PG owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/add-listing.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get maximum allowed images from settings
$max_images = 10; // Default value
$max_images_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'max_images_per_listing'";
$max_images_result = mysqli_query($conn, $max_images_query);
if ($max_images_result && mysqli_num_rows($max_images_result) > 0) {
    $max_images = intval(mysqli_fetch_assoc($max_images_result)['setting_value']);
    if ($max_images <= 0) {
        $max_images = 10; // Fallback if setting is invalid
    }
}

// Get owner ID
$owner_id = $_SESSION['user_id'];

// Get all amenities for selection
$amenities_query = "SELECT * FROM amenities ORDER BY name";
$amenities_result = mysqli_query($conn, $amenities_query);
$amenities = [];
while ($amenity = mysqli_fetch_assoc($amenities_result)) {
    $amenities[] = $amenity;
}

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $price = floatval($_POST['price']);
    $security_deposit = floatval($_POST['security_deposit']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $locality = mysqli_real_escape_string($conn, $_POST['locality']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $zipcode = mysqli_real_escape_string($conn, $_POST['zipcode']);
    $available_from = mysqli_real_escape_string($conn, $_POST['available_from']);
    $min_duration = intval($_POST['min_duration']);
    $max_occupants = intval($_POST['max_occupants']);
    $furnishing_type = mysqli_real_escape_string($conn, $_POST['furnishing_type']);
    $property_size = floatval($_POST['property_size']);
    $bathroom_count = intval($_POST['bathroom_count']);
    $is_shared_bathroom = isset($_POST['is_shared_bathroom']) ? 1 : 0;
    $selected_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    
    // Validate data
    if (empty($title) || empty($description) || empty($type) || $price <= 0 || $security_deposit <= 0 || 
        empty($address) || empty($locality) || empty($city) || empty($state) || empty($zipcode) || 
        empty($available_from) || $min_duration <= 0 || $max_occupants <= 0 || empty($furnishing_type)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check system settings for automatic approval
        $auto_approval = false;
        $settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'pg_approval_mode'";
        $settings_result = mysqli_query($conn, $settings_query);
        if ($settings_result && mysqli_num_rows($settings_result) > 0) {
            $approval_mode = mysqli_fetch_assoc($settings_result)['setting_value'];
            if ($approval_mode === 'auto') {
                $auto_approval = true;
            } else {
                // Check if owner is in auto-approved list
                $auto_approved_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'auto_approved_owners'";
                $auto_approved_result = mysqli_query($conn, $auto_approved_query);
                if ($auto_approved_result && mysqli_num_rows($auto_approved_result) > 0) {
                    $auto_approved_owners = mysqli_fetch_assoc($auto_approved_result)['setting_value'];
                    $auto_approved_array = explode(',', $auto_approved_owners);
                    if (in_array($owner_id, $auto_approved_array)) {
                        $auto_approval = true;
                    }
                }
            }
        }

        // Set is_verified based on approval settings
        $is_verified = $auto_approval ? 1 : 0;
        $is_active = 1; // Define this variable to use in the bind_param

        // Insert listing - FIXED QUERY
        $listing_query = "INSERT INTO listings (user_id, title, description, type, price, security_deposit, 
                         address, locality, city, state, zipcode, available_from, min_duration, max_occupants, 
                         furnishing_type, property_size, bathroom_count, is_shared_bathroom, is_verified, is_active, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($conn, $listing_query);
        mysqli_stmt_bind_param($stmt, "isssddsssssiiisdiiii", 
            $owner_id,           // i - integer
            $title,              // s - string
            $description,        // s - string 
            $type,               // s - string
            $price,              // d - double
            $security_deposit,   // d - double
            $address,            // s - string
            $locality,           // s - string
            $city,               // s - string
            $state,              // s - string
            $zipcode,            // s - string
            $available_from,     // s - string (date as string, not integer)
            $min_duration,       // i - integer
            $max_occupants,      // i - integer
            $furnishing_type,    // s - string (not integer)
            $property_size,      // d - double (not integer)
            $bathroom_count,     // i - integer
            $is_shared_bathroom, // i - integer 
            $is_verified,        // i - integer
            $is_active);         // i - integer

        if (mysqli_stmt_execute($stmt)) {
            $listing_id = mysqli_insert_id($conn);
            
            // Insert amenities
            if (!empty($selected_amenities)) {
                foreach ($selected_amenities as $amenity_id) {
                    $amenity_id = intval($amenity_id);
                    $amenity_query = "INSERT INTO listing_amenities (listing_id, amenity_id) VALUES ($listing_id, $amenity_id)";
                    mysqli_query($conn, $amenity_query);
                }
            }
            
            // Handle image uploads
            $upload_dir = '../../uploads/listings/' . $listing_id . '/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Process uploaded images
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                $images = $_FILES['images'];
                $image_count = count($images['name']);
                $uploaded_count = 0;
                $skipped_count = 0;
                
                for ($i = 0; $i < $image_count && $uploaded_count < $max_images; $i++) {
                    if ($images['error'][$i] === 0) {
                        if (!in_array($images['type'][$i], $allowed_types)) {
                            $error .= "File type not allowed for " . $images['name'][$i] . ". ";
                            $skipped_count++;
                            continue;
                        }
                        
                        if ($images['size'][$i] > $max_size) {
                            $error .= "File size too large for " . $images['name'][$i] . ". ";
                            $skipped_count++;
                            continue;
                        }
                        
                        $filename = 'image_' . time() . '_' . $i . '_' . basename($images['name'][$i]);
                        $target_file = $upload_dir . $filename;
                        
                        if (move_uploaded_file($images['tmp_name'][$i], $target_file)) {
                            // Save image to database with CORRECT path (remove /htdocs/)
                            $image_url = '/Rapidstay1/uploads/listings/' . $listing_id . '/' . $filename;
                            $is_primary = ($i === 0) ? 1 : 0; // First image is primary
                            
                            $image_query = "INSERT INTO listing_images (listing_id, image_url, is_primary, created_at) 
                                          VALUES ($listing_id, '$image_url', $is_primary, NOW())";
                            mysqli_query($conn, $image_query);
                            $uploaded_count++;
                        } else {
                            $error .= "Failed to upload " . $images['name'][$i] . ". ";
                            $skipped_count++;
                        }
                    }
                }
                
                // If there were more images than allowed, add a warning
                if ($image_count > $max_images) {
                    $message = "Your PG listing has been submitted successfully and is pending approval. ";
                    $message .= "Note: Only $max_images out of $image_count images were uploaded (maximum limit reached).";
                } else {
                    $message = "Your PG listing has been submitted successfully and is pending approval.";
                }
                
                // If some images were skipped due to errors
                if ($skipped_count > 0) {
                    $error = "Warning: $skipped_count images could not be uploaded due to errors. " . $error;
                }
            }
            
            $message = "Your PG listing has been submitted successfully and is pending approval.";
            
            // Clear form data on success
            $_POST = array();
        } else {
            $error = "Failed to submit your listing. Please try again.";
        }
    }
}

// Include header
include '../includes/owner_header.php';
?>
<link rel="stylesheet" href="../../assets/css/style.css">

<style>
    .main-content{
        /* margin-left:200px; */
    }
</style>
<!-- Main Content -->
<div class="flex-1 main-content p-20 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Add New PG Listing</h1>
        <p class="text-gray-600">Fill in the details to list your PG accommodation</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="font-bold">PG Details</h2>
        </div>
        
        <form action="add-listing.php" method="POST" enctype="multipart/form-data" class="p-6">
            <!-- Basic Information -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-gray-700 font-medium mb-2">PG Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="type" class="block text-gray-700 font-medium mb-2">PG Type</label>
                        <select 
                            id="type" 
                            name="type" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="">Select Type</option>
                            <option value="room" <?php echo (isset($_POST['type']) && $_POST['type'] === 'room') ? 'selected' : ''; ?>>Room</option>
                            <option value="roommate" <?php echo (isset($_POST['type']) && $_POST['type'] === 'roommate') ? 'selected' : ''; ?>>Roommate</option>
                            <option value="pg" <?php echo (isset($_POST['type']) && $_POST['type'] === 'pg') ? 'selected' : ''; ?>>PG Accommodation</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="price" class="block text-gray-700 font-medium mb-2">Monthly Rent (₹)</label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                            min="1000" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="security_deposit" class="block text-gray-700 font-medium mb-2">Security Deposit (₹)</label>
                        <input 
                            type="number" 
                            id="security_deposit" 
                            name="security_deposit" 
                            value="<?php echo isset($_POST['security_deposit']) ? htmlspecialchars($_POST['security_deposit']) : ''; ?>" 
                            min="1000" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="4" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Location Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="locality" class="block text-gray-700 font-medium mb-2">Locality/Area</label>
                        <input 
                            type="text" 
                            id="locality" 
                            name="locality" 
                            value="<?php echo isset($_POST['locality']) ? htmlspecialchars($_POST['locality']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="city" class="block text-gray-700 font-medium mb-2">City</label>
                        <input 
                            type="text" 
                            id="city" 
                            name="city" 
                            value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="state" class="block text-gray-700 font-medium mb-2">State</label>
                        <input 
                            type="text" 
                            id="state" 
                            name="state" 
                            value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="zipcode" class="block text-gray-700 font-medium mb-2">Zipcode</label>
                        <input 
                            type="text" 
                            id="zipcode" 
                            name="zipcode" 
                            value="<?php echo isset($_POST['zipcode']) ? htmlspecialchars($_POST['zipcode']) : ''; ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                </div>
            </div>
            
            <!-- Property Details -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Property Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="available_from" class="block text-gray-700 font-medium mb-2">Available From</label>
                        <input 
                            type="date" 
                            id="available_from" 
                            name="available_from" 
                            value="<?php echo isset($_POST['available_from']) ? htmlspecialchars($_POST['available_from']) : ''; ?>" 
                            min="<?php echo date('Y-m-d'); ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="min_duration" class="block text-gray-700 font-medium mb-2">Minimum Duration (Months)</label>
                        <select 
                            id="min_duration" 
                            name="min_duration" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="1" <?php echo (isset($_POST['min_duration']) && $_POST['min_duration'] == 1) ? 'selected' : ''; ?>>1 Month</option>
                            <option value="3" <?php echo (isset($_POST['min_duration']) && $_POST['min_duration'] == 3) ? 'selected' : ''; ?>>3 Months</option>
                            <option value="6" <?php echo (isset($_POST['min_duration']) && $_POST['min_duration'] == 6) ? 'selected' : ''; ?>>6 Months</option>
                            <option value="12" <?php echo (isset($_POST['min_duration']) && $_POST['min_duration'] == 12) ? 'selected' : ''; ?>>12 Months</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="max_occupants" class="block text-gray-700 font-medium mb-2">Maximum Occupants</label>
                        <input 
                            type="number" 
                            id="max_occupants" 
                            name="max_occupants" 
                            value="<?php echo isset($_POST['max_occupants']) ? htmlspecialchars($_POST['max_occupants']) : ''; ?>" 
                            min="1" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="furnishing_type" class="block text-gray-700 font-medium mb-2">Furnishing Type</label>
                        <select 
                            id="furnishing_type" 
                            name="furnishing_type" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="">Select Furnishing Type</option>
                            <option value="unfurnished" <?php echo (isset($_POST['furnishing_type']) && $_POST['furnishing_type'] === 'unfurnished') ? 'selected' : ''; ?>>Unfurnished</option>
                            <option value="semi-furnished" <?php echo (isset($_POST['furnishing_type']) && $_POST['furnishing_type'] === 'semi-furnished') ? 'selected' : ''; ?>>Semi-Furnished</option>
                            <option value="fully-furnished" <?php echo (isset($_POST['furnishing_type']) && $_POST['furnishing_type'] === 'fully-furnished') ? 'selected' : ''; ?>>Fully-Furnished</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="property_size" class="block text-gray-700 font-medium mb-2">Property Size (sq ft)</label>
                        <input 
                            type="number" 
                            id="property_size" 
                            name="property_size" 
                            value="<?php echo isset($_POST['property_size']) ? htmlspecialchars($_POST['property_size']) : ''; ?>" 
                            min="0" 
                            step="0.01" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                    </div>
                    
                    <div>
                        <label for="bathroom_count" class="block text-gray-700 font-medium mb-2">Number of Bathrooms</label>
                        <input 
                            type="number" 
                            id="bathroom_count" 
                            name="bathroom_count" 
                            value="<?php echo isset($_POST['bathroom_count']) ? htmlspecialchars($_POST['bathroom_count']) : '1'; ?>" 
                            min="1" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div class="md:col-span-3">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="is_shared_bathroom" 
                                <?php echo (isset($_POST['is_shared_bathroom'])) ? 'checked' : ''; ?> 
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-gray-700">Shared Bathroom</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Amenities -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Amenities</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($amenities as $amenity): ?>
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                            <input 
                                type="checkbox" 
                                name="amenities[]" 
                                value="<?php echo $amenity['id']; ?>" 
                                <?php echo (isset($_POST['amenities']) && in_array($amenity['id'], $_POST['amenities'])) ? 'checked' : ''; ?> 
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-gray-700"><?php echo htmlspecialchars($amenity['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Images -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Images</h3>
                
                <div class="border-dashed border-2 border-gray-300 rounded-lg p-6 text-center">
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        multiple 
                        accept="image/jpeg, image/png, image/jpg" 
                        class="hidden"
                        onchange="displayImagePreviews(this)"
                    >
                    <label for="images" class="cursor-pointer">
                        <div class="text-gray-500 mb-2">
                            <i class="fas fa-cloud-upload-alt text-3xl"></i>
                        </div>
                        <p class="text-gray-700 font-medium mb-1">Click to upload images</p>
                        <p class="text-gray-500 text-sm">Upload up to <?php echo $max_images; ?> images (JPEG, PNG, JPG)</p>
                        <p class="text-gray-500 text-sm">Max size: 5MB per image</p>
                    </label>
                    
                    <div id="image-previews" class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-4">
                        <!-- Image previews will be displayed here -->
                    </div>
                    <div id="image-warning" class="mt-3 text-red-600 hidden"></div>
                </div>
            </div>
            
            <div class="border-t pt-6">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md">
                    Submit Listing
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Display image previews
    function displayImagePreviews(input) {
        const previewContainer = document.getElementById('image-previews');
        const warningContainer = document.getElementById('image-warning');
        previewContainer.innerHTML = '';
        warningContainer.innerHTML = '';
        warningContainer.classList.add('hidden');
        
        const maxImages = <?php echo $max_images; ?>;
        
        if (input.files && input.files.length > 0) {
            if (input.files.length > maxImages) {
                warningContainer.innerHTML = `Warning: You selected ${input.files.length} images, but only the first ${maxImages} will be uploaded.`;
                warningContainer.classList.remove('hidden');
            }
            
            // Show previews for all images (or max allowed)
            for (let i = 0; i < Math.min(input.files.length, maxImages); i++) {
                const file = input.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-32 object-cover rounded-lg';
                    
                    // Add a label if it's the primary image
                    if (i === 0) {
                        const primaryBadge = document.createElement('div');
                        primaryBadge.className = 'absolute top-0 right-0 bg-blue-600 text-white text-xs px-2 py-1 rounded-bl-lg';
                        primaryBadge.textContent = 'Primary';
                        preview.appendChild(primaryBadge);
                    }
                    
                    preview.appendChild(img);
                    previewContainer.appendChild(preview);
                }
                
                reader.readAsDataURL(file);
            }
            
            // If there are more files than allowed, add faded previews
            if (input.files.length > maxImages) {
                for (let i = maxImages; i < Math.min(input.files.length, maxImages + 3); i++) {
                    const preview = document.createElement('div');
                    preview.className = 'relative opacity-50';
                    
                    const placeholder = document.createElement('div');
                    placeholder.className = 'w-full h-32 bg-gray-200 flex items-center justify-center rounded-lg';
                    placeholder.innerHTML = '<i class="fas fa-ban text-gray-400 text-2xl"></i>';
                    
                    const label = document.createElement('div');
                    label.className = 'absolute bottom-0 left-0 right-0 bg-red-600 text-white text-xs px-2 py-1 text-center';
                    label.textContent = 'Exceeds limit';
                    
                    preview.appendChild(placeholder);
                    preview.appendChild(label);
                    previewContainer.appendChild(preview);
                }
                
                if (input.files.length > maxImages + 3) {
                    const more = document.createElement('div');
                    more.className = 'w-full h-32 bg-gray-200 flex items-center justify-center rounded-lg opacity-50';
                    more.innerHTML = `<span class="text-gray-600">+${input.files.length - maxImages - 3} more</span>`;
                    previewContainer.appendChild(more);
                }
            }
        }
    }
</script>

<script>
    // Safe initialization of user menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Prevent redefinition of userMenuButton
        if (typeof userMenuToggle !== 'function') {
            // User menu toggle functionality
            window.userMenuToggle = function() {
                const userMenuDropdown = document.getElementById('user-menu-dropdown');
                if (userMenuDropdown) {
                    userMenuDropdown.classList.toggle('hidden');
                }
            }
            
            // Only attach event listener if it hasn't been attached
            const userMenuButton = document.getElementById('user-menu-button');
            if (userMenuButton && !userMenuButton.dataset.listenerAttached) {
                userMenuButton.addEventListener('click', userMenuToggle);
                userMenuButton.dataset.listenerAttached = 'true';
            }
        }
    });
</script>

<?php
// Include footer
include '../includes/owner_footer.php';
?>

