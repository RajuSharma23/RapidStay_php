<?php
// Start session
session_start();

// Include database connection and access control
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/settings.php");
    exit();
}

// Get current admin data
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM users WHERE id = ? AND user_type = 'admin'";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

// Handle profile update
$profile_updated = false;
$profile_error = '';

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $profile_error = "Name and email are required fields";
    } else {
        // Check if email already exists for another user
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $profile_error = "Email already used by another user";
        } else {
            // Process profile picture upload if exists
            $profile_pic_path = $admin_data['profile_picture']; // Default to current
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $file_name = $_FILES['profile_picture']['name'];
                $file_size = $_FILES['profile_picture']['size'];
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_ext)) {
                    $new_file_name = 'admin_' . $admin_id . '_' . time() . '.' . $file_ext;
                    $upload_path = '../../uploads/profile_pictures/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }
                    
                    if (move_uploaded_file($file_tmp, $upload_path . $new_file_name)) {
                        $profile_pic_path = 'uploads/profile_pictures/' . $new_file_name;
                    }
                }
            }
            
            // Update profile in database
            $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $name, $email, $phone, $profile_pic_path, $admin_id);
            
            if ($stmt->execute()) {
                $profile_updated = true;
                
                // Update session data
                $_SESSION['name'] = $name;
                
                // Refresh admin data
                $result = $conn->query("SELECT * FROM users WHERE id = $admin_id");
                $admin_data = $result->fetch_assoc();
            } else {
                $profile_error = "Failed to update profile: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Handle password change
$password_updated = false;
$password_error = '';

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $admin_data['password'])) {
        $password_error = "Current password is incorrect";
    } else if (strlen($new_password) < 8) {
        $password_error = "New password must be at least 8 characters long";
    } else if ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $admin_id);
        
        if ($stmt->execute()) {
            $password_updated = true;
        } else {
            $password_error = "Failed to update password: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Handle add new admin
$admin_added = false;
$admin_error = '';

if (isset($_POST['add_admin'])) {
    $admin_name = trim($_POST['admin_name']);
    $admin_email = trim($_POST['admin_email']);
    $admin_password = $_POST['admin_password'];
    $admin_phone = trim($_POST['admin_phone']);
    
    // Validate inputs
    if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $admin_error = "Name, email and password are required fields";
    } else if (strlen($admin_password) < 8) {
        $admin_error = "Password must be at least 8 characters long";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $admin_email);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $admin_error = "Email already used by another user";
        } else {
            // Hash password
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $user_type = 'admin';
            
            // Add new admin to database
            $insert_query = "INSERT INTO users (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssss", $admin_name, $admin_email, $hashed_password, $admin_phone, $user_type);
            
            if ($stmt->execute()) {
                $admin_added = true;
            } else {
                $admin_error = "Failed to add new admin: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Include header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - RapidStay</title>
    <link rel="stylesheet" href="../../assets/css//admin-setting.css">
    <style>
        .crop-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        
        .crop-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            max-width: 600px;
            text-align: center;
        }
        
        .crop-preview {
            max-width: 100%;
            max-height: 300px;
            margin: 20px auto;
            display: block;
        }
        
        .crop-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .crop-actions button {
            padding: 8px 16px;
            cursor: pointer;
        }
        
        /* Improved profile pic container */
        .profile-pic-container {
            position: relative;
            width: 150px;  /* Increased size */
            height: 150px; /* Increased size */
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #2c7be5;
        }
        
        .profile-pic-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* This ensures the image covers the area well */
        }
        
        .profile-pic-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #2c7be5;
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .profile-pic-edit:hover {
            background: #1a68d1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-cog fa-fw"></i>
                <span>Admin Dashboard</span>
            </h1>
            <p>Manage your profile, security settings, and administrative controls</p>
        </div>
        
        <?php if ($profile_updated || $password_updated || $admin_added): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i>
            <?php 
                if ($profile_updated) echo "Profile updated successfully";
                if ($password_updated) echo "Password changed successfully";
                if ($admin_added) echo "New admin added successfully";
            ?>
        </div>
        <?php endif; ?>
        
        <div class="settings-container">
            <div class="left-column">
                <!-- Profile Settings -->
                <div class="settings-card">
                    <h2><i class="fas fa-user-circle"></i> Profile Settings</h2>
                    
                    <?php if (!empty($profile_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $profile_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="profile-pic-container">
                            <?php if (!empty($admin_data['profile_picture']) && $admin_data['profile_picture'] != 'NULL'): ?>
                                <img src="../../<?php echo $admin_data['profile_picture']; ?>" alt="Profile Picture">
                            <?php else: ?>
                                <img src="../../assets/images/default-avatar.png" alt="Default Avatar">
                            <?php endif; ?>
                            <label for="profile_picture" class="profile-pic-edit">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" style="display: none;">
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($admin_data['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Password Settings -->
                <div class="settings-card">
                    <h2><i class="fas fa-lock"></i> Change Password</h2>
                    
                    <?php if (!empty($password_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $password_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="right-column">
                <!-- Admin Management -->
                <div class="settings-card">
                    <h2><i class="fas fa-users-cog"></i> Admin Management</h2>
                    
                    <button id="add-admin-btn" class="btn btn-primary" style="width: 100%; margin-bottom: 20px;">
                        <i class="fas fa-user-plus"></i> Add New Admin
                    </button>
                    
                    <div class="admin-list">
                        <h3>Current Admins</h3>
                        
                        <?php
                        // Get all admins
                        $admin_list_query = "SELECT id, name, email, profile_picture FROM users WHERE user_type = 'admin'";
                        $admin_list_result = $conn->query($admin_list_query);
                        
                        if ($admin_list_result && $admin_list_result->num_rows > 0) {
                            while ($admin = $admin_list_result->fetch_assoc()) {
                                $initials = strtoupper(substr($admin['name'], 0, 1));
                                ?>
                                <div class="admin-card">
                                    <div class="admin-avatar">
                                        <?php echo $initials; ?>
                                    </div>
                                    <div class="admin-info">
                                        <div class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                        <div class="admin-email"><?php echo htmlspecialchars($admin['email']); ?></div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p>No admin users found.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Admin Modal -->
    <div id="add-admin-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3><i class="fas fa-user-plus"></i> Add New Admin</h3>
            
            <?php if (!empty($admin_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $admin_error; ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label for="admin_name">Name</label>
                    <input type="text" id="admin_name" name="admin_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Email</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Password</label>
                    <input type="password" id="admin_password" name="admin_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_phone">Phone (Optional)</label>
                    <input type="text" id="admin_phone" name="admin_phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_admin" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-plus-circle"></i> Add Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Crop Modal -->
    <div id="crop-modal" class="crop-modal">
        <div class="crop-modal-content">
            <h3><i class="fas fa-crop-alt"></i> Selected Image</h3>
            <img id="crop-preview" class="crop-preview" alt="Preview">
            <p id="selected-filename"></p>
            <div class="crop-actions">
                <button id="crop-cancel" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button id="crop-confirm" class="btn btn-primary">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
        // Show preview of selected image
        document.getElementById('profile_picture').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const cropModal = document.getElementById('crop-modal');
            const preview = document.getElementById('crop-preview');
            const filenameElement = document.getElementById('selected-filename');
            
            // Display filename
            filenameElement.textContent = 'Selected file: ' + file.name;
            
            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                
                // Show the crop modal
                cropModal.style.display = 'block';
                
                // Also update the profile picture preview
                const profilePreview = document.querySelector('.profile-pic-container img');
                if (profilePreview) {
                    // Store original src to revert if canceled
                    profilePreview.dataset.originalSrc = profilePreview.src;
                    profilePreview.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        });
        
        // Cancel button
        document.getElementById('crop-cancel').addEventListener('click', function() {
            const cropModal = document.getElementById('crop-modal');
            cropModal.style.display = 'none';
            
            // Reset the file input
            document.getElementById('profile_picture').value = '';
            
            // Revert profile picture
            const profilePreview = document.querySelector('.profile-pic-container img');
            if (profilePreview && profilePreview.dataset.originalSrc) {
                profilePreview.src = profilePreview.dataset.originalSrc;
            }
        });
        
        // Confirm button - would normally handle cropping here
        // For now, just close modal and keep the preview
        document.getElementById('crop-confirm').addEventListener('click', function() {
            const cropModal = document.getElementById('crop-modal');
            cropModal.style.display = 'none';
            
            // Keep the current preview (cropping would be implemented here in a production app)
            // The actual image will be uploaded when the form is submitted
        });
        
        // Modal functionality
        const modal = document.getElementById('add-admin-modal');
        const addAdminBtn = document.getElementById('add-admin-btn');
        const closeBtn = document.querySelector('.modal-close');
        
        addAdminBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            // Close admin modal when clicking outside
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            
            // Close crop modal when clicking outside
            const cropModal = document.getElementById('crop-modal');
            if (event.target == cropModal) {
                cropModal.style.display = 'none';
                // Reset the file input
                document.getElementById('profile_picture').value = '';
                
                // Revert profile picture
                const profilePreview = document.querySelector('.profile-pic-container img');
                if (profilePreview && profilePreview.dataset.originalSrc) {
                    profilePreview.src = profilePreview.dataset.originalSrc;
                }
            }
        });
        
        // Only show the admin modal on refresh if there was an admin error
        // and we're explicitly told to show it (not on every page load)
        <?php if (!empty($admin_error) && isset($_POST['add_admin'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            modal.style.display = 'block';
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>