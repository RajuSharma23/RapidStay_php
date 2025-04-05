<?php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/profile.php");
    exit();
}

// Initialize variables
$updateMessage = "";
$userData = null;

// Get user data from database
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    $updateMessage = "User not found!";
}

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $updateMessage = "Name and email are required fields!";
    } else {
        // Update user data in database
        $updateSql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssi", $name, $email, $phone, $userId);
        
        if ($updateStmt->execute()) {
            $updateMessage = "Profile updated successfully!";
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
        } else {
            $updateMessage = "Error updating profile: " . $conn->error;
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Check if current password is correct
    $passwordSql = "SELECT password FROM users WHERE id = ?";
    $passwordStmt = $conn->prepare($passwordSql);
    $passwordStmt->bind_param("i", $userId);
    $passwordStmt->execute();
    $passwordResult = $passwordStmt->get_result();
    $userPassword = $passwordResult->fetch_assoc();
    
    // Verify passwords match and current password is correct
    if (password_verify($currentPassword, $userPassword['password'])) {
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password in database
            $updatePasswordSql = "UPDATE users SET password = ? WHERE id = ?";
            $updatePasswordStmt = $conn->prepare($updatePasswordSql);
            $updatePasswordStmt->bind_param("si", $hashedPassword, $userId);
            
            if ($updatePasswordStmt->execute()) {
                $updateMessage = "Password changed successfully!";
            } else {
                $updateMessage = "Error changing password: " . $conn->error;
            }
        } else {
            $updateMessage = "New passwords do not match!";
        }
    } else {
        $updateMessage = "Current password is incorrect!";
    }
}

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_picture'])) {
    // Create uploads directory
    $targetDir = "../../uploads/profile_pictures/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $fileName = basename($_FILES["profile_picture"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "profile_" . $userId . "_" . time() . "." . $fileType;
        $targetFilePath = $targetDir . $newFileName;
        
        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array($fileType, $allowTypes)) {
            // Delete old profile picture if exists
            if (!empty($userData['profile_picture'])) {
                $oldFile = $_SERVER['DOCUMENT_ROOT'] . '/Rapidstay1/' . $userData['profile_picture'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                // Store relative path in database
                $dbFilePath = "uploads/profile_pictures/" . $newFileName;
                $updatePictureSql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $updatePictureStmt = $conn->prepare($updatePictureSql);
                $updatePictureStmt->bind_param("si", $dbFilePath, $userId);
                
                if ($updatePictureStmt->execute()) {
                    $updateMessage = "Profile picture uploaded successfully!";
                    // Refresh user data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $userData = $result->fetch_assoc();
                } else {
                    $updateMessage = "Error updating profile picture in database.";
                }
            } else {
                $updateMessage = "Error uploading file.";
            }
        } else {
            $updateMessage = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $updateMessage = "Please select a file to upload.";
    }
}
// Include header
include '../includes/owner_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Management</title>
    <style>
        
        
        .container {
            max-width: 1200px;
           
            margin-left:250px;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            /* margin-bottom: -200px; */

        }
        .owner-container{
            margin: 0 auto;

        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            margin-top:50px;
        }
        .profile-picture-container {
            margin-right: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f4f4f4;
            background-color: #f8f9fa;
        }
        .default-profile {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #777;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
        }
        h1 {
            color: #2c3e50;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            margin-right: 10px;
        }
        .tab.active {
            border-bottom: 2px solid #3498db;
            color: #3498db;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .profile-picture-upload {
            margin: 20px 0;
        }
        .profile-picture-upload input[type="file"] {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="owner-container">
            <?php if (!empty($updateMessage)): ?>
                <div class="message <?php echo strpos($updateMessage, "successfully") !== false ? 'success' : 'error'; ?>" id="statusMessage">
                    <?php echo $updateMessage; ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-picture-container">
                    <?php if (isset($userData['profile_picture']) && !empty($userData['profile_picture'])): ?>
                        <img src="/Rapidstay1/<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                            alt="" 
                            class="profile-picture" 
                            onerror="this.onerror=null; this.src='/Rapidstay1/assets/images/default-profile.png';">
                    <?php else: ?>
                        <div class="default-profile">
                            <?php echo htmlspecialchars(substr($userData['name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($userData['name'] ?? 'User Profile'); ?></h1>
                    <p><?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active" data-tab="profile">Profile Information</div>
                <div class="tab" data-tab="password">Change Password</div>
                <div class="tab" data-tab="picture">Profile Picture</div>
            </div>

            <div class="tab-content active" id="profile">
                <h2>Edit Profile</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>

            <div class="tab-content" id="password">
                <h2>Change Password</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>
            </div>

            <div class="tab-content" id="picture">
                <h2>Update Profile Picture</h2>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="form-group profile-picture-upload">
                        <label for="profile_picture">Select Image:</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif" required>
                        <p>Allowed file types: JPG, JPEG, PNG, GIF</p>
                    </div>
                    <button type="submit" name="upload_picture" class="btn">Upload Picture</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Message auto-hide functionality
        const statusMessage = document.getElementById('statusMessage');
        if (statusMessage) {
            setTimeout(() => {
                statusMessage.classList.add('fade-out');
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 500);
            }, 3000); // Message will stay for 3 seconds
        }
    </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>