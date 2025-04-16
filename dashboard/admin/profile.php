<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/profile.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if user_preferences table exists, create if not
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_preferences'");
if(mysqli_num_rows($table_check) == 0) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `user_preferences` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `email_notifications` tinyint(1) NOT NULL DEFAULT '1',
        `browser_notifications` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if(!mysqli_query($conn, $create_table_sql)) {
        // Log the error but don't show it to user
        error_log("Error creating user_preferences table: " . mysqli_error($conn));
    }
}

// Get user details
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $error = "User not found";
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        
        // Validate inputs
        if (empty($name)) {
            $error = "Name cannot be empty";
        } elseif (empty($email)) {
            $error = "Email cannot be empty";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email is already in use by another user
            $email_check_query = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
            $email_check_result = mysqli_query($conn, $email_check_query);
            
            if (mysqli_num_rows($email_check_result) > 0) {
                $error = "Email is already in use by another account";
            } else {
                // Update user profile
                $update_query = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', updated_at = NOW() WHERE id = $user_id";
                
                if (mysqli_query($conn, $update_query)) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $message = "Profile updated successfully";
                    
                    // Refresh user data
                    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                    $user = mysqli_fetch_assoc($result);
                } else {
                    $error = "Error updating profile: " . mysqli_error($conn);
                }
            }
        }
    }
    
    // Handle password change
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($current_password)) {
            $error = "Current password is required";
        } elseif (empty($new_password)) {
            $error = "New password is required";
        } elseif ($new_password != $confirm_password) {
            $error = "New passwords do not match";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters long";
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE id = $user_id";
                
                if (mysqli_query($conn, $update_query)) {
                    $message = "Password changed successfully";
                } else {
                    $error = "Error changing password: " . mysqli_error($conn);
                }
            } else {
                $error = "Current password is incorrect";
            }
        }
    }
    
    // Handle profile image upload
    elseif (isset($_POST['upload_image'])) {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                $error = "Only JPG, JPEG and PNG files are allowed";
            } elseif ($_FILES['profile_image']['size'] > $max_size) {
                $error = "File size must be less than 5MB";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = "../../uploads/profile/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = 'profile_' . $user_id . '_' . time() . '_' . $_FILES['profile_image']['name'];
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Get the path relative to the web root
                    $profile_image = '/Rapidstay1/uploads/profile/' . $filename;
                    
                    // Update user profile
                    $update_query = "UPDATE users SET profile_image = '$profile_image', updated_at = NOW() WHERE id = $user_id";
                    
                    if (mysqli_query($conn, $update_query)) {
                        $_SESSION['profile_image'] = $profile_image;
                        $message = "Profile image updated successfully";
                        
                        // Refresh user data
                        $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                        $user = mysqli_fetch_assoc($result);
                    } else {
                        $error = "Error updating profile image: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Error uploading image";
                }
            }
        } else {
            $error = "No image file selected or upload error occurred";
        }
    }
    
    // Handle notification preferences
    elseif (isset($_POST['notification_preferences'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $browser_notifications = isset($_POST['browser_notifications']) ? 1 : 0;
        
        $update_query = "UPDATE user_preferences SET 
                        email_notifications = $email_notifications, 
                        browser_notifications = $browser_notifications, 
                        updated_at = NOW() 
                        WHERE user_id = $user_id";
        
        // Check if preferences record exists
        $check_query = "SELECT id FROM user_preferences WHERE user_id = $user_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing preferences
            if (mysqli_query($conn, $update_query)) {
                $message = "Notification preferences updated successfully";
            } else {
                $error = "Error updating notification preferences: " . mysqli_error($conn);
            }
        } else {
            // Create new preferences record
            $insert_query = "INSERT INTO user_preferences (user_id, email_notifications, browser_notifications, created_at) 
                           VALUES ($user_id, $email_notifications, $browser_notifications, NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                $message = "Notification preferences saved successfully";
            } else {
                $error = "Error saving notification preferences: " . mysqli_error($conn);
            }
        }
    }
}

// Get user notification preferences if table exists
$preferences = [];
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_preferences'");
if(mysqli_num_rows($table_check) > 0) {
    $preferences_query = "SELECT * FROM user_preferences WHERE user_id = $user_id";
    $preferences_result = mysqli_query($conn, $preferences_query);
    if($preferences_result) {
        $preferences = mysqli_fetch_assoc($preferences_result);
    }
}

// Include header
include '../includes/admin_header.php';
?>

