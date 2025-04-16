<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/view-listing.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';
require_once '../../includes/image_helpers.php';

// Check if listing ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my-listings.php?error=invalid_id");
    exit();
}

$listing_id = intval($_GET['id']);

// Process actions (approve/reject/delete)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle approval
        if ($_POST['action'] === 'approve') {
            $update_query = "UPDATE listings SET is_verified = 1 WHERE id = $listing_id";
            
            if (mysqli_query($conn, $update_query)) {
                // Log the approval
                $admin_id = $_SESSION['user_id'];
                $log_query = "INSERT INTO admin_actions (admin_id, listing_id, action_type, created_at) 
                             VALUES ($admin_id, $listing_id, 'approve', NOW())";
                mysqli_query($conn, $log_query);
                
                // Send notification to owner
                $owner_notification = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                                     SELECT {$_SESSION['user_id']}, user_id, id, 'Your listing has been approved and is now live.', NOW() 
                                     FROM listings WHERE id = $listing_id";
                mysqli_query($conn, $owner_notification);
                
                $message = "Listing has been approved successfully.";
            } else {
                $error = "Error approving listing: " . mysqli_error($conn);
            }
        }
        
        // Handle rejection
        elseif ($_POST['action'] === 'reject' && isset($_POST['rejection_reason'])) {
            $reason = mysqli_real_escape_string($conn, $_POST['rejection_reason']);
            $update_query = "UPDATE listings SET is_verified = 2 WHERE id = $listing_id";
            
            if (mysqli_query($conn, $update_query)) {
                // Log the rejection
                $admin_id = $_SESSION['user_id'];
                $log_query = "INSERT INTO admin_actions (admin_id, listing_id, action_type, notes, created_at) 
                             VALUES ($admin_id, $listing_id, 'reject', '$reason', NOW())";
                mysqli_query($conn, $log_query);
                
                // Send notification to owner
                $owner_notification = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                                     SELECT {$_SESSION['user_id']}, user_id, id, 'Your listing has been rejected. Reason: $reason', NOW() 
                                     FROM listings WHERE id = $listing_id";
                mysqli_query($conn, $owner_notification);
                
                $message = "Listing has been rejected successfully.";
            } else {
                $error = "Error rejecting listing: " . mysqli_error($conn);
            }
        }
        
        // Handle deletion
        elseif ($_POST['action'] === 'delete') {
            // Delete related records
            mysqli_query($conn, "DELETE FROM listing_amenities WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM listing_images WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM messages WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM reviews WHERE listing_id = $listing_id");
            
            // Delete the listing
            if (mysqli_query($conn, "DELETE FROM listings WHERE id = $listing_id")) {
                // Redirect to listings page
                header("Location: my-listings.php?message=listing_deleted");
                exit();
            } else {
                $error = "Error deleting listing: " . mysqli_error($conn);
            }
        }
        
        // Handle toggling premium status
        elseif ($_POST['action'] === 'toggle_premium') {
            $update_query = "UPDATE listings SET is_premium = 1 - is_premium WHERE id = $listing_id";
            
            if (mysqli_query($conn, $update_query)) {
                $message = "Premium status updated successfully.";
            } else {
                $error = "Error updating premium status: " . mysqli_error($conn);
            }
        }
    }
}

// Get listing details
$query = "SELECT l.*, 
            u.name as owner_name, 
            u.email as owner_email,
            u.phone as owner_phone,
            CASE 
                WHEN l.is_verified = 1 THEN 'Approved'
                WHEN l.is_verified = 2 THEN 'Rejected'
                ELSE 'Pending'
            END as status
          FROM listings l
          JOIN users u ON l.user_id = u.id
          WHERE l.id = $listing_id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: my-listings.php?error=listing_not_found");
    exit();
}

$listing = mysqli_fetch_assoc($result);

// Get listing images
$images_query = "SELECT * FROM listing_images WHERE listing_id = $listing_id ORDER BY is_primary DESC";
$images_result = mysqli_query($conn, $images_query);
$images = [];
while ($image = mysqli_fetch_assoc($images_result)) {
    $images[] = $image;
}

// Get listing amenities
$amenities_query = "SELECT a.* FROM amenities a 
                   JOIN listing_amenities la ON a.id = la.amenity_id 
                   WHERE la.listing_id = $listing_id
                   ORDER BY a.name";
