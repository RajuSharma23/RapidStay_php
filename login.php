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

// Process login form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Database connection
  require_once 'includes/db_connect.php';
  
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $password = $_POST['password'];
  $remember = isset($_POST['remember']) ? true : false;
  
  // Validate input
  if (empty($email) || empty($password)) {
      $error = 'Please enter both email and password.';
  } else {
      // Check if user exists
      $query = "SELECT * FROM users WHERE email = '$email'";
      $result = mysqli_query($conn, $query);
      
      if (mysqli_num_rows($result) === 1) {
          $user = mysqli_fetch_assoc($result);
          
          // Verify password
          if (password_verify($password, $user['password'])) {
              // Set session variables
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['user_name'] = $user['name'];
              $_SESSION['user_email'] = $user['email'];
              $_SESSION['user_type'] = $user['user_type'];
              
              // Set remember me cookie if requested
              if ($remember) {
                  $token = bin2hex(random_bytes(32));
                  $expires = time() + (30 * 24 * 60 * 60); // 30 days
                  
                  // Store token in database
                  $token_hash = password_hash($token, PASSWORD_DEFAULT);
                  $user_id = $user['id'];
                  
                  $query = "INSERT INTO remember_tokens (user_id, token, expires) VALUES ($user_id, '$token_hash', FROM_UNIXTIME($expires))";
                  mysqli_query($conn, $query);
                  
                  // Set cookie
                  setcookie('remember_token', $token, $expires, '/', '', false, true);
                  setcookie('remember_user', $user['id'], $expires, '/', '', false, true);
              }
              
              // Redirect based on user type
              if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                  // If a specific redirect URL was provided
                  header("Location: " . $_GET['redirect']);
              } else {
                  // Redirect to appropriate dashboard based on user type
                  switch ($user['user_type']) {
                      case 'admin':
                          header("Location: dashboard/admin/index.php");
                          break;
                      case 'owner':
                          header("Location: dashboard/owner/index.php");
                          break;
                      case 'tenant':
                      default:
                          header("Location: dashboard/user/index.php");
                          break;
                  }
              }
              exit();
          } else {
              $error = 'Invalid password.';
          }
      } else {
          $error = 'No account found with this email.';
      }
  }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-100 min-h-screen  py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto bg-white border-top rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
                    <p class="text-gray-600 mt-1">Sign in to your RapidStay account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
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
                    
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-gray-700 font-medium">Password</label>
                            <a href="forgot-password.php" class="text-sm text-blue-600 hover:underline">Forgot Password?</a>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            required
                        >
                    </div>
                    
                    <div class="flex items-center mb-6">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-gray-700">
                            Remember me
                        </label>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 btn-bg hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                    >
                        Sign In
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="text-blue-600 hover:underline font-medium">
                            Sign up
                        </a>
                    </p>
                </div>
                
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="text-center mb-4">
                        <span class="text-gray-500">Or sign in with</span>
                    </div>
                    <div class="flex justify-center space-x-4">
                        <button class="flex items-center justify-center px-4 py-2 border rounded-lg hover:bg-gray-50 transition duration-300 w-full">
                            <img src="assets/images/google-icon.svg" alt="Google" class="h-5 w-5 mr-2">
                            <span>Google</span>
                        </button>
                        <button class="flex items-center justify-center px-4 py-2 border rounded-lg hover:bg-gray-50 transition duration-300 w-full">
                            <img src="assets/images/facebook-icon.svg" alt="Facebook" class="h-5 w-5 mr-2">
                            <span>Facebook</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

