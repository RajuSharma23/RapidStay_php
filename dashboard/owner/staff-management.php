<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a PG owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/staff-management.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Replace the image creation code near the top of your file with this:
$default_avatar_path = "../../assets/images/default-avatar.png";
if (!file_exists($default_avatar_path)) {
    // Create directory if it doesn't exist
    if (!file_exists("../../assets/images")) {
        mkdir("../../assets/images", 0777, true);
    }
    // Copy a default avatar image from a base64 string
    $default_avatar = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4wIIDjgJR5++BAAAAs9JREFUeNrt3T1oE2EYwPH/3SUpakE/qCAd1EUHQQcHEVx0EAQXwQ/Ezdldv0B0cnXRRRQVxFGcFAQRdBAEB0EQREFBoYJ1arU2uTsHbUFeRUKb5HJ5n98WQu6Su+eX577vLglVJUmSJEmSJEmSJEmSJEmSJEmSJEmSpHoKvAT1NQGeA2P7+JsZ4BpwH1gFFt3C+jkEzABPgCvAeAm/3QrcAb4Co+u/s20r6+MQ8By4D5zY5zXHgavAR2AMWAb6wFKxjz23uXpngEfAXWC6pGtOA3eAL0A3+mwZWCiuOeehq24QmAQeALeBkw2YyTwDXkefXwF+ASvOJPc2ARwHpoC3wE1gqoHXPwpcKmYQMWSl2OZW3O6/G0I7CirCNgA0xDZ0HIZ/HUzRWEMOiEkGDUSSJEmSJEmSJEmSJEmSJEmSJElSE4TeWf9dQ9Im8eA/zxYseZ+/AD4QEnpneeTGA3gCXACGbWP5DgPngbnu1c9dw6hGHPAhcBo4ZvvKNQycA95HC3/NQ8rVBZ4C54Ajtu3gOsA54FVgKl7QVWEc8Bo4Cxy2XQc3CbwEzgPjBrJ/x4BXRRhjtqkcHeBsMYOMGsi/TQFvgBvApG0p3VHgMvCpCGXRQP5sCngL3KLc94upMFXMIK+Lba8byP+NAe+AJ8BV23AgR4ArwI9i+/8YSNxxfwY8BG7YdsqcRZaBH8Wgsh4DiTvvz8A94JptpXJbwI9iFlkC1gyE0ZxfbwKzwHpt6lMvlwpDxeq6VcwmS8Dv1l4uFjuubXQe0AXeATcZ4OWOGtL8YMGCBQsuZyxYsGDBggULFixYsGDBggULFixYsGDBQjMLFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBggULFixYsGDBQhsKfwDXzMoWHD2a+QAAAABJRU5ErkJggg==');
    file_put_contents($default_avatar_path, $default_avatar);
}

// Get owner ID
$owner_id = $_SESSION['user_id'];

