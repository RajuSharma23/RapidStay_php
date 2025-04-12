<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/pg-approval.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';

// Process approval actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Approve PG listing
        if ($_POST['action'] === 'approve' && isset($_POST['listing_id'])) {
            $listing_id = intval($_POST['listing_id']);
            
            // Update listing status - USING PREPARED STATEMENT FOR SAFETY
            $update_query = "UPDATE listings SET is_verified = 1, status = 'approved' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $listing_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_stmt_close($update_stmt);
                
                // Get listing details
                $listing_query = "SELECT l.title, u.id as owner_id, u.email, u.name 
                                 FROM listings l 
                                 JOIN users u ON l.user_id = u.id 
                                 WHERE l.id = ?";
                                 
                $stmt = mysqli_prepare($conn, $listing_query);
                mysqli_stmt_bind_param($stmt, "i", $listing_id);
                mysqli_stmt_execute($stmt);
                $listing_result = mysqli_stmt_get_result($stmt);
                
                if ($listing = mysqli_fetch_assoc($listing_result)) {
                    // Send notification to owner
                    $owner_id = $listing['owner_id'];
                    $admin_id = $_SESSION['user_id'];
                    $notification_message = sprintf(
                        "Your PG listing '%s' has been approved and is now visible to users.",
                        mysqli_real_escape_string($conn, $listing['title'])
                    );
                    
                    $notification_query = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())";
                                         
                    $notify_stmt = mysqli_prepare($conn, $notification_query);
                    mysqli_stmt_bind_param($notify_stmt, "iiis", $admin_id, $owner_id, $listing_id, $notification_message);
                    
                    if (mysqli_stmt_execute($notify_stmt)) {
                        $message = "PG listing has been approved successfully.";
                    } else {
                        $error = "Listing approved, but failed to send notification: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($notify_stmt);
                } else {
                    $message = "PG listing has been approved successfully.";
                }
            } else {
                $error = "Failed to approve PG listing: " . mysqli_error($conn);
            }
        }
        
        // Reject PG listing
        if ($_POST['action'] === 'reject' && isset($_POST['listing_id']) && isset($_POST['rejection_reason'])) {
            $listing_id = intval($_POST['listing_id']);
            $rejection_reason = $_POST['rejection_reason'];
            
            // Get listing details
            $listing_query = "SELECT l.title, u.id as owner_id, u.email, u.name 
                             FROM listings l 
                             JOIN users u ON l.user_id = u.id 
                             WHERE l.id = ?";
                             
            $stmt = mysqli_prepare($conn, $listing_query);
            mysqli_stmt_bind_param($stmt, "i", $listing_id);
            mysqli_stmt_execute($stmt);
            $listing_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($listing_result) > 0) {
                $listing = mysqli_fetch_assoc($listing_result);
                
                // Send notification to owner
                $owner_id = $listing['owner_id'];
                $admin_id = $_SESSION['user_id'];
                $notification_message = sprintf(
                    "Your PG listing '%s' has been rejected for the following reason: %s",
                    mysqli_real_escape_string($conn, $listing['title']),
                    mysqli_real_escape_string($conn, $rejection_reason)
                );
                
                $notification_query = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())";
                                      
                $stmt = mysqli_prepare($conn, $notification_query);
                mysqli_stmt_bind_param($stmt, "iiis", $admin_id, $owner_id, $listing_id, $notification_message);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Rejection notification has been sent to the owner.";
                } else {
                    $error = "Failed to send rejection notification. Please try again.";
                }
                
                mysqli_stmt_close($stmt);
            } else {
                $error = "PG listing not found.";
            }
        }
    }
}

// Get pending PG listings
$pending_query = "SELECT l.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone 
                 FROM listings l 
                 JOIN users u ON l.user_id = u.id 
                 WHERE l.is_verified = 0 
                 ORDER BY l.created_at DESC";
$pending_result = mysqli_query($conn, $pending_query);

// Include header
include '../includes/admin_header.php';
?>

<style>
    .main-item{
        margin-top: 50px;
        margin-left:-250px;

        
    }
    
/* Add these styles in your existing <style> section */
@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@keyframes slideOut {
    from { transform: translateX(0); }
    to { transform: translateX(100%); }
}

.notification-slide-in {
    animation: slideIn 0.5s forwards;
}

.notification-slide-out {
    animation: slideOut 0.5s forwards;
}
</style>
<link rel="stylesheet" href="../../assets/css/style.css">