$amenities_result = mysqli_query($conn, $amenities_query);
$amenities = [];
while ($amenity = mysqli_fetch_assoc($amenities_result)) {
    $amenities[] = $amenity;
}

// Get admin action history
$history_query = "SELECT aa.*, u.name as admin_name 
                 FROM admin_actions aa
                 JOIN users u ON aa.admin_id = u.id
                 WHERE aa.listing_id = $listing_id
                 ORDER BY aa.created_at DESC";
$history_result = mysqli_query($conn, $history_query);
$history = [];
while ($action = mysqli_fetch_assoc($history_result)) {
    $history[] = $action;
}

// Include header
include '../includes/admin_header.php';
?>

<div class="flex-1 p-8 overflow-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($listing['title']); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></p>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="my-listings.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                <i class="fas fa-arrow-left mr-2"></i> Back to Listings
            </a>
            
            <a href="../owner/edit-listing.php?id=<?php echo $listing_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            
            <button type="button" onclick="confirmDelete()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-trash-alt mr-2"></i> Delete
            </button>
        </div>
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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Image Gallery -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Property Images</h2>
                </div>
                
                <div class="p-4">
                    <?php if (count($images) > 0): ?>
                        <div class="grid grid-cols-1 gap-4">
                            <div id="main-image" class="w-full h-80 rounded-lg overflow-hidden">
                                <img 
                                    src="<?php echo getImageUrl($images[0]['image_url']); ?>" 
                                    alt="Property" 
                                    class="w-full h-full object-cover"
                                >
                            </div>
                            
                            <div class="grid grid-cols-5 gap-2">
                                <?php foreach ($images as $index => $image): ?>
                                    <div 
                                        class="w-full h-20 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 <?php echo $index === 0 ? 'ring-2 ring-blue-500' : ''; ?>"
                                        onclick="showImage('<?php echo getImageUrl($image['image_url']); ?>', this)"
                                    >
                                        <img 
                                            src="<?php echo getImageUrl($image['image_url']); ?>" 
                                            alt="Property" 
                                            class="w-full h-full object-cover"
                                        >
                                        <?php if ($image['is_primary']): ?>
                                            <div class="absolute top-0 right-0 bg-blue-600 text-white text-xs px-1 rounded-bl">Primary</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-image text-4xl mb-3"></i>
                            <p>No images available for this property</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Property Details -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Property Details</h2>
                </div>
                
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3">Description</h3>
                        <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($listing['description']); ?></p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Basic Information</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-center">
                                    <span class="w-32 text-gray-500">Type:</span>
                                    <span class="capitalize"><?php echo htmlspecialchars($listing['type']); ?></span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-32 text-gray-500">Monthly Rent:</span>
                                    <span class="font-semibold">₹<?php echo number_format($listing['price']); ?></span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-32 text-gray-500">Security Deposit:</span>
                                    <span>₹<?php echo number_format($listing['security_deposit']); ?></span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-32 text-gray-500">Premium:</span>
                                    <span>
                                        <?php if ($listing['is_premium']): ?>
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                                Premium Property
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                                Standard Property
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-32 text-gray-500">Available From:</span>
                                    <span><?php echo date('F j, Y', strtotime($listing['available_from'])); ?></span>
                                </li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Property Features</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-center">
                                    <span class="w-40 text-gray-500">Property Size:</span>
                                    <span><?php echo $listing['property_size']; ?> sq ft</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-40 text-gray-500">Furnishing:</span>
                                    <span class="capitalize"><?php echo str_replace('-', ' ', $listing['furnishing_type']); ?></span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-40 text-gray-500">Max Occupants:</span>
                                    <span><?php echo $listing['max_occupants']; ?> persons</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-40 text-gray-500">Bathrooms:</span>
                                    <span>
                                        <?php echo $listing['bathroom_count']; ?> 
                                        <?php echo $listing['is_shared_bathroom'] ? '(Shared)' : '(Private)'; ?>
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <span class="w-40 text-gray-500">Min Duration:</span>
                                    <span><?php echo $listing['min_duration']; ?> month(s)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3">Location</h3>
                        <ul class="text-gray-700">
                            <li class="mb-1"><span class="text-gray-500">Address:</span> <?php echo htmlspecialchars($listing['address']); ?></li>
                            <li class="mb-1"><span class="text-gray-500">Locality:</span> <?php echo htmlspecialchars($listing['locality']); ?></li>
                            <li class="mb-1"><span class="text-gray-500">City:</span> <?php echo htmlspecialchars($listing['city']); ?></li>
                            <li class="mb-1"><span class="text-gray-500">State:</span> <?php echo htmlspecialchars($listing['state']); ?></li>
                            <li><span class="text-gray-500">Zipcode:</span> <?php echo htmlspecialchars($listing['zipcode']); ?></li>
                        </ul>
                        
                        <!-- Add a Map View here if you have Google Maps API integration -->
                    </div>
                </div>
            </div>
            
            <!-- Amenities -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Amenities</h2>
                </div>
                
                <div class="p-6">
                    <?php if (count($amenities) > 0): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No amenities listed for this property.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action History -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Admin Action History</h2>
                </div>
                
                <div class="p-4">
                    <?php if (count($history) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="text-left text-gray-500 text-sm bg-gray-50">
                                    <tr>
                                        <th class="p-3">Action</th>
                                        <th class="p-3">Admin</th>
                                        <th class="p-3">Date</th>
                                        <th class="p-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($history as $action): ?>
                                        <tr>
                                            <td class="p-3">
                                                <?php
                                                $action_class = '';
                                                $action_icon = '';
                                                
                                                switch ($action['action_type']) {
                                                    case 'approve':
                                                        $action_class = 'bg-green-100 text-green-800';
                                                        $action_icon = 'fa-check';
                                                        break;
                                                    case 'reject':
                                                        $action_class = 'bg-red-100 text-red-800';
                                                        $action_icon = 'fa-times';
                                                        break;
                                                    case 'edit':
                                                        $action_class = 'bg-blue-100 text-blue-800';
                                                        $action_icon = 'fa-edit';
                                                        break;
                                                    default:
                                                        $action_class = 'bg-gray-100 text-gray-800';
                                                        $action_icon = 'fa-info-circle';
                                                }
                                                ?>
                                                <span class="<?php echo $action_class; ?> px-2 py-1 rounded-full text-xs font-medium capitalize">
                                                    <i class="fas <?php echo $action_icon; ?> mr-1"></i> <?php echo $action['action_type']; ?>
                                                </span>
                                            </td>
                                            <td class="p-3"><?php echo htmlspecialchars($action['admin_name']); ?></td>
                                            <td class="p-3"><?php echo date('M j, Y, g:i a', strtotime($action['created_at'])); ?></td>
                                            <td class="p-3">
                                                <?php echo !empty($action['notes']) ? htmlspecialchars($action['notes']) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No action history available for this listing.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Listing Status -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Listing Status</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-medium">Current Status:</span>
                        <?php if ($listing['status'] === 'Approved'): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-check-circle mr-1"></i> Approved
                            </span>
                        <?php elseif ($listing['status'] === 'Rejected'): ?>
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-times-circle mr-1"></i> Rejected
                            </span>
                        <?php else: ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-clock mr-1"></i> Pending
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-medium">Listed On:</span>
                        <span><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></span>
                    </div>
                    
                    <div class="mb-6 border-t border-b py-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium">Premium Status:</span>
                            <?php if ($listing['is_premium']): ?>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-star mr-1"></i> Premium
                                </span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                    Standard
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_premium">
                            <button type="submit" class="w-full mt-2 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm">
                                <?php echo $listing['is_premium'] ? 'Remove Premium Status' : 'Mark as Premium'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <?php if ($listing['status'] === 'Pending'): ?>
                        <div class="space-y-3">
                            <button type="button" onclick="showApprovalModal()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-check-circle mr-2"></i> Approve Listing
                            </button>
                            
                            <button type="button" onclick="showRejectionModal()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-times-circle mr-2"></i> Reject Listing
                            </button>
                        </div>
                    <?php elseif ($listing['status'] === 'Approved'): ?>
                        <button type="button" onclick="showRejectionModal()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-times-circle mr-2"></i> Reject Listing
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="showApprovalModal()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-check-circle mr-2"></i> Approve Listing
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Owner Information -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Owner Information</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-lg font-bold mr-3">
                            <?php echo substr($listing['owner_name'], 0, 1); ?>
                        </div>
                        <div>
                            <h3 class="font-medium"><?php echo htmlspecialchars($listing['owner_name']); ?></h3>
                            <p class="text-gray-500 text-sm">Property Owner</p>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex">
                            <span class="w-20 text-gray-500">Email:</span>
                            <a href="mailto:<?php echo htmlspecialchars($listing['owner_email']); ?>" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($listing['owner_email']); ?>
                            </a>
                        </div>
                        
                        <div class="flex">
                            <span class="w-20 text-gray-500">Phone:</span>
                            <a href="tel:<?php echo htmlspecialchars($listing['owner_phone']); ?>" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($listing['owner_phone']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="view-user.php?id=<?php echo $listing['user_id']; ?>" class="block w-full bg-gray-100 hover:bg-gray-200 text-center px-4 py-2 rounded-md text-sm">
                            View Owner Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-bold">Quick Actions</h2>
                </div>
                
                <div class="p-4">
                    <div class="space-y-2">
                        <a href="../owner/edit-listing.php?id=<?php echo $listing_id; ?>" class="block bg-white hover:bg-gray-50 border px-4 py-2 rounded-md text-center">
                            <i class="fas fa-edit mr-2"></i> Edit Listing
                        </a>
                        
                        <a href="../../listing.php?id=<?php echo $listing_id; ?>" target="_blank" class="block bg-white hover:bg-gray-50 border px-4 py-2 rounded-md text-center">
                            <i class="fas fa-eye mr-2"></i> View Public Page
                        </a>
                        
                        <a href="#" onclick="sendMessageToOwner(); return false;" class="block bg-white hover:bg-gray-50 border px-4 py-2 rounded-md text-center">
                            <i class="fas fa-envelope mr-2"></i> Message Owner
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approval-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-6">
            <h3 class="text-lg font-bold mb-4">Approve Listing</h3>
            <p class="mb-4">Are you sure you want to approve this listing? It will be visible to all users.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('approval-modal')" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejection-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-6">
            <h3 class="text-lg font-bold mb-4">Reject Listing</h3>
            <p class="mb-4">Please provide a reason for rejecting this listing:</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                
                <div>
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                    <textarea 
                        id="rejection_reason" 
                        name="rejection_reason" 
                        rows="4"
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Explain why this listing is being rejected..."
                        required
                    ></textarea>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('rejection-modal')" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                        Reject Listing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-6">
            <h3 class="text-lg font-bold mb-4">Confirm Deletion</h3>
            <p class="mb-4">Are you sure you want to delete this listing? This action cannot be undone.</p>
            
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 rounded-md mb-4">
                <p class="text-sm"><i class="fas fa-exclamation-triangle mr-2"></i> Warning: Deleting this listing will permanently remove all associated data including images, amenities, and messages.</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('delete-modal')" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                        Delete Listing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div id="message-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="p-6">
            <h3 class="text-lg font-bold mb-4">Message to Owner</h3>
            
            <form action="send-message.php" method="POST">
                <input type="hidden" name="receiver_id" value="<?php echo $listing['user_id']; ?>">
                <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Your Message</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        rows="4"
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Write your message to the owner..."
                        required
                    ></textarea>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('message-modal')" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Image Gallery
    function showImage(src, element) {
        document.getElementById('main-image').querySelector('img').src = src;
        
        // Remove highlight from all thumbnails
        document.querySelectorAll('#main-image + div > div').forEach(el => {
            el.classList.remove('ring-2', 'ring-blue-500');
        });
        
        // Add highlight to clicked thumbnail
        element.classList.add('ring-2', 'ring-blue-500');
    }
    
    // Modal functions
    function showApprovalModal() {
        document.getElementById('approval-modal').classList.remove('hidden');
    }
    
    function showRejectionModal() {
        document.getElementById('rejection-modal').classList.remove('hidden');
    }
    
    function confirmDelete() {
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    
    function sendMessageToOwner() {
        document.getElementById('message-modal').classList.remove('hidden');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
    
    // Close modals when clicking outside
    document.querySelectorAll('#approval-modal, #rejection-modal, #delete-modal, #message-modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>