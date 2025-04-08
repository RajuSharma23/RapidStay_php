<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/user-management.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Process user actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Delete user
        if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            
            // Check if user exists
            $check_query = "SELECT * FROM users WHERE id = $user_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $user = mysqli_fetch_assoc($check_result);
                
                // Don't allow deleting own account
                if ($user_id === $_SESSION['user_id']) {
                    $error = "You cannot delete your own account.";
                } else {
                    // Delete user
                    $delete_query = "DELETE FROM users WHERE id = $user_id";
                    
                    if (mysqli_query($conn, $delete_query)) {
                        $message = "User '" . htmlspecialchars($user['name']) . "' has been deleted successfully.";
                    } else {
                        $error = "Failed to delete user. Please try again.";
                    }
                }
            } else {
                $error = "User not found.";
            }
        }
        
        // Add new user
        if ($_POST['action'] === 'add_user') {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $password = $_POST['password'];
            $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
            
            // Validate input
            if (empty($name) || empty($email) || empty($phone) || empty($password)) {
                $error = "Please fill in all required fields.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long.";
            } else {
                // Check if email already exists
                $check_query = "SELECT id FROM users WHERE email = '$email'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error = "Email address is already registered.";
                } else {
                    // Check if phone already exists
                    $check_query = "SELECT id FROM users WHERE phone = '$phone'";
                    $check_result = mysqli_query($conn, $check_query);
                    
                    if (mysqli_num_rows($check_result) > 0) {
                        $error = "Phone number is already registered.";
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert new user
                        $insert_query = "INSERT INTO users (name, email, phone, password, user_type, is_verified, created_at) 
                                        VALUES ('$name', '$email', '$phone', '$hashed_password', '$user_type', 1, NOW())";
                        
                        if (mysqli_query($conn, $insert_query)) {
                            $message = "User '$name' has been added successfully.";
                        } else {
                            $error = "Failed to add user. Please try again.";
                        }
                    }
                }
            }
        }
    }
}

// Get users list
$users_query = "SELECT * FROM users WHERE user_type != 'admin' ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

// Include header
include '../includes/admin_header.php';
?>
<link rel="stylesheet" href="../../assets/css/style.css">



<!-- Main Content -->
<div class="flex-1  p-8 overflow-auto">
    <div class="mb-8 flex main-item justify-between items-center">
        <h1 class="text-2xl font-bold">User Management</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" onclick="openAddUserModal()">
            <i class="fas fa-plus mr-2"></i> Add New User
        </button>
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
    
    <!-- Users Table -->
    <div class="bg-white  border-top rounded-lg shadow-sm user-item overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="font-bold">All Users</h2>
        </div>
        
        <div class="overflow-x-auto  ">
            <table class="min-w-full divide-y  divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($users_result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $user['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['user_type'] === 'owner'): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            PG Owner
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Tenant
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['is_verified']): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Unverified
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view-user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="text-red-600 hover:text-red-900" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No users found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-bold">Add New User</h3>
            <button onclick="closeAddUserModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="user-management.php" method="POST" class="p-4">
            <input type="hidden" name="action" value="add_user">
            
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    required
                    minlength="8"
                >
                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
            </div>
            
            <div class="mb-6">
                <label for="user_type" class="block text-gray-700 font-medium mb-2">User Type</label>
                <select 
                    id="user_type" 
                    name="user_type" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    required
                >
                    <option value="tenant">Tenant</option>
                    <option value="owner">PG Owner</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeAddUserModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div id="delete-user-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="p-6">
            <h3 class="font-bold text-lg mb-4">Confirm Deletion</h3>
            <p class="mb-6">Are you sure you want to delete user <span id="delete-user-name" class="font-semibold"></span>? This action cannot be undone.</p>
            
            <form action="user-management.php" method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete-user-id">
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeDeleteUserModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Add User Modal
    function openAddUserModal() {
        document.getElementById('add-user-modal').classList.remove('hidden');
    }
    
    function closeAddUserModal() {
        document.getElementById('add-user-modal').classList.add('hidden');
    }
    
    // Delete User Modal
    function confirmDeleteUser(userId, userName) {
        document.getElementById('delete-user-id').value = userId;
        document.getElementById('delete-user-name').textContent = userName;
        document.getElementById('delete-user-modal').classList.remove('hidden');
    }
    
    function closeDeleteUserModal() {
        document.getElementById('delete-user-modal').classList.add('hidden');
    }
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>

