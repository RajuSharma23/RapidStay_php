<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header('X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");

// Enforce HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    if (!headers_sent()) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}
// // Check if user is admin and redirect accordingly
// if (isset($user['role']) && strtolower($user['role']) === 'admin') {
//     header("Location: dashboard/admin/index.php");
//     exit;
// } 
// elseif (isset($user['role']) && strtolower($user['role']) === 'owner') {
//     header("Location: dashboard/owner/index.php");
//     exit;
// }
// elseif (isset($user['role']) && strtolower($user['role']) === 'tenant') {
//     header("Location: dashboard/tenant/index.php");
//     exit;
// }
// else {
//     // Regular user - redirect to dashboard
//     header("Location: index.php");
//     exit;
// }



?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover comfortable rooms, reliable roommates, and quality PG accommodations all in one place.">
    <title>RapidStay - Find Your Perfect Stay</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    
    <!-- Preload critical assets -->
    <link rel="preload" href="assets/images/logo.png" as="image">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" as="style">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Defer non-critical JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center">
                    <img src="assets/images/logo.png" alt="RapidStay" class="h-8">
                    <span class="ml-2 text-xl font-bold text-blue-600">RapidStay</span>
                </a>
                
                <!-- Navigation - Desktop -->
                
                
                <!-- User Menu -->
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="wishlist.php" class="text-gray-700 hover:text-blue-600 mr-4 relative" aria-label="Wishlist">
                            <i class="far fa-heart text-xl"></i>
                            <?php
                            // Get wishlist count
                            require_once 'includes/db_connect.php';
                            $user_id = $_SESSION['user_id'];
                            $wishlist_query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id";
                            $wishlist_result = mysqli_query($conn, $wishlist_query);
                            $wishlist_count = mysqli_fetch_assoc($wishlist_result)['count'];
                            
                            if ($wishlist_count > 0):
                            ?>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" aria-label="<?php echo $wishlist_count; ?> items in wishlist">
                                    <?php echo $wishlist_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="relative">
                            <button 
                                class="flex items-center focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md px-2 py-1"
                                aria-expanded="false"
                                aria-haspopup="true"
                                id="user-menu-button">
                                <span class="mr-2 hidden md:block"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            </button>
                            <div 
                                id="user-menu-dropdown"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden"
                                role="menu"
                                aria-orientation="vertical"
                                aria-labelledby="user-menu-button">
                                
                                <?php if (isset($_SESSION['user_type'])): ?>
                                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                        <a href="dashboard/admin/index.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i> Admin Dashboard
                                        </a>
                                        <a href="dashboard/admin/user-management.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-users-cog mr-2" aria-hidden="true"></i> User Management
                                        </a>
                                    <?php elseif ($_SESSION['user_type'] === 'owner'): ?>
                                        <a href="dashboard/owner/index.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i> Owner Dashboard
                                        </a>
                                        <a href="dashboard/owner/my-listings.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-home mr-2" aria-hidden="true"></i> My Listings
                                        </a>
                                        <a href="dashboard/owner/bookings.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-calendar-check mr-2" aria-hidden="true"></i> Booking Requests
                                        </a>
                                    <?php elseif ($_SESSION['user_type'] === 'tenant'): ?>
                                        <a href="dashboard/tenant/index.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i> My Dashboard
                                        </a>
                                        <a href="dashboard/tenant/bookings.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-calendar-alt mr-2" aria-hidden="true"></i> My Bookings
                                        </a>
                                        <a href="dashboard/tenant/wishlist.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-heart mr-2" aria-hidden="true"></i> My Wishlist
                                        </a>
                                        <a href="dashboard/tenant/roommates.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                            <i class="fas fa-user-friends mr-2" aria-hidden="true"></i> Find Roommate
                                        </a>
                                    <?php endif; ?>

                                    <!-- Common menu items for all user types -->
                                    <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                        <i class="fas fa-user-circle mr-2" aria-hidden="true"></i> My Profile
                                    </a>
                                    <div class="border-t border-gray-100 my-1" role="separator"></div>
                                    <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 focus:bg-blue-50 focus:text-blue-600 focus:outline-none" role="menuitem">
                                        <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i> Logout
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 transition duration-300 mr-4 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md px-2 py-1">Login</a>
                        <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500">Register</a>
                    <?php endif; ?>
                    
                    <!-- Mobile Menu Button -->
                    <button 
                        class="ml-4 md:hidden focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1" 
                        id="mobile-menu-button"
                        aria-expanded="false"
                        aria-label="Toggle navigation menu">
                        <i class="fas fa-bars text-gray-700 text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Navigation Menu -->
            <div 
                class="md:hidden hidden w-full absolute left-0 top-16 bg-white border-t shadow-lg" 
                id="mobile-menu"
                role="menu"
                aria-labelledby="mobile-menu-button">
                <div class="py-3 space-y-1 border-t">
                    <a href="index.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Home</a>
                    <a href="explore.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Explore</a>
                    <a href="explore.php?type=room" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 pl-8">Rooms</a>
                    <a href="explore.php?type=roommate" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 pl-8">Roommates</a>
                    <a href="explore.php?type=pg" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 pl-8">PG Accommodations</a>
                    <a href="about.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">About</a>
                    <a href="contact.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Contact</a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Dashboard</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">My Profile</a>
                        <a href="bookings.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">My Bookings</a>
                        <a href="wishlist.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Wishlist</a>
                        <?php if ($_SESSION['user_type'] === 'owner'): ?>
                            <a href="my-listings.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">My Listings</a>
                        <?php endif; ?>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Logout</a>
                    <?php else: ?>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Login</a>
                        <a href="register.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // User menu functionality
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');

    if (userMenuButton && userMenuDropdown) {
        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = userMenuButton.getAttribute('aria-expanded') === 'true';
            userMenuButton.setAttribute('aria-expanded', !isExpanded);
            userMenuDropdown.classList.toggle('hidden');
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!userMenuDropdown.contains(e.target) && !userMenuButton.contains(e.target)) {
                userMenuDropdown.classList.add('hidden');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });

        // Keyboard navigation
        userMenuDropdown.addEventListener('keydown', (e) => {
            const menuItems = userMenuDropdown.querySelectorAll('[role="menuitem"]');
            const currentIndex = Array.from(menuItems).indexOf(document.activeElement);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < menuItems.length - 1) menuItems[currentIndex + 1].focus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) menuItems[currentIndex - 1].focus();
                    break;
                case 'Escape':
                    userMenuDropdown.classList.add('hidden');
                    userMenuButton.setAttribute('aria-expanded', 'false');
                    userMenuButton.focus();
                    break;
            }
        });
    }

    // Mobile menu functionality
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            const isExpanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
            mobileMenuButton.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) { // md breakpoint
                mobileMenu.classList.add('hidden');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>

    <!-- Main Content -->
    <main class="flex-grow">

