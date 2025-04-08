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
    .side-navbar{
            position: fixed;
        }
        #user-menu-button{
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color:rgb(15, 89, 216); /* Tailwind gray-700 */
            margin-left:80px;
            margin-top: 20px;

            

        }
        .user-icon{
            width: 80px;
            height: 80px;
        }
        .user-name{
            font-size: 20px;
            color: white;
            margin-left: 55px;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 10px;
        }
</style>

</head>
<body class="bg-gray-100">
    <div class="flex  side-navbar h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-blue-800">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-blue-900">
                    <a href="../user/index.php" class="flex items-center">
                        <img src="../../assets/images/footer-logo.png" alt="RapidStay" class="h-8">
                        <!-- <span class="ml-2 text-xl font-bold text-white">RapidStay</span> -->
                    </a>
                </div>
                
                <!-- Sidebar Navigation -->
                <div class="flex flex-col flex-1 overflow-y-auto">

                <button 
                        class="flex items-center focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg px-2 py-1" 
                        id="user-menu-button"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        
                        <?php if (isset($_SESSION['user_profile_image']) && !empty($_SESSION['user_profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['user_profile_image']); ?>" 
                                 alt="Profile" 
                                 class="w-8 h-8  rounded-full object-cover">
                        <?php else: ?>
                            <div class=" user-icon rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-user "></i>
                            </div>
                        <?php endif; ?>
                    <!-- Page Title -->
                <h1 class="text-lg font-semibold md:hidden">
                    <?php
                    $page = basename($_SERVER['PHP_SELF'], '.php');
                    $page_title = ucwords(str_replace('-', ' ', $page));
                    echo $page_title == 'Index' ? 'Dashboard' : $page_title;
                    ?>
                </h1>
                    </button>
 
                    <span class="mr-2 text-sm user-name font-medium text-gray-700 hidden md:block">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                        </span>
                    <nav class="flex-1 px-2 py-4 space-y-1">
                        <a href="../user/index.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../user/wishlist.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-heart mr-3"></i>
                            My Wishlist
                        </a>
                        
                        <a href="../user/bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            My Bookings
                        </a>
                        
                        <a href="../user/roommates.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-friends mr-3"></i>
                            Find Roommate
                        </a>
                        
                        <a href="../user/profile.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-circle mr-3"></i>
                            My Profile
                        </a>
                    </nav>
                </div>
                
                <!-- Sidebar Footer -->
                <div class="p-4 bg-blue-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-gray-300">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sidebar -->
        <div class="md:hidden fixed inset-0 z-40 flex bg-black bg-opacity-50 transition-opacity duration-300 ease-linear" id="mobile-sidebar" style="display: none;">
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-blue-800 transform transition ease-in-out duration-300">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="close-sidebar">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
                
                <!-- Mobile Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-blue-900">
                    <a href="../user/index.php" class="flex items-center">
                        <img src="../../assets/images/logo-white.png" alt="RapidStay" class="h-8">
                        <!-- <span class="ml-2 text-xl font-bold text-white">RapidStay</span> -->
                    </a>
                </div>
                
                <!-- Mobile Sidebar Navigation -->
                <div class="flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 py-4 space-y-1">
                        <a href="../user/index.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../user/wishlist.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-heart mr-3"></i>
                            My Wishlist
                        </a>
                        
                        <a href="../user/bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            My Bookings
                        </a>
                        
                        <a href="../user/roommates.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-friends mr-3"></i>
                            Find Roommate
                        </a>
                        
                        <a href="../user/profile.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-circle mr-3"></i>
                            My Profile
                        </a>
                    </nav>
                </div>
                
                <!-- Mobile Sidebar Footer -->
                <div class="p-4 bg-blue-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-gray-300">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
            <div class="flex-shrink-0 w-14"></div>
        </div>
        
        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            
</div>
</div>

<script>
    // Mobile sidebar toggle
    document.getElementById('open-sidebar').addEventListener('click', function() {
        document.getElementById('mobile-sidebar').style.display = 'flex';
    });
    
    document.getElementById('close-sidebar').addEventListener('click', function() {
        document.getElementById('mobile-sidebar').style.display = 'none';
    });
    
    // User menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');
        const chevronIcon = userMenuButton.querySelector('.fa-chevron-down');
        let isOpen = false;

        function toggleMenu(show) {
            isOpen = show;
            userMenuButton.setAttribute('aria-expanded', show);
            
            if (show) {
                userMenuDropdown.classList.remove('hidden', 'opacity-0', 'scale-95');
                userMenuDropdown.classList.add('opacity-100', 'scale-100');
                chevronIcon.style.transform = 'rotate(180deg)';
            } else {
                userMenuDropdown.classList.add('opacity-0', 'scale-95');
                chevronIcon.style.transform = 'rotate(0)';
                setTimeout(() => {
                    if (!isOpen) {
                        userMenuDropdown.classList.add('hidden');
                    }
                }, 200);
            }
        }

        // Toggle menu on button click
        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu(!isOpen);
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                toggleMenu(false);
            }
        });

        // Handle keyboard navigation
        userMenuDropdown.addEventListener('keydown', (e) => {
            const menuItems = userMenuDropdown.querySelectorAll('[role="menuitem"]');
            const currentIndex = Array.from(menuItems).indexOf(document.activeElement);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < menuItems.length - 1) {
                        menuItems[currentIndex + 1].focus();
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        menuItems[currentIndex - 1].focus();
                    }
                    break;
                case 'Escape':
                    toggleMenu(false);
                    userMenuButton.focus();
                    break;
            }
        });

        // Close menu when tabbing out
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && isOpen) {
                const menuItems = userMenuDropdown.querySelectorAll('[role="menuitem"]');
                const lastMenuItem = menuItems[menuItems.length - 1];
                
                if (e.shiftKey && document.activeElement === userMenuButton) {
                    toggleMenu(false);
                } else if (!e.shiftKey && document.activeElement === lastMenuItem) {
                    toggleMenu(false);
                }
            }
        });
    });
</script>

