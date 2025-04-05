<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/user/roommates.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user details using prepared statement
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Check if roommates table exists
$table_exists = false;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'roommates'");
if (mysqli_num_rows($table_check) > 0) {
    $table_exists = true;
}

// Initialize variables
$is_listed = false;
$roommate_data = null;
$error = '';
$message = '';

if ($table_exists) {
    // Check if user is already listed as a roommate
    $check_query = "SELECT * FROM roommates WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $is_listed = mysqli_num_rows($check_result) > 0;
    $roommate_data = $is_listed ? mysqli_fetch_assoc($check_result) : null;
} else {
    // Table doesn't exist - show error
    $error = "The roommates feature is currently unavailable. Please contact the administrator.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table_exists) {
    if (isset($_POST['list_as_roommate'])) {
        // Validate and sanitize input with default values
        $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
        $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
        $occupation = isset($_POST['occupation']) ? mysqli_real_escape_string($conn, $_POST['occupation']) : '';
        $budget = isset($_POST['budget']) ? intval($_POST['budget']) : 0;
        $preferred_location = isset($_POST['preferred_location']) ? mysqli_real_escape_string($conn, $_POST['preferred_location']) : '';
        $move_in_date = isset($_POST['move_in_date']) ? mysqli_real_escape_string($conn, $_POST['move_in_date']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
        $preferences = isset($_POST['preferences']) ? mysqli_real_escape_string($conn, $_POST['preferences']) : '';

        // Initialize errors array
        $errors = [];

        // Validate all required fields
        if (empty($gender)) $errors[] = "Gender is required";
        if ($age < 18) $errors[] = "You must be at least 18 years old";
        if (empty($occupation)) $errors[] = "Occupation is required";
        if ($budget <= 0) $errors[] = "Valid budget is required";
        if (empty($preferred_location)) $errors[] = "Preferred location is required";
        if (empty($move_in_date)) $errors[] = "Move-in date is required";
        if ($duration <= 0) $errors[] = "Valid duration is required";
        if (empty($description)) $errors[] = "Description is required";
        if (empty($preferences)) $errors[] = "Preferences are required";

        // Display errors if any
        if (!empty($errors)) {
            $error = "Please fix the following errors:<br>" . implode("<br>", $errors);
        } else {
            if ($is_listed) {
                // Update using prepared statement
                $update_query = "UPDATE roommates SET 
                    gender = ?, 
                    age = ?, 
                    occupation = ?, 
                    budget = ?, 
                    preferred_location = ?, 
                    move_in_date = ?, 
                    duration = ?, 
                    description = ?, 
                    preferences = ?, 
                    updated_at = NOW() 
                    WHERE user_id = ?";
                
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "sisississi", 
                    $gender, $age, $occupation, $budget, 
                    $preferred_location, $move_in_date, $duration, 
                    $description, $preferences, $user_id
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Your roommate listing has been updated successfully.";
                } else {
                    $error = "Failed to update your listing. Please try again.";
                }
            } else {
                // Insert using prepared statement
                $insert_query = "INSERT INTO roommates (
                    user_id, gender, age, occupation, budget, 
                    preferred_location, move_in_date, duration, 
                    description, preferences, is_approved, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
                
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "isisssisis", 
                    $user_id, $gender, $age, $occupation, $budget,
                    $preferred_location, $move_in_date, $duration,
                    $description, $preferences
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Your roommate listing has been submitted successfully and is pending approval.";
                    
                    // Refresh roommate data
                    $stmt = mysqli_prepare($conn, $check_query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $check_result = mysqli_stmt_get_result($stmt);
                    $is_listed = mysqli_num_rows($check_result) > 0;
                    $roommate_data = $is_listed ? mysqli_fetch_assoc($check_result) : null;
                } else {
                    $error = "Failed to submit your listing. Please try again.";
                }
            }
        }
    } elseif (isset($_POST['remove_listing'])) {
        // Remove roommate listing
        $delete_query = "DELETE FROM roommates WHERE user_id = $user_id";
        
        if (mysqli_query($conn, $delete_query)) {
            $message = "Your roommate listing has been removed successfully.";
            $is_listed = false;
            $roommate_data = null;
        } else {
            $error = "Failed to remove your listing. Please try again.";
        }
    }
}

