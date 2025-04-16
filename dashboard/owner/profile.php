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
            margin-left: 50px;
            margin-top: 20px;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            border-top: 4px solid #4c57ef;
        }
        
        .owner-container {
            margin: 0 auto;
            
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            margin-top: 20px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .profile-picture-container {
            margin-right: 30px;
            position: relative;
        }
        
        .profile-picture {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f4f4f4;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .profile-picture:hover {
            transform: scale(1.03);
        }
        
        .default-profile {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4c57ef, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
        }
        
        .profile-info h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .profile-info p {
            color: #64748b;
            font-size: 16px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            gap: 8px;
        }
        
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            margin-right: 5px;
            font-weight: 600;
            color: #64748b;
            border-radius: 6px 6px 0 0;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab:hover {
            color: #4c57ef;
            background-color: #f8fafc;
        }
        
        .tab.active {
            border-bottom: 3px solid #4c57ef;
            color: #4c57ef;
            background-color: #f8fafc;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
            padding: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-content h2 {
            margin-bottom: 20px;
            color: #334155;
            font-weight: 600;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        input[type="password"]:focus {
            border-color: #4c57ef;
            box-shadow: 0 0 0 3px rgba(76, 87, 239, 0.1);
            outline: none;
        }
        
        .btn {
            background: linear-gradient(to right, #4c57ef, #3b82f6);
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(76, 87, 239, 0.12);
        }
        
        .btn:hover {
            background: linear-gradient(to right, #3b45d9, #2563eb);
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(76, 87, 239, 0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            position: relative;
            animation: slideIn 0.4s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .profile-picture-upload {
            margin: 20px 0;
        }
        
        .profile-picture-upload input[type="file"] {
            border: 2px dashed #cbd5e1;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 15px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-picture-upload input[type="file"]:hover {
            border-color: #4c57ef;
            background: #f1f5f9;
        }
        
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container overflow-auto">
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
                <div class="tab active" data-tab="profile">
                    <i class="fas fa-user"></i> Profile Information
                </div>
                <div class="tab" data-tab="password">
                    <i class="fas fa-key"></i> Change Password
                </div>
                <div class="tab" data-tab="picture">
                    <i class="fas fa-camera"></i> Profile Picture
                </div>
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