<!-- Main Content -->
<div class="flex-1 p-8  overflow-auto">
    <div class="mb-8  ">
        <h1 class="text-2xl font-bold">PG Approval System</h1>
        <p class="text-gray-600">Review and approve PG listings submitted by owners</p>
    </div>
    
    
    
    <!-- Pending PG Listings -->
    <div class="bg-white border-top rounded-lg shadow-sm  overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-bold">Pending PG Listings</h2>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                <?php echo mysqli_num_rows($pending_result); ?> Pending
            </span>
        </div>
        
        <?php if (mysqli_num_rows($pending_result) > 0): ?>
            <div class="divide-y">
                <?php while ($listing = mysqli_fetch_assoc($pending_result)): ?>
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row">
                            <!-- Listing Image -->
                            <div class="w-full md:w-1/4 mb-4 md:mb-0 md:mr-6">
                                <?php
                                // Get primary image
                                $image_query = "SELECT image_url FROM listing_images WHERE listing_id = " . $listing['id'] . " AND is_primary = 1 LIMIT 1";
                                $image_result = mysqli_query($conn, $image_query);
                                if (mysqli_num_rows($image_result) > 0) {
                                    $image = mysqli_fetch_assoc($image_result)['image_url'];
                                    echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($listing['title']) . '" class="w-full h-48 object-cover rounded-lg">';
                                } else {
                                    echo '<div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500"><i class="fas fa-home text-3xl"></i></div>';
                                }
                                ?>
                            </div>
                            
                            <!-- Listing Details -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            <?php echo htmlspecialchars($listing['address'] . ', ' . $listing['locality'] . ', ' . $listing['city']); ?>
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-rupee-sign mr-2"></i>
                                            <?php echo number_format($listing['price']); ?> / month
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-home mr-2"></i>
                                            Type: <?php echo ucfirst($listing['type']); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-user mr-2"></i>
                                            Owner: <?php echo htmlspecialchars($listing['owner_name']); ?>
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-envelope mr-2"></i>
                                            Email: <?php echo htmlspecialchars($listing['owner_email']); ?>
                                        </p>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-phone mr-2"></i>
                                            Phone: <?php echo htmlspecialchars($listing['owner_phone']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 class="font-semibold mb-2">Description</h4>
                                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                                </div>
                                
                                <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                                    <div class="text-sm text-gray-500 mb-4 md:mb-0">
                                        Submitted on <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                                    </div>
                                    <div class="flex space-x-3">
                                        <a href="view-listing.php?id=<?php echo $listing['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                            View Details
                                        </a>
                                        <button onclick="approveListingConfirm(<?php echo $listing['id']; ?>, '<?php echo htmlspecialchars($listing['title']); ?>')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                            Approve
                                        </button>
                                        <button onclick="rejectListingModal(<?php echo $listing['id']; ?>, '<?php echo htmlspecialchars($listing['title']); ?>')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="p-6 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">No Pending Approvals</h3>
                <p class="text-gray-600">
                    All PG listings have been reviewed. Check back later for new submissions.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Listing Confirmation Modal -->
<div id="approve-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="p-6">
            <h3 class="font-bold text-lg mb-4">Confirm Approval</h3>
            <p class="mb-6">Are you sure you want to approve the PG listing "<span id="approve-listing-title" class="font-semibold"></span>"?</p>
            
            <form action="pg-approval.php" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="listing_id" id="approve-listing-id">
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeApproveModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Listing Modal -->
<div id="reject-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="p-6">
            <h3 class="font-bold text-lg mb-4">Reject PG Listing</h3>
            <p class="mb-4">Please provide a reason for rejecting "<span id="reject-listing-title" class="font-semibold"></span>":</p>
            
            <form action="pg-approval.php" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="listing_id" id="reject-listing-id">
                
                <div class="mb-4">
                    <textarea 
                        name="rejection_reason" 
                        rows="4" 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" 
                        placeholder="Enter rejection reason..."
                        required
                    ></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeRejectModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Approve Listing Modal
    function approveListingConfirm(listingId, listingTitle) {
        document.getElementById('approve-listing-id').value = listingId;
        document.getElementById('approve-listing-title').textContent = listingTitle;
        document.getElementById('approve-modal').classList.remove('hidden');
    }
    
    function closeApproveModal() {
        document.getElementById('approve-modal').classList.add('hidden');
    }
    
    // Reject Listing Modal
    function rejectListingModal(listingId, listingTitle) {
        document.getElementById('reject-listing-id').value = listingId;
        document.getElementById('reject-listing-title').textContent = listingTitle;
        document.getElementById('reject-modal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
    }

    // Add this to your existing script section
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we need to show a notification
        <?php if (!empty($message) && $message == "PG listing has been approved successfully."): ?>
            showNotification("<?php echo $message; ?>", "success");
        <?php endif; ?>
        
        // Notification function with slide animation
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 -right-full p-4 rounded-lg shadow-lg z-50 transition-all duration-500 transform';
            
            // Style based on notification type
            if (type === 'success') {
                notification.classList.add('bg-green-500', 'text-white');
            } else if (type === 'error') {
                notification.classList.add('bg-red-500', 'text-white');
            }
            
            // Add message and dismiss button
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>${message}</span>
                    <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Add to DOM
            document.body.appendChild(notification);
            
            // Trigger slide-in animation
            setTimeout(() => {
                notification.classList.remove('-right-full');
                notification.classList.add('right-4');
            }, 100);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                notification.classList.remove('right-4');
                notification.classList.add('-right-full');
                
                // Remove from DOM after animation completes
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 5000);
        }
    });
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>

