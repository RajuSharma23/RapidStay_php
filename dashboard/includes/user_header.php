<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidStay User Dashboard</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Main colors */
        :root {
            --primary: #494D8B;
            --primary-dark: #393c6e;
            --primary-light: #5c60a3;
            --white: #ffffff;
        }
        
        .side-navbar{
            position: relative;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
        }
        
        .bg-primary {
            background-color: var(--primary) !important;
        }
        
        .bg-primary-dark {
            background-color: var(--primary-dark) !important;
        }
        
        .bg-primary-light {
            background-color: var(--primary-light) !important;
        }
        
        .text-primary {
            color: var(--primary) !important;
        }
        
        .border-primary {
            border-color: var(--primary) !important;
        }
        
        .hover\:bg-primary-light:hover {
            background-color: var(--primary-light) !important;
        }
        
        .hover\:bg-primary-dark:hover {
            background-color: var(--primary-dark) !important;
        }
        
        .user-profile{
            margin-left:25px
        }
        
        #user-menu-button{
            margin-left: 40px;
            margin-top: 10px;
            width: 50px;
            height: 50px;
        }
        
        .user-menu-button{
            width: 50px;
            height: 50px;
        }
        
        .user-title{
            margin-left: 35px;
            margin-top: 10px;
            margin-bottom: 10px;
            color: white;
            font-size: 15px;
        }

        .dropdown-container {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            z-index: 1000;
            width: 20rem;
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        /* New header and footer styles */
        .admin-header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(73, 77, 139, 0.1);
        }
        
        .admin-sidebar {
            background-color: var(--primary);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar-header, .admin-sidebar-footer {
            background-color: var(--primary-dark);
        }
        
        .admin-nav-link {
            color: var(--white);
            border-radius: 0.375rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .admin-nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-left: 3px solid rgba(255,255,255,0.5);
        }
        
        .admin-nav-link.active {
            background-color: rgba(255,255,255,0.9);
            color: var(--primary);
            border-left: 3px solid var(--white);
        }
        
        .admin-content-container {
            display: flex;
            flex-direction: column;
            
            overflow: auto;
        }
        
        .admin-button {
            background-color: var(--primary);
            color: var(--white);
            transition: all 0.2s;
        }
        
        .admin-button:hover {
            background-color: var(--primary-dark);
        }
        
        .admin-badge {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .dropdown-item:hover {
            background-color: #f3f4ff;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #5c60a3, #494D8B);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex side-navbar h-screen">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col admin-sidebar rounded-r-lg overflow-hidden">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-20 px-4 admin-sidebar-header">
                    <a href="../user/index.php" class="flex items-center">
                        <img src="../../assets/images/Fast & Reliable room service (2).png" alt="RapidStay" class="h-10">
                    </a>
                </div>
                
                <!-- User Profile - New Design -->
                <div class="px-6 py-4 flex flex-col items-center border-b border-primary-light">
                    <div class="relative group">
                        <div class="w-20 h-20 rounded-full bg-white/20 p-1">
                            <div class="w-full h-full rounded-full overflow-hidden border-2 border-white flex items-center justify-center bg-white">
                                <?php if (isset($_SESSION['user_profile_image']) && !empty($_SESSION['user_profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($_SESSION['user_profile_image']); ?>" alt="Profile" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full rounded-full gradient-bg flex items-center justify-center text-white">
                                        <i class="fas fa-user-circle text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                       
                    </div>
                    
                    <div class="mt-3 text-center">
                        <h3 class="text-white font-medium text-lg">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'user'; ?>
                        </h3>
                        <p class="text-white text-sm">User</p>
                    </div>
                    
                    <!-- User dropdown remains functionally the same but is hidden by default -->
                    <div id="user-dropdown" class="hidden absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white z-50" style="top: 130px; left: 220px;">
                        <a href="../admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:bg-opacity-10">Your Profile</a>
                        <a href="../admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:bg-opacity-10">Settings</a>
                        <div class="border-t border-gray-100"></div>
                        <a href="../../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign out</a>
                    </div>
                </div>
                
                <!-- Sidebar Navigation - Enhanced styling -->
                <div class="flex flex-col flex-1 overflow-y-auto px-3 py-6">
                    <nav class="flex-1 space-y-2">
                        <a href="../user/index.php" class="flex items-center px-4 py-3 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active shadow-md' : ''; ?>">
                            <span class="inline-flex items-center justify-center w-8 h-8 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-white text-primary rounded-lg' : 'text-white'; ?>">
                                <i class="fas fa-tachometer-alt"></i>
                            </span>
                            <span class="font-medium">Dashboard</span>
                        </a>
                        
                        <a href="../user/wishlist.php" class="flex items-center px-4 py-3 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active shadow-md' : ''; ?>">
                            <span class="inline-flex items-center justify-center w-8 h-8 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'bg-white text-primary rounded-lg' : 'text-white'; ?>">
                                <i class="fas fa-users"></i>
                            </span>
                            <span class="font-medium">wishlist</span>
                        </a>
                        
                        <a href="../user/bookings.php" class="flex items-center px-4 py-3 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active shadow-md' : ''; ?>">
                            <span class="inline-flex items-center justify-center w-8 h-8 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-white text-primary rounded-lg' : 'text-white'; ?>">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <span class="font-medium">My Bookings</span>
                        </a>
                        
                        <a href="../user/roommates.php" class="flex items-center px-4 py-3 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'active shadow-md' : ''; ?>">
                            <span class="inline-flex items-center justify-center w-8 h-8 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'bg-white text-primary rounded-lg' : 'text-white'; ?>">
                                <i class="fas fa-home"></i>
                            </span>
                            <span class="font-medium">Find Roommate</span>
                        </a>
                    
                        
                        <a href="../user/profile.php" class="flex items-center px-4 py-3 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active shadow-md' : ''; ?>">
                            <span class="inline-flex items-center justify-center w-8 h-8 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-white text-primary rounded-lg' : 'text-white'; ?>">
                                <i class="fas fa-user"></i>
                            </span>
                            <span class="font-medium">My Profile</span>
                        </a>
                    </nav>
                </div>
                
                <!-- Sidebar Footer - Enhanced design -->
                <div class="p-4 admin-sidebar-footer">
                    <a href="../../logout.php" class="flex items-center justify-center text-white hover:text-red-200 py-2 bg-primary-dark/50 rounded-lg transition-all hover:bg-primary-dark">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sidebar -->
        <div class="md:hidden fixed inset-0 z-40 flex bg-black bg-opacity-50 transition-opacity duration-300 ease-linear" id="mobile-sidebar" style="display: none;">
            <div class="relative flex-1 flex flex-col max-w-xs w-full admin-sidebar transform transition ease-in-out duration-300">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="close-sidebar">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
                
                <!-- Mobile Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 admin-sidebar-header">
                    <a href="../user/index.php" class="flex items-center">
                        <img src="../../assets/images/footer-logo.png" alt="RapidStay" class="h-8">
                        
                    </a>
                </div>
                
                <!-- Mobile Sidebar Navigation -->
                <div class="flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 py-4 space-y-1">
                        <a href="../user/index.php" class="flex items-center px-4 py-2 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        <a href="../user/wishlist.php" class="flex items-center px-4 py-2 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active' : ''; ?>">
                            <i class="fas fa-heart mr-3"></i>
                            My Wishlist
                        </a>
                        <a href="../user/bookings.php" class="flex items-center px-4 py-2 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calender-check mr-3"></i>
                            My Bookings
                        </a>
                        <a href="../user/roommates.php" class="flex items-center px-4 py-2 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-friends mr-3"></i>
                            Find Roommate
                        </a>
                        <a href="../user/profile.php" class="flex items-center px-4 py-2 admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user mr-3"></i>
                            My Profile
                        </a>
                        
                        <!-- Other nav links with same styling pattern -->
                        <!-- Copy the same pattern for remaining nav links -->
                    </nav>
                </div>
                
                <!-- Mobile Sidebar Footer -->
                <div class="p-4 admin-sidebar-footer">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-red-200">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
            <div class="flex-shrink-0 w-14"></div>
        </div>
        
        <!-- Main Content Container -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header Bar -->
            <header class="admin-header shadow-sm z-10">
                <div class="flex items-center justify-between px-4 py-3">
                    <!-- Left side: Page title and toggle button -->
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button type="button" id="open-sidebar" class="md:hidden p-2 mr-3 text-primary hover:text-primary-dark focus:outline-none">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <!-- Page Title -->
                        <h1 class="text-xl font-semibold text-primary">
                            <?php
                            $page = basename($_SERVER['PHP_SELF'], '.php');
                            $page_title = ucwords(str_replace('-', ' ', $page));
                            echo $page_title == 'Index' ? 'Dashboard' : $page_title;
                            ?>
                        </h1>
                    </div>
                    
                    <!-- Right side: Notifications, messages, and profile -->
                    <div class="flex items-center space-x-4">
                        <!-- Search button -->
                        <button class="p-2 text-primary hover:text-primary-dark focus:outline-none">
                            <a href="../../explore.php"><- Back to Home</a>
                        </button>
                        
                        <!-- Notifications -->
                        <div class="relative dropdown-container">
                            <button class="p-2 text-primary hover:text-primary-dark focus:outline-none" id="notification-button">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div id="notifications-dropdown" class="dropdown-menu hidden">
                                <div class="px-4 py-2 border-b flex justify-between items-center bg-primary text-white">
                                    <h3 class="font-medium">Notifications</h3>
                                    <span class="text-xs bg-white text-primary rounded-full px-2 py-1">3 new</span>
                                </div>
                                <div class="max-h-60 overflow-y-auto">
                                    <a href="#" class="block px-4 py-3 text-sm hover:bg-gray-100 border-b">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mr-3 mt-1">
                                                <div class="w-8 h-8 rounded-full bg-primary bg-opacity-20 flex items-center justify-center text-primary">
                                                    <i class="fas fa-home"></i>
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </a>
                                    <!-- Other notification items with the same styling pattern -->
                                </div>
                                <div class="border-t">
                                    <a href="../admin/notifications.php" class="block px-4 py-2 text-sm text-center text-primary hover:bg-gray-100">
                                        View all notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        
                        
                        <!-- Profile dropdown -->
                        <div class="relative dropdown-container" id="header-profile-container">
                            <button type="button" class="flex items-center focus:outline-none" id="header-user-menu-button">
                                <div class="flex-shrink-0 h-10 w-10 relative">
                                    <div class="h-full w-full rounded-full overflow-hidden bg-primary bg-opacity-10 flex items-center justify-center">
                                        <?php if (isset($_SESSION['user_profile_image']) && !empty($_SESSION['user_profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($_SESSION['user_profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <div class="h-full w-full rounded-full bg-primary bg-opacity-20 flex items-center justify-center text-white">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-base font-medium text-gray-800">
                                        <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'user'; ?>
                                    </div>
                                    <div class="text-sm font-medium text-gray-500">
                                        <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'user'; ?>
                                    </div>
                                </div>
                                <span class="ml-2 bg-white rounded-full p-1">
                                    <i class="fas fa-chevron-down text-primary"></i>
                                </span>
                            </button>
                            
                            <!-- Dropdown menu -->
                            <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 hidden z-50" id="header-user-dropdown">
                                <a href="../user/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:bg-opacity-10">Your Profile</a>
                                <div class="border-t border-gray-100"></div>
                                <a href="../../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <div class="admin-content-container">
                <!-- Content will be injected here by each page -->
            </div>
        

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar functionality
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const openSidebarBtn = document.getElementById('open-sidebar');
    const closeSidebarBtn = document.getElementById('close-sidebar');

    // Fix for open sidebar button
    if (openSidebarBtn && mobileSidebar) {
        openSidebarBtn.addEventListener('click', function() {
            mobileSidebar.style.display = 'flex'; // Actually show the sidebar
            document.body.style.overflow = 'hidden';
            console.log('Sidebar opened'); // Debug output
        });
    }

    // Fix for close sidebar button
    if (closeSidebarBtn && mobileSidebar) {
        closeSidebarBtn.addEventListener('click', function() {
            mobileSidebar.style.display = 'none';
            document.body.style.overflow = '';
            console.log('Sidebar closed'); // Debug output
        });
    }

    // Add event handlers for all action buttons
    const actionButtons = document.querySelectorAll('.action-btn, .btn, [type="submit"]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            // For non-form submit buttons, add visual feedback
            if (!button.getAttribute('type') || button.getAttribute('type') !== 'submit') {
                // Add visual feedback
                const originalBgColor = button.style.backgroundColor;
                button.style.opacity = '0.8';
                
                setTimeout(() => {
                    button.style.opacity = '1';
                }, 200);
            }
            
            console.log('Button clicked', button.textContent.trim());
        });
    });

    // User dropdown functionality (fixed)
    const headerUserMenuButton = document.getElementById('header-user-menu-button');
    const headerUserDropdown = document.getElementById('header-user-dropdown');
    const sidebarUserMenuButton = document.getElementById('user-menu-button'); 
    const sidebarUserDropdown = document.getElementById('user-dropdown');

    // Handle Header Profile Dropdown
    if (headerUserMenuButton && headerUserDropdown) {
        headerUserMenuButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            headerUserDropdown.classList.toggle('hidden');
            
            // Close other dropdowns
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
            }
            if (messageDropdown && !messageDropdown.classList.contains('hidden')) {
                messageDropdown.classList.add('hidden');
            }
            
            console.log('Header profile clicked');
        });
    }

    // Handle Sidebar Profile Dropdown
    if (sidebarUserMenuButton && sidebarUserDropdown) {
        sidebarUserMenuButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebarUserDropdown.classList.toggle('hidden');
            console.log('Sidebar profile clicked');
        });
    }

    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function(e) {
        // Close header profile dropdown
        if (headerUserDropdown && headerUserMenuButton && 
            !headerUserMenuButton.contains(e.target) && 
            !headerUserDropdown.contains(e.target)) {
            headerUserDropdown.classList.add('hidden');
        }
        
        // Close sidebar profile dropdown
        if (sidebarUserDropdown && sidebarUserMenuButton && 
            !sidebarUserMenuButton.contains(e.target) && 
            !sidebarUserDropdown.contains(e.target)) {
            sidebarUserDropdown.classList.add('hidden');
        }
    });

    // Notifications dropdown
    const notificationButton = document.getElementById('notification-button');
    const notificationDropdown = document.getElementById('notifications-dropdown');

    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            
            // Hide the user dropdown if it's open
            if (headerUserDropdown && !headerUserDropdown.classList.contains('hidden')) {
                headerUserDropdown.classList.add('hidden');
            }
            
            // Hide message dropdown if it's open
            if (messageDropdown && !messageDropdown.classList.contains('hidden')) {
                messageDropdown.classList.add('hidden');
            }
        });
        
        // Close dropdown when clicking anywhere else
        document.addEventListener('click', function(e) {
            if (!notificationButton.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }
    
    // Messages dropdown
    const messageButton = document.getElementById('message-button');
    const messageDropdown = document.getElementById('messages-dropdown');

    if (messageButton && messageDropdown) {
        messageButton.addEventListener('click', function(e) {
            e.stopPropagation();
            messageDropdown.classList.toggle('hidden');
            
            // Hide other dropdowns
            if (headerUserDropdown && !headerUserDropdown.classList.contains('hidden')) {
                headerUserDropdown.classList.add('hidden');
            }
            
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
            }
        });
        
        // Close dropdown when clicking anywhere else
        document.addEventListener('click', function(e) {
            if (!messageButton.contains(e.target) && !messageDropdown.contains(e.target)) {
                messageDropdown.classList.add('hidden');
            }
        });
    }
    
    // Search functionality
    const searchButton = document.querySelector('.fa-search').parentElement;
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            const searchTerm = prompt('Enter search term:');
            if (searchTerm) {
                // You can implement actual search functionality here
                console.log('Searching for:', searchTerm);
            }
        });
    }
});
</script>
</body>
</html>