// Get available roommates if table exists
$roommates_result = false;
if ($table_exists) {
    $roommates_query = "SELECT r.*, u.name, u.email, u.phone, u.profile_image 
                      FROM roommates r 
                      JOIN users u ON r.user_id = u.id 
                      WHERE r.is_approved = 1 AND r.user_id != ? 
                      ORDER BY r.created_at DESC";
    $stmt = mysqli_prepare($conn, $roommates_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $roommates_result = mysqli_stmt_get_result($stmt);
}

// Include header
include '../includes/user_header.php';
?>

<!-- Main Content -->
<div class="flex-1 p-8 overflow-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Find a Roommate</h1>
            <p class="text-gray-600">Connect with potential roommates or list yourself as available</p>
        </div>
        
        <?php if ($is_listed): ?>
            <div class="flex space-x-4">
                <button onclick="openEditForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-edit mr-2"></i> Edit My Listing
                </button>
                <form action="roommates.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove your roommate listing?');">
                    <input type="hidden" name="remove_listing" value="1">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-trash-alt mr-2"></i> Remove Listing
                    </button>
                </form>
            </div>
        <?php else: ?>
            <button onclick="openRoommateForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-plus mr-2"></i> List as Roommate
            </button>
        <?php endif; ?>
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
    
    <!-- My Roommate Status -->
    <?php if ($is_listed): ?>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
            <div class="p-4 border-b">
                <h2 class="font-bold">My Roommate Listing</h2>
            </div>
            
            <div class="p-6">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-1/4 mb-4 md:mb-0">
                        <div class="flex flex-col items-center">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover mb-2">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-4xl mb-2">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($user['name']); ?></h3>
                            
                            <?php
                            $status_class = $roommate_data['is_approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            $status_text = $roommate_data['is_approved'] ? 'Approved' : 'Pending Approval';
                            ?>
                            <span class="px-3 py-1 <?php echo $status_class; ?> rounded-full text-xs font-medium mt-2">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="md:w-3/4 md:pl-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <span class="text-gray-600">Gender:</span>
                                <span class="font-semibold ml-2"><?php echo htmlspecialchars($roommate_data['gender'] ?? ''); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Age:</span>
                                <span class="font-semibold ml-2"><?php echo $roommate_data['age'] ?? ''; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Occupation:</span>
                                <span class="font-semibold ml-2"><?php echo htmlspecialchars($roommate_data['occupation'] ?? ''); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Budget:</span>
                                <span class="font-semibold ml-2">₹<?php echo number_format($roommate_data['budget'] ?? 0); ?>/month</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Preferred Location:</span>
                                <span class="font-semibold ml-2"><?php echo htmlspecialchars($roommate_data['preferred_location'] ?? ''); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Move-in Date:</span>
                                <span class="font-semibold ml-2"><?php echo $roommate_data['move_in_date'] ? date('M d, Y', strtotime($roommate_data['move_in_date'])) : ''; ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-semibold ml-2"><?php echo $roommate_data['duration']; ?> Month(s)</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Listed On:</span>
                                <span class="font-semibold ml-2"><?php echo date('M d, Y', strtotime($roommate_data['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="font-semibold mb-2">About Me</h4>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($roommate_data['description'])); ?></p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold mb-2">Preferences</h4>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($roommate_data['preferences'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Available Roommates -->
    <?php if ($table_exists): ?>
        <div>
            <h2 class="text-xl font-bold mb-4">Available Roommates</h2>
            
            <?php if (mysqli_num_rows($roommates_result) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($roommate = mysqli_fetch_assoc($roommates_result)): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <?php if (!empty($roommate['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($roommate['profile_image']); ?>" alt="<?php echo htmlspecialchars($roommate['name']); ?>" class="w-16 h-16 rounded-full object-cover mr-4">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl mr-4">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($roommate['name']); ?></h3>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($roommate['occupation']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-2 mb-4">
                                    <div>
                                        <span class="text-gray-600 text-sm">Gender:</span>
                                        <span class="font-semibold text-sm ml-1"><?php echo htmlspecialchars($roommate['gender']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Age:</span>
                                        <span class="font-semibold text-sm ml-1"><?php echo $roommate['age']; ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Budget:</span>
                                        <span class="font-semibold text-sm ml-1">₹<?php echo number_format($roommate['budget']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Duration:</span>
                                        <span class="font-semibold text-sm ml-1"><?php echo $roommate['duration']; ?> Month(s)</span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <span class="text-gray-600 text-sm">Preferred Location:</span>
                                    <span class="font-semibold text-sm ml-1"><?php echo htmlspecialchars($roommate['preferred_location']); ?></span>
                                </div>
                                
                                <div class="mb-4">
                                    <span class="text-gray-600 text-sm">Move-in Date:</span>
                                    <span class="font-semibold text-sm ml-1"><?php echo date('M d, Y', strtotime($roommate['move_in_date'])); ?></span>
                                </div>
                                
                                <div class="border-t pt-4 mt-4">
                                    <button onclick="viewRoommateDetails(<?php echo $roommate['id']; ?>)" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-user-friends fa-4x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No Roommates Available</h3>
                    <p class="text-gray-600 mb-6">
                        There are no roommates available at the moment. Be the first to list yourself as a roommate!
                    </p>
                    <button onclick="openRoommateForm()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                        List as Roommate
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Roommate Form Modal -->
<div id="roommate-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden overflow-y-auto">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-6 mx-4">
        <!-- Modal Header with Gradient -->
        <div class="sticky top-0 z-30 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-user-friends mr-3 text-blue-100"></i>
                    <?php echo $is_listed ? 'Edit Your Roommate Profile' : 'Create Roommate Profile'; ?>
                </h3>
                <button onclick="closeRoommateForm()" class="p-2 hover:bg-blue-600/50 rounded-full transition-colors">
                    <i class="fas fa-times text-white"></i>
                </button>
            </div>
        </div>

        <!-- Form Content with Better Sections -->
        <div class="custom-scrollbar overflow-y-auto px-6 py-8" style="max-height: calc(100vh - 200px);">
            <form action="roommates.php" method="POST" class="space-y-8" id="roommate-form">
                <input type="hidden" name="list_as_roommate" value="1">
                
                <!-- Personal Information Section -->
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 shadow-sm">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                        Personal Information
                    </h4>
                    <!-- Existing personal info fields go here -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="gender" class="block text-gray-700 font-medium mb-2">Gender</label>
                            <select 
                                id="gender" 
                                name="gender" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($is_listed && $roommate_data['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($is_listed && $roommate_data['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($is_listed && $roommate_data['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="age" class="block text-gray-700 font-medium mb-2">Age</label>
                            <input 
                                type="number" 
                                id="age" 
                                name="age" 
                                min="18" 
                                max="100" 
                                value="<?php echo $is_listed ? $roommate_data['age'] : ''; ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="occupation" class="block text-gray-700 font-medium mb-2">Occupation</label>
                            <input 
                                type="text" 
                                id="occupation" 
                                name="occupation" 
                                value="<?php echo $is_listed ? htmlspecialchars($roommate_data['occupation']) : ''; ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="budget" class="block text-gray-700 font-medium mb-2">Monthly Budget (₹)</label>
                            <input 
                                type="number" 
                                id="budget" 
                                name="budget" 
                                min="1000" 
                                value="<?php echo $is_listed ? $roommate_data['budget'] : ''; ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="preferred_location" class="block text-gray-700 font-medium mb-2">Preferred Location</label>
                            <input 
                                type="text" 
                                id="preferred_location" 
                                name="preferred_location" 
                                value="<?php echo $is_listed ? htmlspecialchars($roommate_data['preferred_location']) : ''; ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="move_in_date" class="block text-gray-700 font-medium mb-2">Move-in Date</label>
                            <input 
                                type="date" 
                                id="move_in_date" 
                                name="move_in_date" 
                                min="<?php echo date('Y-m-d'); ?>" 
                                value="<?php echo $is_listed ? $roommate_data['move_in_date'] : ''; ?>" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="duration" class="block text-gray-700 font-medium mb-2">Duration (Months)</label>
                            <select 
                                id="duration" 
                                name="duration" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                                <option value="">Select Duration</option>
                                <option value="1" <?php echo ($is_listed && $roommate_data['duration'] == 1) ? 'selected' : ''; ?>>1 Month</option>
                                <option value="3" <?php echo ($is_listed && $roommate_data['duration'] == 3) ? 'selected' : ''; ?>>3 Months</option>
                                <option value="6" <?php echo ($is_listed && $roommate_data['duration'] == 6) ? 'selected' : ''; ?>>6 Months</option>
                                <option value="12" <?php echo ($is_listed && $roommate_data['duration'] == 12) ? 'selected' : ''; ?>>12 Months</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preferences Section -->
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 shadow-sm">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-heart text-blue-500 mr-2"></i>
                        Living Preferences
                    </h4>
                    <!-- Existing preferences fields go here -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mt-6">
                            <label for="description" class="block text-gray-700 font-medium mb-2">About Me</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            ><?php echo $is_listed ? htmlspecialchars($roommate_data['description']) : ''; ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Describe yourself, your lifestyle, habits, and what you're looking for in a roommate.</p>
                        </div>
                        
                        <div class="mt-6">
                            <label for="preferences" class="block text-gray-700 font-medium mb-2">Roommate Preferences</label>
                            <textarea 
                                id="preferences" 
                                name="preferences" 
                                rows="4" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            ><?php echo $is_listed ? htmlspecialchars($roommate_data['preferences']) : ''; ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Describe your preferences for a roommate (e.g., gender, age, occupation, lifestyle).</p>
                        </div>
                    </div>
                </div>

                <!-- About Section -->
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 shadow-sm">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-comment-alt text-blue-500 mr-2"></i>
                        About You
                    </h4>
                    <!-- Existing about fields go here -->
                    <div class="space-y-6">
                        <!-- Keep your existing textarea fields but wrapped in this new structure -->
                    </div>
                </div>
            </form>
        </div>

        <!-- Enhanced Footer -->
        <div class="sticky bottom-0 z-30 bg-white border-t px-6 py-4 rounded-b-2xl shadow-inner">
            <div class="flex justify-end space-x-4">
                <button 
                    type="button" 
                    onclick="closeRoommateForm()" 
                    class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors flex items-center"
                >
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
                <button 
                    type="submit"
                    form="roommate-form"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center"
                >
                    <i class="fas fa-check mr-2"></i>
                    <?php echo $is_listed ? 'Update Profile' : 'Create Profile'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Roommate Details Modal -->
<div id="roommate-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-bold text-lg">Roommate Details</h3>
            <button onclick="closeRoommateDetails()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="roommate-details-content" class="p-6">
            <!-- Content will be loaded via AJAX -->
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Roommate Form Modal
    function openRoommateForm() {
        document.getElementById('roommate-modal').classList.remove('hidden');
    }
    
    function closeRoommateForm() {
        document.getElementById('roommate-modal').classList.add('hidden');
    }
    
    function openEditForm() {
        document.getElementById('roommate-modal').classList.remove('hidden');
    }
    
    // Roommate Details Modal
    function viewRoommateDetails(roommateId) {
        document.getElementById('roommate-details-modal').classList.remove('hidden');
        
        // Load roommate details via AJAX
        fetch('get-roommate-details.php?id=' + roommateId)
            .then(response => response.text())
            .then(data => {
                document.getElementById('roommate-details-content').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('roommate-details-content').innerHTML = '<div class="text-red-500">Error loading roommate details. Please try again.</div>';
            });
    }
    
    function closeRoommateDetails() {
        document.getElementById('roommate-details-modal').classList.add('hidden');
    }
</script>

<?php
// Include footer
include '../includes/user_footer.php';
?>

