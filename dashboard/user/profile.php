<?php
// Start session for user authentication
session_start();

// Add this near the top of your PHP file, after session_start()
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/profile.php");
    exit();
}

// Check for message in session
if (isset($_SESSION['profile_message'])) {
    $message = $_SESSION['profile_message'];
    unset($_SESSION['profile_message']); // Clear the message
}

// Database connection
require_once '../../includes/db_connect.php';

// Check if location column exists in users table
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'location'");
if(mysqli_num_rows($column_check) == 0) {
    // Column doesn't exist, add it
    $add_column = mysqli_query($conn, "ALTER TABLE users ADD COLUMN location VARCHAR(255) DEFAULT NULL");
    if(!$add_column) {
        die("Error adding location column: " . mysqli_error($conn));
    }
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);
$profile_image = $user['profile_image']; // Default to current image

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Process profile update
        $profile_image = $user['profile_image']; // Default to current image
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            // Check if this is an explicit update from the modal AND the update was triggered by our form
            if (isset($_POST['explicit_image_update']) && $_POST['explicit_image_update'] == '1' && isset($_POST['form_token']) && $_POST['form_token'] == $_SESSION['form_token']) {
                // Process image upload as before
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                    $error = "Only JPG, PNG, and GIF images are allowed.";
                } elseif ($_FILES['profile_image']['size'] > $max_size) {
                    $error = "Image size should be less than 2MB.";
                } else {
                    $upload_dir = '../../uploads/profiles/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = 'user_' . $user_id . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
                    $target_file = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                        // Store consistent path format in database (starting with /)
                        $profile_image = '/uploads/profiles/' . $filename;
                    } else {
                        $error = "Failed to upload image. Please try again.";
                    }
                }
            } else {
                // Skip image processing if not explicitly requested
                // This prevents updates on accidental page refreshes
            }
        }
        
        // Update profile information
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $location = mysqli_real_escape_string($conn, $_POST['location'] ?? ''); // Add this line
        
        // Check if phone number is already used by another user
        $phone_check_query = "SELECT id FROM users WHERE phone = '$phone' AND id != $user_id";
        $phone_check_result = mysqli_query($conn, $phone_check_query);
        
        if (mysqli_num_rows($phone_check_result) > 0) {
            $error = "This phone number is already used by another user.";
        } else {
            if (empty($error)) {
                // Update user profile
                $update_query = "UPDATE users SET 
                                name = '$name', 
                                phone = '$phone', 
                                bio = '$bio', 
                                location = '$location',
                                profile_image = '$profile_image',
                                updated_at = NOW() 
                                WHERE id = $user_id";
                
                if (mysqli_query($conn, $update_query)) {
                    // Update session variables
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_profile_image'] = $profile_image;
                    
                    // Set success message in session
                    $_SESSION['profile_message'] = "Profile updated successfully.";
                    
                    // Redirect to prevent form resubmission on refresh
                    header("Location: profile.php");
                    exit();
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        }
    } 
    else if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters long.";
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET 
                                password = '$hashed_password',
                                updated_at = NOW() 
                                WHERE id = $user_id";
                
                if (mysqli_query($conn, $update_query)) {
                    $message = "Password changed successfully.";
                } else {
                    $error = "Failed to change password. Please try again.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
}

// Include header
include '../includes/user_header.php';
?>

<!-- Main Content -->
<div class="flex-1 p-0 bg-gray-50 overflow-auto">
    <!-- Profile Header -->
    <div class="relative bg-gradient-to-r from-blue-600 to-indigo-800 h-60">
        <!-- Profile Actions -->
        <div class="absolute top-4 right-4 flex gap-3">
            <button onclick="openProfileModal()" class="bg-white/20 hover:bg-white/30 text-white rounded-lg px-4 py-2 flex items-center transition-all">
                <i class="fas fa-edit mr-2"></i> Edit Profile
            </button>
            
        </div>
        
        <!-- Profile Photo & Name -->
        <div class="absolute bottom-0 left-0 transform translate-y-1/2 ml-8 flex items-end">
            <div class="relative">
                <!-- Profile Image -->
                <div class="w-32 h-32 rounded-xl border-4 border-white bg-white shadow-xl overflow-hidden">
                    <?php if (!empty($user['profile_image'])): ?>
                        <?php
                        // Fix path for profile image
                        $profile_img = $user['profile_image'];
                        
                        // Properly handle different path formats
                        if (filter_var($profile_img, FILTER_VALIDATE_URL)) {
                            // If it's already a full URL, leave it as is
                        } else if (strpos($profile_img, '/uploads/') === 0) {
                            // If it starts with /uploads/, add the site root
                            $profile_img = '../..' . $profile_img;
                        } else if (strpos($profile_img, 'uploads/') === 0) {
                            // If it starts with uploads/ without leading slash
                            $profile_img = '../../' . $profile_img;
                        } else {
                            // Otherwise assume it's just a filename
                            $profile_img = '../../uploads/profiles/' . $profile_img;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-blue-600 text-white text-5xl">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upload photo button -->
                <form id="profileImageForm" action="" method="POST" enctype="multipart/form-data" class="hidden">
                    <input type="hidden" name="update_profile" value="1">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    <input type="hidden" name="bio" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>">
                    <input type="file" id="profile_image_upload" name="profile_image" class="hidden" accept="image/jpeg,image/png,image/gif">
                </form>

                <label for="profile_image_upload" class="absolute bottom-1 right-1 w-8 h-8 bg-blue-600 hover:bg-blue-700 rounded-full flex items-center justify-center text-white cursor-pointer shadow-md transition-all">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            
            <div class="ml-4  text-black">
                <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="flex items-center text-blue-500">
                    <i class="fas fa-map-marker-alt mr-2"></i> 
                    <?php echo !empty($user['location']) ? htmlspecialchars($user['location']) : 'Location not set'; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Profile Body -->
    <div class="mt-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: User Info Card -->
            <div class="space-y-6">
                <!-- Contact & Basic Info Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-lg text-gray-800">Contact Information</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="font-medium"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided'; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Member Since</p>
                                <p class="font-medium"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Status Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-lg text-gray-800">Account Status</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Account Type</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <?php echo ucfirst($user['user_type']); ?>
                            </span>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-gray-600">Status</span>
                            <?php if ($user['is_active']): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Profile Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Bio Section -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-lg text-gray-800">About Me</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($user['bio'])): ?>
                            <p class="text-gray-600 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <div class="text-right mt-4">
                                
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="text-gray-400 mb-3">
                                    <i class="fas fa-user-edit text-3xl"></i>
                                </div>
                                <p class="text-gray-500 mb-4">Your bio is empty. Tell others about yourself!</p>
                                <button onclick="openBioModal('')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    Add Bio
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Activity Stats -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-lg text-gray-800">Activity Stats</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo $wishlist_count ?? 0; ?></div>
                                <div class="text-gray-600">Wishlist Items</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-1"><?php echo $bookings_count ?? 0; ?></div>
                                <div class="text-gray-600">Bookings</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 text-center">
                                <div class="text-3xl font-bold text-green-600 mb-1"><?php echo $reviews_count ?? 0; ?></div>
                                <div class="text-gray-600">Reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-lg text-gray-800">Security Settings</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-800">Password</h3>
                                <p class="text-gray-500 text-sm">Last changed: <?php echo date('M d, Y', strtotime($user['updated_at'])); ?></p>
                            </div>
                            <button onclick="openPasswordModal()" class="px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                Change Password
                            </button>
                        </div>
                        
                        <div class="border-t pt-6 flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-800">Two-Factor Authentication</h3>
                                <p class="text-gray-500 text-sm">Add an extra layer of security to your account</p>
                            </div>
                            <form action="toggle_2fa.php" method="post" id="twoFactorForm">
                                <input type="hidden" name="toggle_2fa" value="1">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enable_2fa" class="sr-only peer" id="twoFactorToggle" 
                                          <?php echo (!empty($user['two_factor_enabled'])) ? 'checked' : ''; ?>>
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 
                                               peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full 
                                               rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white 
                                               after:content-[''] after:absolute after:top-[2px] after:start-[2px] 
                                               after:bg-white after:border-gray-300 after:border after:rounded-full 
                                               after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Image Change Modal -->
<div id="imageChangeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Change Profile Picture</h3>
            <button onclick="closeImageModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div class="flex flex-col items-center">
                <!-- Image Preview -->
                <div class="w-32 h-32 rounded-xl border-2 border-blue-300 bg-white shadow overflow-hidden mb-4">
                    <div id="imagePreview" class="w-full h-full flex items-center justify-center bg-gray-100">
                        <i class="fas fa-image text-gray-400 text-4xl"></i>
                    </div>
                </div>
                
                <!-- File Input Button -->
                <label class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer mb-3">
                    <i class="fas fa-upload mr-2"></i> Choose Image
                    <input type="file" id="modal_profile_image" class="hidden" accept="image/jpeg,image/png,image/gif">
                </label>
                
                <p class="text-sm text-gray-500 text-center">
                    Supported formats: JPEG, PNG, GIF<br>
                    Max size: 2MB
                </p>
            </div>
        </div>
        
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end rounded-b-xl">
            <button type="button" onclick="closeImageModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 mr-2">
                Cancel
            </button>
            <button type="button" id="saveImageButton" onclick="saveProfileImage()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" disabled>
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Edit Profile</h3>
            <button onclick="closeProfileModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editProfileForm" method="POST" action="" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea id="bio" name="bio" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end rounded-b-xl">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 mr-2">
                    Cancel
                </button>
                <input type="hidden" name="update_profile" value="1">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bio Edit Modal -->
<div id="bioModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Edit Your Bio</h3>
            <button onclick="closeBioModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="bioForm" method="POST" action="">
            <div class="p-6">
                <textarea id="bioText" name="bio" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i> 
                    Your bio will be visible to other users on the platform.
                </p>
            </div>
            
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end rounded-b-xl">
                <button type="button" onclick="closeBioModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 mr-2">
                    Cancel
                </button>
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">Change Password</h3>
            <button onclick="closePasswordModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <div class="p-6 space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <p class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Password must be at least 8 characters long.
                </p>
            </div>
            
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end rounded-b-xl">
                <button type="button" onclick="closePasswordModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 mr-2">
                    Cancel
                </button>
                <input type="hidden" name="change_password" value="1">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Other existing script functions remain unchanged...

// Add these new functions for image upload
let selectedImageFile = null;

// Open image modal when camera icon is clicked
document.querySelector('label[for="profile_image_upload"]').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent the default action (opening file dialog)
    openImageModal();
    return false;
});

