<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidStay Admin Dashboard</title>
    
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
            width: 100%;
             
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
        
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex side-navbar h-screen ">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col  bg-gray-800">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-gray-900">
                    <a href="../admin/index.php" class="flex items-center">
                        <img src="../../assets/images/Fast & Reliable room service (2).png" alt="RapidStay" class="h-8">
                        <!-- <span class="ml-2 text-xl font-bold text-white">RapidStay</span> -->
                    </a>
                </div>
                
                <!-- Sidebar Navigation -->
                <div class="flex flex-col flex-1 overflow-y-auto">
                    <nav class="flex-1 px-2 py-4 space-y-1">
                    <div class="user-profile ">

                    <button 
                        class=" items-center space-x-3     px-2 py-1" 
                        id="user-menu-button"
                        aria-expanded="false"
                        aria-haspopup="true">
                    
                        <div class=" user-menu-button rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fas fa-user"></i>
                        </div>
                    </button>
                    <span class="mr-2 text-sm font-medium user-title hidden md:block">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?>
                        </span>

                         <!-- Page Title -->
                <h1 class="text-lg font-semibold md:hidden">
                    <?php
                    $page = basename($_SERVER['PHP_SELF'], '.php');
                    $page_title = ucwords(str_replace('-', ' ', $page));
                    echo $page_title == 'Index' ? 'Dashboard' : $page_title;
                    ?>
                </h1>
                    
                    
                    </div>

                        <a href="../admin/index.php" class="flex items-center px-4 py-2 border-top text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../admin/user-management.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'user-management.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-users mr-3"></i>
                            User Management
                        </a>
                        
                        <a href="../admin/pg-approval.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'pg-approval.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-check-circle mr-3"></i>
                            PG Approval
                        </a>
                        
                        <a href="../admin/pg-listings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'pg-listings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-home mr-3"></i>
                            PG Listings
                        </a>
                        
                        <a href="../admin/manage-bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Bookings
                        </a>
                        
                        <a href="../admin/roommates.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-user-friends mr-3"></i>
                            Roommates
                        </a>
                        
                        <a href="../admin/settings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-cog mr-3"></i>
                            Settings
                        </a>
                    </nav>
                </div>
                
                <!-- Sidebar Footer -->
                <div class="p-4 bg-gray-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-red-600">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sidebar -->
        <div class="md:hidden fixed inset-0 z-40 flex bg-black bg-opacity-50 transition-opacity duration-300 ease-linear" id="mobile-sidebar" style="display: none;">
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-gray-800 transform transition ease-in-out duration-300">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="close-sidebar">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
                
                <!-- Mobile Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-gray-900">
                    <a href="../admin/index.php" class="flex items-center">
                        <img src="../../assets/images/logo-white.png" alt="RapidStay" class="h-8">
                        <span class="ml-2 text-xl font-bold text-white">RapidStay</span>
                    </a>
                </div>
                
                <!-- Mobile Sidebar Navigation -->
                <div class="flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 py-4 space-y-1">
                        <a href="../admin/index.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../admin/user-management.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'user-management.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-users mr-3"></i>
                            User Management
                        </a>
                        
                        <a href="../admin/pg-approval.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'pg-approval.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-check-circle mr-3"></i>
                            PG Approval
                        </a>
                        
                        <a href="../admin/pg-listings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'pg-listings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-home mr-3"></i>
                            PG Listings
                        </a>
                        
                        <a href="../admin/bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Bookings
                        </a>
                        
                        <a href="../admin/roommates.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'roommates.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-user-friends mr-3"></i>
                            Roommates
                        </a>
                        
                        <a href="../admin/settings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-cog mr-3"></i>
                            Settings
                        </a>
                    </nav>
                </div>
                
                <!-- Mobile Sidebar Footer -->
                <div class="p-4 bg-gray-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-gray-300">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
            <div class="flex-shrink-0 w-14"></div>
        </div>
        
        <!-- Main Content Container -->
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
});
</script>
</body>
</html>