<div class="flex-1 p-8 overflow-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <h1 class="text-2xl font-bold text-primary">My Profile</h1>
        <a href="index.php" class="mt-3 md:mt-0 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Overview -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Profile Overview</h2>
                </div>
                
                <div class="p-6 flex flex-col items-center">
                    <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-200 mb-4">
                        <?php if (isset($user['profile_image']) && !empty($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-primary bg-opacity-20 flex items-center justify-center">
                                <i class="fas fa-user text-primary text-5xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($user['user_type']); ?></p>
                    
                    <div class="w-full space-y-2 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-primary w-6"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if (!empty($user['phone'])): ?>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-white w-6"></i>
                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-primary w-6"></i>
                            <span>Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <button type="button" onclick="document.getElementById('upload-photo-form').classList.toggle('hidden')" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md w-full">
                        <i class="fas fa-camera mr-2"></i> Change Photo
                    </button>
                    
                    <!-- Hidden Photo Upload Form -->
                    <form id="upload-photo-form" class="hidden mt-4 w-full" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Select new profile picture</label>
                            <input type="file" name="profile_image" class="w-full" required>
                            <p class="text-xs text-gray-500 mt-1">JPG, JPEG or PNG. Max 5MB.</p>
                        </div>
                        <button type="submit" name="upload_image" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md w-full">
                            Upload Image
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Activity Log -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Recent Activity</h2>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="border-b pb-2">
                            <div class="text-sm font-medium">Login from new device</div>
                            <div class="text-xs text-gray-500">2 days ago</div>
                        </div>
                        <div class="border-b pb-2">
                            <div class="text-sm font-medium">Password changed</div>
                            <div class="text-xs text-gray-500">1 week ago</div>
                        </div>
                        <div class="border-b pb-2">
                            <div class="text-sm font-medium">Profile updated</div>
                            <div class="text-xs text-gray-500">2 weeks ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Settings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information Form -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Personal Information</h2>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="profile.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    value="<?php echo htmlspecialchars($user['name']); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label for="email" class="block mb-1 text-sm font-medium text-gray-700">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label for="phone" class="block mb-1 text-sm font-medium text-gray-700">Phone Number</label>
                                <input 
                                    type="text" 
                                    id="phone" 
                                    name="phone" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                >
                            </div>
                            <div>
                                <label for="user_type" class="block mb-1 text-sm font-medium text-gray-700">Role</label>
                                <input 
                                    type="text" 
                                    id="user_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
                                    value="<?php echo htmlspecialchars(ucfirst($user['user_type'])); ?>"
                                    readonly
                                >
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="mt-6 bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-md">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Change Password</h2>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="profile.php">
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block mb-1 text-sm font-medium text-gray-700">Current Password</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    required
                                >
                            </div>
                            <div>
                                <label for="new_password" class="block mb-1 text-sm font-medium text-gray-700">New Password</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block mb-1 text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                    required
                                >
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="mt-6 bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-md">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Notification Preferences -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Notification Preferences</h2>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="profile.php">
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="email_notifications" 
                                    name="email_notifications" 
                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    <?php echo (!empty($preferences) && $preferences['email_notifications'] == 1) ? 'checked' : ''; ?>
                                >
                                <label for="email_notifications" class="ml-2 block text-sm text-gray-700">Email Notifications</label>
                            </div>
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="browser_notifications" 
                                    name="browser_notifications" 
                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    <?php echo (!empty($preferences) && $preferences['browser_notifications'] == 1) ? 'checked' : ''; ?>
                                >
                                <label for="browser_notifications" class="ml-2 block text-sm text-gray-700">Browser Notifications</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="notification_preferences" class="mt-6 bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-md">
                            Save Preferences
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Two Factor Authentication -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-primary">
                    <h2 class="font-bold text-white">Two-Factor Authentication</h2>
                </div>
                
                <div class="p-6">
                    <p class="text-gray-600 mb-4">Enhance your account security by enabling two-factor authentication.</p>
                    
                    <div class="flex items-center justify-between p-4 border rounded-lg bg-gray-50">
                        <div>
                            <h3 class="font-medium">Two-Factor Authentication</h3>
                            <p class="text-sm text-gray-500">Not currently enabled</p>
                        </div>
                        <button type="button" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md">
                            Setup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password validation
document.getElementById('new_password').addEventListener('input', function() {
    const newPassword = this.value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword.length < 8) {
        this.setCustomValidity('Password must be at least 8 characters long');
    } else {
        this.setCustomValidity('');
    }
    
    if (confirmPassword && confirmPassword !== newPassword) {
        document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
    } else {
        document.getElementById('confirm_password').setCustomValidity('');
    }
});

document.getElementById('confirm_password').addEventListener('input', function() {
    const confirmPassword = this.value;
    const newPassword = document.getElementById('new_password').value;
    
    if (newPassword && confirmPassword !== newPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>