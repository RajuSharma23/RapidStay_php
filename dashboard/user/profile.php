<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/profile.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        
        // Check if phone number is already used by another user
        $phone_check_query = "SELECT id FROM users WHERE phone = '$phone' AND id != $user_id";
        $phone_check_result = mysqli_query($conn, $phone_check_query);
        
        if (mysqli_num_rows($phone_check_result) > 0) {
            $error = "This phone number is already used by another user.";
        } else {
            // Handle profile image upload
            $profile_image = $user['profile_image']; // Default to current image
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
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
                        $profile_image = '/uploads/profiles/' . $filename;
                    } else {
                        $error = "Failed to upload image. Please try again.";
                    }
                }
            }
            
            if (empty($error)) {
                // Update user profile
                $update_query = "UPDATE users SET 
                                name = '$name', 
                                phone = '$phone', 
                                bio = '$bio', 
                                profile_image = '$profile_image',
                                updated_at = NOW() 
                                WHERE id = $user_id";
                
                if (mysqli_query($conn, $update_query)) {
                    // Update session variables
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_profile_image'] = $profile_image;
                    
                    $message = "Profile updated successfully.";
                    
                    // Refresh user data
                    $user_result = mysqli_query($conn, $user_query);
                    $user = mysqli_fetch_assoc($user_result);
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
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
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">My Profile</h1>
        <p class="text-gray-600">Manage your personal information and account settings</p>
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
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Profile Information -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Profile Information</h2>
                </div>
                
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-6">
                        <label for="profile_image" class="block text-gray-700 font-medium mb-2">Profile Picture</label>
                        <div class="flex items-center">
                            <div class="mr-4">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-24 h-24 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-4xl">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    id="profile_image" 
                                    name="profile_image" 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    accept="image/jpeg, image/png, image/gif"
                                >
                                <p class="text-xs text-gray-500 mt-1">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
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
                    
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            class="w-full px-4 py-2 border rounded-lg bg-gray-100" 
                            disabled
                        >
                        <p class="text-xs text-gray-500 mt-1">Email address cannot be changed</p>
                    </div>
                    
                    <div class="mb-4">
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
                    
                    <div class="mb-6">
                        <label for="bio" class="block text-gray-700 font-medium mb-2">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            rows="4" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Tell us a bit about yourself</p>
                    </div>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Settings -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Account Settings</h2>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Account Type</h3>
                        <div class="px-3 py-2 bg-blue-100 text-blue-800 rounded-md inline-block">
                            <?php echo ucfirst($user['user_type']); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Member Since</h3>
                        <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Account Status</h3>
                        <?php if ($user['is_active']): ?>
                            <div class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm inline-block">
                                Active
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm inline-block">
                                Inactive
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Change Password</h2>
                </div>
                
                <form action="profile.php" method="POST" class="p-6">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-700 font-medium mb-2">Current Password</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            required
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-700 font-medium mb-2">New Password</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm New Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md w-full">
                        Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/user_footer.php';
?>

