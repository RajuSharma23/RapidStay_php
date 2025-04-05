<?php
// Start session for user authentication
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
  // Redirect to home page
  header("Location: index.php");
  exit();
}

// Check for redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Process login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Database connection
  require_once 'includes/db_connect.php';
  
  $email = $_POST['email'];
  $password = $_POST['password'];
  
  // Validate input
  if (empty($email) || empty($password)) {
    $error = 'Please enter both email and password.';
  } else {
    // Check user credentials
    $sql = "SELECT * FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) === 1) {
      $user = mysqli_fetch_assoc($result);
      
      // Verify password
      if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Redirect to requested page or home
        header("Location: " . $redirect);
        exit();
      } else {
        $error = 'Invalid password.';
      }
    } else {
      $error = 'User not found.';
    }
  }
}

// Include header
include 'includes/header.php';
?>

<div class="container flex items-center justify-center min-h-[80vh] py-12 mx-auto px-4">
  <div class="w-full max-w-md bg-white rounded-lg border shadow-sm">
    <div class="p-6 space-y-4">
      <div class="space-y-1 text-center">
        <h2 class="text-2xl font-bold">Sign in</h2>
        <p class="text-sm text-gray-500">Enter your email and password to access your account</p>
      </div>
      
      <?php if (!empty($error)): ?>
        <div class="bg-red-50 text-red-500 p-3 rounded-md text-sm">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="signin.php?redirect=<?php echo urlencode($redirect); ?>" class="space-y-4">
        <div class="space-y-2">
          <label for="email" class="block text-sm font-medium">Email</label>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="name@example.com"
            class="w-full border border-gray-300 rounded-md p-2"
            required
          />
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm font-medium">Password</label>
            <a href="forgot-password.php" class="text-sm text-gray-500 hover:underline">
              Forgot password?
            </a>
          </div>
          <input
            id="password"
            name="password"
            type="password"
            class="w-full border border-gray-300 rounded-md p-2"
            required
          />
        </div>
        <div class="flex items-center space-x-2">
          <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300" />
          <label for="remember" class="text-sm font-normal">
            Remember me
          </label>
        </div>
        <button type="submit" class="w-full bg-black text-white py-2 rounded-md hover:bg-gray-800 transition">
          Sign in
        </button>
      </form>
      <div class="mt-4 text-center text-sm">
        Don't have an account?{" "}
        <a href="register.php" class="underline hover:text-black">
          Sign up
        </a>
      </div>
    </div>
  </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