function openImageModal() {
    // Reset the modal state
    document.getElementById('imagePreview').innerHTML = '<i class="fas fa-image text-gray-400 text-4xl"></i>';
    document.getElementById('saveImageButton').disabled = true;
    selectedImageFile = null;
    
    // Show modal
    document.getElementById('imageChangeModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeImageModal() {
    document.getElementById('imageChangeModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Reset file input
    document.getElementById('modal_profile_image').value = '';
}

// Handle image selection in modal
document.getElementById('modal_profile_image').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const file = e.target.files[0];
        
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('Image size should be less than 2MB.');
            return;
        }
        
        // Check file type
        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
            alert('Only JPG, PNG, and GIF images are allowed.');
            return;
        }
        
        selectedImageFile = file;
        
        // Preview the image
        const reader = new FileReader();
        reader.onload = function(event) {
            const imagePreview = document.getElementById('imagePreview');
            imagePreview.innerHTML = ''; // Clear existing content
            
            const img = document.createElement('img');
            img.src = event.target.result;
            img.className = 'w-full h-full object-cover';
            imagePreview.appendChild(img);
            
            // Enable save button
            document.getElementById('saveImageButton').disabled = false;
        };
        reader.readAsDataURL(file);
    }
});

// Save profile image when button is clicked
function saveProfileImage() {
    if (!selectedImageFile) {
        return;
    }
    
    console.log("Uploading image: ", selectedImageFile.name);
    
    // Create loading state
    document.getElementById('saveImageButton').innerHTML = '<div class="flex items-center"><div class="animate-spin h-4 w-4 border-2 border-white rounded-full border-t-transparent mr-2"></div> Saving...</div>';
    document.getElementById('saveImageButton').disabled = true;
    
    // Create a traditional form for more reliable file upload
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.enctype = 'multipart/form-data';
    tempForm.style.display = 'none';
    tempForm.action = window.location.href; // Submit to the current page
    document.body.appendChild(tempForm);
    
    // Add an explicit flag to indicate this is a user-initiated update
    const flagInput = document.createElement('input');
    flagInput.type = 'hidden';
    flagInput.name = 'explicit_image_update'; 
    flagInput.value = '1';
    tempForm.appendChild(flagInput);
    
    // Create hidden inputs for all form data
    const nameInput = document.createElement('input');
    nameInput.type = 'hidden';
    nameInput.name = 'name';
    nameInput.value = '<?php echo addslashes(htmlspecialchars($user['name'])); ?>';
    tempForm.appendChild(nameInput);
    
    // Continue with the rest of your form data...
    const phoneInput = document.createElement('input');
    phoneInput.type = 'hidden';
    phoneInput.name = 'phone';
    phoneInput.value = '<?php echo addslashes(htmlspecialchars($user['phone'] ?? '')); ?>';
    tempForm.appendChild(phoneInput);
    
    // Add location field
    const locationInput = document.createElement('input');
    locationInput.type = 'hidden';
    locationInput.name = 'location';
    locationInput.value = '<?php echo addslashes(htmlspecialchars($user['location'] ?? '')); ?>';
    tempForm.appendChild(locationInput);
    
    const bioInput = document.createElement('input');
    bioInput.type = 'hidden';
    bioInput.name = 'bio';
    bioInput.value = '<?php echo addslashes(htmlspecialchars($user['bio'] ?? '')); ?>';
    tempForm.appendChild(bioInput);
    
    const updateInput = document.createElement('input');
    updateInput.type = 'hidden';
    updateInput.name = 'update_profile';
    updateInput.value = '1';
    tempForm.appendChild(updateInput);
    
    // Add the missing form token
    const formTokenInput = document.createElement('input');
    formTokenInput.type = 'hidden';
    formTokenInput.name = 'form_token';
    formTokenInput.value = '<?php echo isset($_SESSION["form_token"]) ? $_SESSION["form_token"] : "" ?>';
    tempForm.appendChild(formTokenInput);
    
    // Create a file input with the selected file
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.name = 'profile_image';
    
    // Use DataTransfer to set the file
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(selectedImageFile);
    fileInput.files = dataTransfer.files;
    
    tempForm.appendChild(fileInput);
    
    // Submit the form
    tempForm.submit();
}