// Process form submission for adding new staff
$message = '';
$error = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new staff
        if ($_POST['action'] === 'add_staff') {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $property_id = !empty($_POST['property_id']) ? intval($_POST['property_id']) : NULL;
            $salary = floatval($_POST['salary']);
            $join_date = mysqli_real_escape_string($conn, $_POST['join_date']);
            
            // Validate data
            if (empty($name) || empty($email) || empty($phone) || empty($role) || $salary <= 0 || empty($join_date)) {
                $error = "Please fill in all required fields.";
            } else {
                // Check if property exists if property_id is provided
                if ($property_id) {
                    $check_property = "SELECT id FROM listings WHERE id = ? AND user_id = ? AND is_active = 1";
                    $stmt = $conn->prepare($check_property);
                    $stmt->bind_param("ii", $property_id, $owner_id);
                    $stmt->execute();
                    $property_result = $stmt->get_result();
                    
                    if ($property_result->num_rows === 0) {
                        $error = "Invalid property selected.";
                        $property_id = NULL;
                    }
                    $stmt->close();
                }
                
                if (empty($error)) {
                    $profile_picture = null;
            
                    // Handle profile picture upload
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                        $upload_dir = '../../uploads/staff/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                        $allowed_types = ['jpg', 'jpeg', 'png'];
                        
                        if (in_array($file_extension, $allowed_types)) {
                            $new_filename = uniqid('staff_') . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                                $profile_picture = $new_filename;
                            }
                        }
                    }
                    
                    if (empty($error)) {
                        // Insert new staff member
                        $insert_query = "INSERT INTO staff (
                            owner_id, 
                            name, 
                            email, 
                            phone, 
                            role, 
                            property_id, 
                            salary, 
                            join_date, 
                            profile_picture,
                            status,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
                    
                        $stmt = $conn->prepare($insert_query);
                        $stmt->bind_param(
                            "issssisss",
                            $owner_id,
                            $name,
                            $email,
                            $phone,
                            $role,
                            $property_id,
                            $salary,
                            $join_date,
                            $profile_picture
                        );
                    
                        if ($stmt->execute()) {
                            $message = "Staff member added successfully.";
                        } else {
                            $error = "Failed to add staff member. Please try again.";
                        }
                        $stmt->close();
                    }
                }
            }
        }
        
        // Update staff
        elseif ($_POST['action'] === 'update_staff') {
            $staff_id = intval($_POST['staff_id']);
            
            // Get existing staff data
            $check_staff = "SELECT * FROM staff WHERE id = ? AND owner_id = ?";
            $stmt = $conn->prepare($check_staff);
            $stmt->bind_param("ii", $staff_id, $owner_id);
            $stmt->execute();
            $existing_staff = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_staff) {
                // Use existing values if new ones aren't provided
                $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : $existing_staff['name'];
                $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : $existing_staff['email'];
                $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : $existing_staff['phone'];
                $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : $existing_staff['role'];
                $property_id = isset($_POST['property_id']) ? ($_POST['property_id'] ? intval($_POST['property_id']) : NULL) : $existing_staff['property_id'];
                $salary = isset($_POST['salary']) ? floatval($_POST['salary']) : $existing_staff['salary'];
                $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : $existing_staff['status'];

                // Validate property_id if provided
                if ($property_id !== NULL) {
                    $check_property = "SELECT id FROM listings WHERE id = ? AND user_id = ? AND is_active = 1";
                    $stmt = $conn->prepare($check_property);
                    $stmt->bind_param("ii", $property_id, $owner_id);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows === 0) {
                        $error = "Invalid property selected.";
                        $property_id = NULL;
                    }
                    $stmt->close();
                }

                if (empty($error)) {
                    // Use prepared statement for update
                    if ($status === 'on_leave') {
                        $leave_start = isset($_POST['leave_start_date']) ? $_POST['leave_start_date'] : NULL;
                        $leave_end = isset($_POST['leave_end_date']) ? $_POST['leave_end_date'] : NULL;
                        
                        $update_query = "UPDATE staff SET 
                            status = ?,
                            leave_start_date = ?,
                            leave_end_date = ?
                            WHERE id = ? AND owner_id = ?";
                        
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("sssii", $status, $leave_start, $leave_end, $staff_id, $owner_id);
                    } else {
                        $update_query = "UPDATE staff SET 
                            status = ?,
                            leave_start_date = NULL,
                            leave_end_date = NULL
                            WHERE id = ? AND owner_id = ?";
                        
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("sii", $status, $staff_id, $owner_id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = "Staff information updated successfully.";
                    } else {
                        $error = "Failed to update staff information. Please try again.";
                    }
                    $stmt->close();
                }
            } else {
                $error = "Staff member not found.";
            }

            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $upload_dir = '../../uploads/staff/';
                
                // Create directories if they don't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_info = pathinfo($_FILES['profile_picture']['name']);
                $file_extension = strtolower($file_info['extension']);
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_types)) {
                    // Generate unique filename
                    $new_filename = 'staff_' . $staff_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    // Delete old profile picture if exists
                    if (!empty($staff['profile_picture'])) {
                        $old_file = $upload_dir . $staff['profile_picture'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        // Update database with new filename
                        $update_picture_query = "UPDATE staff SET profile_picture = ? WHERE id = ? AND owner_id = ?";
                        $stmt = $conn->prepare($update_picture_query);
                        $stmt->bind_param("sii", $new_filename, $staff_id, $owner_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
        
        // Delete staff
        elseif ($_POST['action'] === 'delete_staff') {
            $staff_id = intval($_POST['staff_id']);
            
            // Delete staff
            $delete_query = "DELETE FROM staff WHERE id = $staff_id AND owner_id = $owner_id";
            
            if (mysqli_query($conn, $delete_query)) {
                $message = "Staff member removed successfully.";
            } else {
                $error = "Failed to remove staff member. Please try again.";
            }
        }
    }
}

// Update the staff query section with proper execution
// Find this section in your code (around line 89):

// Get all staff members for this owner
$staff_query = "SELECT s.*, l.title as property_name 
                FROM staff s 
                LEFT JOIN listings l ON s.property_id = l.id 
                WHERE s.owner_id = $owner_id 
                ORDER BY s.created_at DESC";
                
// Add this line to execute the query
$staff_result = mysqli_query($conn, $staff_query);

if (!$staff_result) {
    $error = "Error fetching staff data: " . mysqli_error($conn);
}

// Rest of your code remains the same...

// Get all properties for dropdown
$properties_query = "SELECT id, title FROM listings WHERE user_id = $owner_id AND is_active = 1";
$properties_result = mysqli_query($conn, $properties_query);
$properties = [];
while ($property = mysqli_fetch_assoc($properties_result)) {
    $properties[] = $property;
}

// Include header
include '../includes/owner_header.php';
?>
<style>
    .main-content {
        /* margin-left: 200px; */
    }
    .container{
        /* margin-top:50px; */

    }
    .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close-modal:hover {
        color: black;
    }

    .message-popup {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease-in-out;
    }

    .message-popup.show {
        opacity: 1;
        transform: translateX(0);
    }

    .message-popup.success {
        background-color: #34d399;
        color: white;
        border-left: 4px solid #059669;
    }

    .message-popup.error {
        background-color: #f87171;
        color: white;
        border-left: 4px solid #dc2626;
    }
</style>
<link rel="stylesheet" href="../../assets/css/style.css">


<!-- Main Content -->
<div class="flex-1 main-content p-8 overflow-auto">
    <div class="container ">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Staff Management</h1>
        <p class="text-gray-600">Manage your PG staff members and their details</p>
    </div>
    
    <?php if (!empty($message) || !empty($error)): ?>
        <div id="messagePopup" class="message-popup <?php echo !empty($error) ? 'error' : 'success'; ?>">
            <?php echo !empty($error) ? $error : $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Staff Statistics -->
    <div class="bg-white border-top rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold mb-4">Staff Overview</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-blue-700">Total Staff</h3>
                <p class="text-2xl font-bold">
                    <?php 
                    $total_query = "SELECT COUNT(*) as count FROM staff WHERE owner_id = $owner_id";
                    $total_result = mysqli_query($conn, $total_query);
                    echo mysqli_fetch_assoc($total_result)['count']; 
                    ?>
                </p>
            </div>
            
            <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-green-700">Active Staff</h3>
                <p class="text-2xl font-bold">
                    <?php 
                    $active_query = "SELECT COUNT(*) as count FROM staff WHERE owner_id = $owner_id AND (status = 'active' OR status IS NULL)";
                    $active_result = mysqli_query($conn, $active_query);
                    echo mysqli_fetch_assoc($active_result)['count']; 
                    ?>
                </p>
            </div>
            
            <div class="bg-yellow-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-yellow-700">On Leave</h3>
                <p class="text-2xl font-bold">
                    <?php 
                    $leave_query = "SELECT COUNT(*) as count FROM staff WHERE owner_id = $owner_id AND (status = 'on_leave' OR status IS NULL)";
                    $leave_result = mysqli_query($conn, $leave_query);
                    echo mysqli_fetch_assoc($leave_result)['count']; 
                    ?>
                </p>
            </div>
            
            <div class="bg-purple-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-purple-700">Monthly Salary</h3>
                <p class="text-2xl font-bold">
                    <?php 
                    // Calculate total salary only for active staff
                    $salary_query = "SELECT 
                        SUM(CASE 
                            WHEN status = 'active' THEN salary
                            WHEN status = 'on_leave' THEN salary * 0.5  -- 50% salary for staff on leave
                            WHEN status = 'inactive' THEN 0  -- No salary for inactive staff
                            ELSE salary 
                        END) as total 
                        FROM staff 
                        WHERE owner_id = $owner_id";
                    $salary_result = mysqli_query($conn, $salary_query);
                    $total_salary = mysqli_fetch_assoc($salary_result)['total'];
                    echo '₹' . number_format($total_salary, 2); 
                    ?>
                </p>
                <p class="text-sm text-purple-600 mt-1">
                    <?php
                    // Show deducted amount
                    $full_salary_query = "SELECT SUM(salary) as full_total FROM staff WHERE owner_id = $owner_id";
                    $full_salary_result = mysqli_query($conn, $full_salary_query);
                    $full_salary = mysqli_fetch_assoc($full_salary_result)['full_total'];
                    $deducted_amount = $full_salary - $total_salary;
                    if ($deducted_amount > 0) {
                        echo "Deducted: ₹" . number_format($deducted_amount, 2);
                    }
                    ?>
                </p>
            </div>
            
            <!-- Add Inactive Staff stat -->
            <div class="bg-red-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-red-700">Inactive</h3>
                <p class="text-2xl font-bold">
                    <?php 
                    $inactive_query = "SELECT COUNT(*) as count FROM staff WHERE owner_id = $owner_id AND status = 'inactive'";
                    $inactive_result = mysqli_query($conn, $inactive_query);
                    echo mysqli_fetch_assoc($inactive_result)['count']; 
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Staff Management -->
    <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden mb-8">
        <div class="flex justify-between items-center p-4 border-b">
            <div class="flex items-center gap-4">
                <h2 class="font-bold">Your Staff Members</h2>
                <select 
                    id="statusFilter" 
                    class="border rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                    onchange="filterStaff(this.value)"
                >
                    <option value="all">All Staff</option>
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button 
                onclick="openAddStaffModal()" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center"
            >
                <i class="fas fa-plus mr-2"></i> Add New Staff
            </button>
        </div>
        
        <!-- Staff List Section -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $staff_query = "SELECT s.*, l.title as property_name 
                                   FROM staff s 
                                   LEFT JOIN listings l ON s.property_id = l.id 
                                   WHERE s.owner_id = ? 
                                   ORDER BY s.created_at DESC";
                    $stmt = $conn->prepare($staff_query);
                    $stmt->bind_param("i", $owner_id);
                    $stmt->execute();
                    $staff_result = $stmt->get_result();
                    
                    if ($staff_result->num_rows > 0):
                        while ($staff = $staff_result->fetch_assoc()):
                    ?>
                        <tr data-staff-id="<?php echo $staff['id']; ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <?php if (!empty($staff['profile_picture'])): ?>
                                            <img 
                                                class="h-10 w-10 rounded-full object-cover border-2 border-gray-200" 
                                                src="<?php echo '../../uploads/staff/' . htmlspecialchars($staff['profile_picture']); ?>" 
                                                alt="<?php echo htmlspecialchars($staff['name']); ?>"
                                                onerror="this.src='../../assets/images/default-avatar.png'; this.onerror=null;"
                                            >
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <span class="text-gray-500 font-medium text-lg">
                                                    <?php echo strtoupper(substr($staff['name'] ?? '', 0, 1)); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($staff['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($staff['role']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($staff['phone']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($staff['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo $staff['property_name'] ? htmlspecialchars($staff['property_name']) : 'Not Assigned'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm <?php echo $staff['status'] === 'active' ? 'text-gray-900' : 'text-gray-500'; ?>">
                                    ₹<?php echo number_format($staff['salary'], 2); ?>
                                    <?php if ($staff['status'] === 'on_leave'): ?>
                                        <span class="text-yellow-600">(50%)</span>
                                    <?php elseif ($staff['status'] === 'inactive'): ?>
                                        <span class="text-red-600">(0%)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select 
                                    onchange="updateStaffStatus(<?php echo $staff['id']; ?>, this.value)"
                                    class="appearance-none px-3 py-1 rounded-full text-xs font-semibold
                                        <?php
                                        switch ($staff['status']) {
                                            case 'active':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'on_leave':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'inactive':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>"
                                >
                                    <option value="active" <?php echo $staff['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="on_leave" <?php echo $staff['status'] === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="inactive" <?php echo $staff['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <?php if ($staff['status'] === 'on_leave' && $staff['leave_start_date'] && $staff['leave_end_date']): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php 
                                        echo date('M d', strtotime($staff['leave_start_date'])) . ' - ' . 
                                             date('M d, Y', strtotime($staff['leave_end_date'])); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($staff['join_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="openEditStaffModal(<?php echo htmlspecialchars(json_encode($staff)); ?>)" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-2">
                                    Edit
                                </button>
                                <button onclick="confirmDeleteStaff(<?php echo $staff['id']; ?>, '<?php echo addslashes($staff['name']); ?>')"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                No staff members found. Add your first staff member to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal ">
        <div class="modal-content border-top">
            <span class="close-modal" onclick="closeAddStaffModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Add New Staff Member</h2>
            
            <form action="staff-management.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_staff">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="profile_picture" class="block text-gray-700 font-medium mb-2">Profile Picture</label>
                        <input 
                            type="file" 
                            id="profile_picture" 
                            name="profile_picture" 
                            accept="image/*"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            onchange="previewImage(this, 'picturePreview')"
                        >
                        <div id="picturePreview" class="mt-2 hidden">
                            <img src="" alt="Preview" class="w-24 h-24 object-cover rounded-full">
                        </div>
                    </div>
                    
                    <div>
                        <label for="role" class="block text-gray-700 font-medium mb-2">Role</label>
                        <select 
                            id="role" 
                            name="role"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="">Select Role</option>
                            <option value="manager">Manager</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="security">Security Guard</option>
                            <option value="cleaning">Cleaning Staff</option>
                            <option value="cook">Cook</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="property_id" class="block text-gray-700 font-medium mb-2">Assign Property (Optional)</label>
                        <select 
                            id="property_id" 
                            name="property_id"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                            <option value="">Not Assigned</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo htmlspecialchars($property['id']); ?>">
                                    <?php echo htmlspecialchars($property['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="salary" class="block text-gray-700 font-medium mb-2">Monthly Salary (₹)</label>
                        <input 
                            type="number" 
                            id="salary" 
                            name="salary" 
                            min="1000" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="join_date" class="block text-gray-700 font-medium mb-2">Join Date</label>
                        <input 
                            type="date" 
                            id="join_date" 
                            name="join_date" 
                            max="<?php echo date('Y-m-d'); ?>" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeAddStaffModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        Add Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="modal">
        <div class="modal-content border-top">
            <span class="close-modal" onclick="closeEditStaffModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Edit Staff Details</h2>
            
            <form action="staff-management.php" method="POST">
                <input type="hidden" name="action" value="update_staff">
                <input type="hidden" name="staff_id" id="edit_staff_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="edit_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                        <input 
                            type="text" 
                            id="edit_name" 
                            name="name" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="edit_role" class="block text-gray-700 font-medium mb-2">Role</label>
                        <select 
                            id="edit_role" 
                            name="role"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="">Select Role</option>
                            <option value="manager">Manager</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="security">Security Guard</option>
                            <option value="cleaning">Cleaning Staff</option>
                            <option value="cook">Cook</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="edit_email" 
                            name="email" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="edit_phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                        <input 
                            type="text" 
                            id="edit_phone" 
                            name="phone" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="edit_property_id" class="block text-gray-700 font-medium mb-2">Assign Property</label>
                        <select 
                            id="edit_property_id" 
                            name="property_id"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        >
                            <option value="0">Not Assigned</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo $property['id']; ?>"><?php echo htmlspecialchars($property['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_salary" class="block text-gray-700 font-medium mb-2">Monthly Salary (₹)</label>
                        <input 
                            type="number" 
                            id="edit_salary" 
                            name="salary" 
                            min="1000" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="edit_status" class="block text-gray-700 font-medium mb-2">Status</label>
                        <select 
                            id="edit_status" 
                            name="status"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" 
                            required
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                </div>
                
                <div class="leave-dates" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="leave_start_date" class="block text-gray-700 font-medium mb-2">Leave Start Date</label>
                            <input 
                                type="date" 
                                id="leave_start_date" 
                                name="leave_start_date" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            >
                        </div>
                        <div>
                            <label for="leave_end_date" class="block text-gray-700 font-medium mb-2">Leave End Date</label>
                            <input 
                                type="date" 
                                id="leave_end_date" 
                                name="leave_end_date" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            >
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditStaffModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Update Details
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Staff Form (Hidden) -->
    <form id="deleteStaffForm" action="staff-management.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete_staff">
        <input type="hidden" name="staff_id" id="delete_staff_id">
    </form>
    </div>
</div>

<script>
    // Add Staff Modal
    const addStaffModal = document.getElementById('addStaffModal');
    
    function openAddStaffModal() {
        addStaffModal.style.display = 'block';
    }
    
    function closeAddStaffModal() {
        addStaffModal.style.display = 'none';
    }
    
    // Edit Staff Modal
    const editStaffModal = document.getElementById('editStaffModal');
    
    function openEditStaffModal(staff) {
        document.getElementById('edit_staff_id').value = staff.id;
        document.getElementById('edit_name').value = staff.name;
        document.getElementById('edit_email').value = staff.email;
        document.getElementById('edit_phone').value = staff.phone;
        document.getElementById('edit_role').value = staff.role;
        document.getElementById('edit_property_id').value = staff.property_id;
        document.getElementById('edit_salary').value = staff.salary;
        document.getElementById('edit_status').value = staff.status;
        
        editStaffModal.style.display = 'block';
    }
    
    function closeEditStaffModal() {
        editStaffModal.style.display = 'none';
    }
    
    // Delete Staff Confirmation
    function confirmDeleteStaff(staffId, staffName) {
        if (confirm(`Are you sure you want to remove ${staffName} from your staff list? This action cannot be undone.`)) {
            document.getElementById('delete_staff_id').value = staffId;
            document.getElementById('deleteStaffForm').submit();
        }
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == addStaffModal) {
            closeAddStaffModal();
        }
        if (event.target == editStaffModal) {
            closeEditStaffModal();
        }
    }

    // Staff filtering functionality
    function filterStaff(status) {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
                return;
            }
            
            const statusCell = row.querySelector('td:nth-child(6) span');
            if (statusCell) {
                const rowStatus = statusCell.textContent.toLowerCase().trim();
                row.style.display = rowStatus === status ? '' : 'none';
            }
        });
        
        // Update empty state message
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const tbody = document.querySelector('tbody');
        const existingEmptyMessage = tbody.querySelector('.empty-message');
        
        if (visibleRows.length === 0) {
            if (!existingEmptyMessage) {
                const emptyRow = document.createElement('tr');
                emptyRow.className = 'empty-message';
                emptyRow.innerHTML = `
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        No staff members found with ${status === 'all' ? 'any' : status} status.
                    </td>
                `;
                tbody.appendChild(emptyRow);
            }
        } else if (existingEmptyMessage) {
            existingEmptyMessage.remove();
        }
    }

    // Add quick status update functionality
    function updateStaffStatus(staffId, newStatus) {
        if (newStatus === 'on_leave') {
            const startDate = prompt('Enter leave start date (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
            if (!startDate) return;
            
            const endDate = prompt('Enter leave end date (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
            if (!endDate) return;
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Leave end date must be after start date');
                return;
            }
            
            submitStatusUpdate(staffId, newStatus, startDate, endDate);
        } else {
            submitStatusUpdate(staffId, newStatus);
        }
    }

    function submitStatusUpdate(staffId, status, leaveStart = null, leaveEnd = null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const fields = {
            action: 'update_staff',
            staff_id: staffId,
            status: status,
            leave_start_date: leaveStart,
            leave_end_date: leaveEnd
        };
        
        for (const [key, value] of Object.entries(fields)) {
            if (value !== null) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        }
        
        document.body.appendChild(form);
        form.submit();
    }

    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const statusCells = document.querySelectorAll('.status-cell');
        statusCells.forEach(cell => {
            const status = cell.getAttribute('data-status');
            if (status) {
                cell.title = `Click to change status`;
            }
        });
    });

    // Add this to your existing JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('edit_status');
        const leaveDates = document.querySelector('.leave-dates');
        
        statusSelect.addEventListener('change', function() {
            if (this.value === 'on_leave') {
                leaveDates.style.display = 'block';
            } else {
                leaveDates.style.display = 'none';
            }
        });

        // Validate leave dates
        const leaveStartDate = document.getElementById('leave_start_date');
        const leaveEndDate = document.getElementById('leave_end_date');
        
        leaveStartDate.addEventListener('change', function() {
            leaveEndDate.min = this.value;
        });
        
        leaveEndDate.addEventListener('change', function() {
            leaveStartDate.max = this.value;
        });
    });

    // Message popup functionality
    document.addEventListener('DOMContentLoaded', function() {
        const messagePopup = document.getElementById('messagePopup');
        if (messagePopup) {
            // Show message
            setTimeout(() => {
                messagePopup.classList.add('show');
            }, 100);

            // Hide message after 3 seconds
            setTimeout(() => {
                messagePopup.classList.remove('show');
                setTimeout(() => {
                    messagePopup.remove();
                }, 300);
            }, 3000);
        }
    });

    // ...existing JavaScript code...
</script>

<?php
// Include footer
include '../includes/owner_footer.php';
?>