<?php
// Start session for user authentication
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to home page or requested page
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header("Location: $redirect");
    exit();
}

// Process registration form
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    require_once 'includes/db_connect.php';
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $agree_terms = isset($_POST['agree_terms']) ? true : false;
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!$agree_terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email address is already registered.';
        } else {
            // Check if phone already exists
            $query = "SELECT id FROM users WHERE phone = '$phone'";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'Phone number is already registered.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $query = "INSERT INTO users (name, email, phone, password, user_type, created_at) 
                          VALUES ('$name', '$email', '$phone', '$hashed_password', '$user_type', NOW())";
                
                if (mysqli_query($conn, $query)) {
                    $user_id = mysqli_insert_id($conn);
                    
                    // Create verification token
                    $token = bin2hex(random_bytes(32));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    
                    $query = "INSERT INTO verification_tokens (user_id, token, created_at) 
                              VALUES ($user_id, '$token_hash', NOW())";
                    mysqli_query($conn, $query);
                    
                    // In a real application, send verification email here
                    
                    $success = true;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto bg-white bordet rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <?php if ($success): ?>
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                            <i class="fas fa-check-circle text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Registration Successful!</h2>
                        <p class="text-gray-600 mt-2">
                            Your account has been created successfully. Please check your email to verify your account.
                        </p>
                        <div class="mt-6">
                            <a href="login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-300">
                                Proceed to Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-800">Create an Account</h2>
                        <p class="text-gray-600 mt-1">Join RapidStay to find your perfect accommodation</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
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
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
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
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
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
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                required
                            >
                        </div>
                        
                        <div class="mb-6">
                            <label for="user_type" class="block text-gray-700 font-medium mb-2">I am a</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input 
                                        type="radio" 
                                        name="user_type" 
                                        value="tenant" 
                                        class="sr-only" 
                                        <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] === 'tenant') ? 'checked' : ''; ?>
                                    >
                                    <div class="text-center">
                                        <i class="fas fa-home text-blue-500 text-xl mb-1"></i>
                                        <div>Tenant</div>
                                    </div>
                                </label>
                                <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input 
                                        type="radio" 
                                        name="user_type" 
                                        value="owner" 
                                        class="sr-only"
                                        <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'owner') ? 'checked' : ''; ?>
                                    >
                                    <div class="text-center">
                                        <i class="fas fa-key text-blue-500 text-xl mb-1"></i>
                                        <div>Owner</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="agree_terms" 
                                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    required
                                >
                                <span class="ml-2 block text-sm text-gray-700">
                                    I agree to the 
                                    <a href="terms.php" class="text-blue-600 hover:underline">Terms of Service</a> 
                                    and 
                                    <a href="privacy.php" class="text-blue-600 hover:underline">Privacy Policy</a>
                                </span>
                            </label>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 btn-bg hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                        >
                            Create Account
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center ">
                        <p class="text-gray-600">
                            Already have an account? 
                            <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="text-blue-600 hover:underline font-medium">
                                Sign in
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