// Close modal when clicking outside of it
document.getElementById('imageChangeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// Add success message handling if not already present
<?php if (!empty($message)): ?>
    // Show success notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 animate-fade-in-down';
    notification.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-3"></i><?php echo $message; ?></div>';
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('animate-fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
<?php endif; ?>

// Fix for Edit Profile modal
function openProfileModal() {
    // Check if the modal exists
    const modal = document.getElementById('profileModal');
    if (!modal) {
        console.error('Profile modal not found');
        return;
    }
    
    // Make sure the form fields have the latest values
    document.getElementById('name').value = '<?php echo addslashes(htmlspecialchars($user['name'])); ?>';
    document.getElementById('phone').value = '<?php echo addslashes(htmlspecialchars($user['phone'] ?? '')); ?>';
    document.getElementById('bio').value = '<?php echo addslashes(htmlspecialchars($user['bio'] ?? '')); ?>';
    
    if (document.getElementById('location')) {
        document.getElementById('location').value = '<?php echo addslashes(htmlspecialchars($user['location'] ?? '')); ?>';
    }
    
    // Show the modal
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// Add click handler for Edit Profile button
document.addEventListener('DOMContentLoaded', function() {
    // Make sure buttons work
    const editProfileButtons = document.querySelectorAll('[onclick="openProfileModal()"]');
    editProfileButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            openProfileModal();
        });
    });
    
    // Make close buttons work
    const closeModalButtons = document.querySelectorAll('[onclick="closeProfileModal()"]');
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            closeProfileModal();
        });
    });
    
    // Close modal when clicking outside
    const profileModal = document.getElementById('profileModal');
    if (profileModal) {
        profileModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });
    }
    
    // Remove automatic form submission behavior
    const profileImageUpload = document.getElementById('profile_image_upload');
    if (profileImageUpload) {
        // Remove any existing event listeners by cloning and replacing
        const newProfileImageUpload = profileImageUpload.cloneNode(true);
        profileImageUpload.parentNode.replaceChild(newProfileImageUpload, profileImageUpload);
    }
    
    // Make sure camera icon only opens modal without submitting form
    const cameraIcon = document.querySelector('label[for="profile_image_upload"]');
    if (cameraIcon) {
        // Remove existing listeners by cloning and replacing
        const newCameraIcon = cameraIcon.cloneNode(true);
        cameraIcon.parentNode.replaceChild(newCameraIcon, cameraIcon);
        
        // Add the correct event listener
        newCameraIcon.addEventListener('click', function(e) {
            e.preventDefault();
            openImageModal();
            return false;
        });
    }
    
    // Ensure only the modal's save button can trigger profile updates
    const saveImageButton = document.getElementById('saveImageButton');
    if (saveImageButton) {
        saveImageButton.onclick = saveProfileImage;
    }

    // Add change password button handler
    const changePasswordBtn = document.querySelector('button.px-4.py-2.border.border-blue-600.text-blue-600');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openPasswordModal();
        });
    }
});

// Add these new functions for password change
function openPasswordModal() {
    // Check if the modal exists
    const modal = document.getElementById('passwordModal');
    if (!modal) {
        console.error('Password modal not found');
        return;
    }
    
    // Clear any existing values
    document.getElementById('current_password').value = '';
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    
    // Show the modal
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}
</script>

<style>
/* Add these animation styles if not already present */
.animate-fade-in-down {
    animation: fadeInDown 0.5s ease forwards;
}
.animate-fade-out {
    animation: fadeOut 0.5s ease forwards;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}
</style>

<?php
// Include footer
include '../includes/user_footer.php';
?>

