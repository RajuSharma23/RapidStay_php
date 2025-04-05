<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidStay PG Owner Dashboard</title>
    
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
            margin-left: 50px;

            

        }
        .user-icon{
            width: 80px;
            height: 80px;
        }
        .user-name{
            font-size: 20px;
            color: white;
            margin-left: 35px;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex side-navbar h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col  bg-green-800">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-green-900">
                    <a href="../owner/index.php" class="flex items-center">
                        <img src="../../assets/images/footer-logo.png" alt="RapidStay" class="h-8">
                        <!-- <span class="ml-2 text-xl font-bold text-white">RapidStay</span> -->
                    </a>
                </div>
                
                <!-- Sidebar Navigation -->
                <div class="flex flex-col flex-1 overflow-y-auto">
                    <nav class="flex-1 px-2 py-4 space-y-1">
                    <div class=" items-center">
                        
                        <button class=" items-center focus:outline-none" id="user-menu-button">
                            <?php if (isset($_SESSION['user_profile_image']) && !empty($_SESSION['user_profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['user_profile_image']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div  class=" user-icon rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                        </button>
                        <span class="mr-2 user-name   hidden md:block">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Owner'; ?>
                        </span>
                        <h1 class="text-lg font-semibold md:hidden">
                    <?php
                    $page = basename($_SERVER['PHP_SELF'], '.php');
                    $page_title = ucwords(str_replace('-', ' ', $page));
                    echo $page_title == 'Index' ? 'Dashboard' : $page_title;
                    ?>
                </h1>
                    </div>
                        <a href="../owner/index.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../owner/my-listings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'my-listings.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-home mr-3"></i>
                            My Listings
                        </a>
                        
                        <a href="../owner/add-listing.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'add-listing.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-plus-circle mr-3"></i>
                            Add New PG
                        </a>
                        
                        <a href="../owner/bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Booking Requests
                        </a>
                        
                        <a href="../owner/booking-history.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'booking-history.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-history mr-3"></i>
                            Booking History
                        </a>
                        
                        <a href="../owner/staff-management.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-user-tie mr-3"></i>
                            Staff Management
                        </a>
                        
                        <a href="../owner/profile.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-user-circle mr-3"></i>
                            My Profile
                        </a>
                    </nav>
                </div>
                
                <!-- Sidebar Footer -->
                <div class="p-4 bg-green-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-gray-300">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sidebar -->
        <div class="md:hidden fixed inset-0 z-40 flex bg-black bg-opacity-50 transition-opacity duration-300 ease-linear" id="mobile-sidebar" style="display: none;">
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-green-800 transform transition ease-in-out duration-300">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="close-sidebar">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
                
                <!-- Mobile Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-green-900">
                    <a href="../owner/index.php" class="flex items-center">
                        <img src="../../assets/images/logo-white.png" alt="RapidStay" class="h-8">
                        <span class="ml-2 text-xl font-bold text-white">RapidStay</span>
                    </a>
                </div>
                
                <!-- Mobile Sidebar Navigation -->
                <div class="flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 py-4 space-y-1">
                        <a href="../owner/index.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="../owner/my-listings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'my-listings.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-home mr-3"></i>
                            My Listings
                        </a>
                        
                        <a href="../owner/add-listing.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'add-listing.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-plus-circle mr-3"></i>
                            Add New PG
                        </a>
                        
                        <a href="../owner/bookings.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Booking Requests
                        </a>
                        
                        <a href="../owner/booking-history.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'booking-history.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-history mr-3"></i>
                            Booking History
                        </a>
                        
                        <a href="../owner/staff.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-user-tie mr-3"></i>
                            Staff Management
                        </a>
                        
                        <a href="../owner/profile.php" class="flex items-center px-4 py-2 text-white rounded-md hover:bg-green-700 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-green-700' : ''; ?>">
                            <i class="fas fa-user-circle mr-3"></i>
                            My Profile
                        </a>
                    </nav>
                </div>
                
                <!-- Mobile Sidebar Footer -->
                <div class="p-4 bg-green-900">
                    <a href="../../logout.php" class="flex items-center text-white hover:text-gray-300">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
            <div class="flex-shrink-0 w-14"></div>
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
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    
    userMenuButton.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
    });
    
    // Close user menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.classList.add('hidden');
        }
    });

    function updateStaffStatus(staffId, status) {
        const leaveDatesDiv = document.getElementById(`leave-dates-${staffId}`);
        if (status === 'on_leave') {
            leaveDatesDiv.classList.remove('hidden');
        } else {
            leaveDatesDiv.classList.add('hidden');
        }
        
        // Send AJAX request to update status
        fetch('staff-management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_staff&staff_id=${staffId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Status updated successfully', 'success');
            } else {
                showMessage('Failed to update status', 'error');
            }
        });
    }

    function updateLeaveDate(staffId, type, date) {
        fetch('staff-management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_staff&staff_id=${staffId}&${type}_date=${date}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Leave dates updated successfully', 'success');
            } else {
                showMessage('Failed to update leave dates', 'error');
            }
        });
    }

    function showMessage(message, type) {
        const popup = document.createElement('div');
        popup.className = `message-popup ${type} show`;
        popup.textContent = message;
        document.body.appendChild(popup);
        
        setTimeout(() => {
            popup.remove();
        }, 3000);
    }
</script>
</body>
</html>

