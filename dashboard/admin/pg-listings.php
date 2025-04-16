<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/my-listings.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';
require_once '../../includes/image_helpers.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$owner = isset($_GET['owner']) ? intval($_GET['owner']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT l.*, 
            (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
            u.name as owner_name,
            u.email as owner_email,
            CASE 
                WHEN l.is_verified = 1 THEN 'Approved'
                WHEN l.is_verified = 2 THEN 'Rejected'
                ELSE 'Pending'
            END as status
          FROM listings l
          JOIN users u ON l.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM listings l JOIN users u ON l.user_id = u.id WHERE 1=1";

// Add filters
if (!empty($type)) {
    $query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
    $count_query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
}

if (!empty($search)) {
    $query .= " AND (l.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR l.city LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
    $count_query .= " AND (l.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR l.city LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

if ($status === 'approved') {
    $query .= " AND l.is_verified = 1";
    $count_query .= " AND l.is_verified = 1";
} elseif ($status === 'pending') {
    $query .= " AND l.is_verified = 0";
    $count_query .= " AND l.is_verified = 0";
} elseif ($status === 'rejected') {
    $query .= " AND l.is_verified = 2";
    $count_query .= " AND l.is_verified = 2";
}

if ($owner > 0) {
    $query .= " AND l.user_id = $owner";
    $count_query .= " AND l.user_id = $owner";
}

// Order by
$query .= " ORDER BY l.created_at DESC";

// Add pagination
$query .= " LIMIT $offset, $items_per_page";

// Execute queries
$result = mysqli_query($conn, $query);
$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_items = $count_data['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN is_verified = 2 THEN 1 ELSE 0 END) as rejected_count,
    COUNT(DISTINCT user_id) as owner_count
    FROM listings";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get all owners for filtering
$owners_query = "SELECT DISTINCT u.id, u.name 
                FROM users u 
                JOIN listings l ON u.id = l.user_id 
                ORDER BY u.name";
$owners_result = mysqli_query($conn, $owners_query);
$owners = [];
while ($owner_row = mysqli_fetch_assoc($owners_result)) {
    $owners[] = $owner_row;
}

// Process bulk actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Bulk approve
    if ($_POST['action'] === 'bulk_approve' && isset($_POST['listing_ids']) && is_array($_POST['listing_ids'])) {
        $listing_ids = array_map('intval', $_POST['listing_ids']);
        $ids_str = implode(',', $listing_ids);
        
        if (!empty($ids_str)) {
            $bulk_update = "UPDATE listings SET is_verified = 1 WHERE id IN ($ids_str)";
            if (mysqli_query($conn, $bulk_update)) {
                $message = count($listing_ids) . " listings approved successfully.";
            } else {
                $error = "Error approving listings: " . mysqli_error($conn);
            }
        }
    }
    
    // Bulk reject
    if ($_POST['action'] === 'bulk_reject' && isset($_POST['listing_ids']) && is_array($_POST['listing_ids']) && isset($_POST['rejection_reason'])) {
        $listing_ids = array_map('intval', $_POST['listing_ids']);
        $reason = mysqli_real_escape_string($conn, $_POST['rejection_reason']);
        $ids_str = implode(',', $listing_ids);
        
        if (!empty($ids_str)) {
            $bulk_update = "UPDATE listings SET is_verified = 2 WHERE id IN ($ids_str)";
            if (mysqli_query($conn, $bulk_update)) {
                // Send notifications
                foreach ($listing_ids as $listing_id) {
                    $notify_query = "INSERT INTO messages (sender_id, receiver_id, listing_id, message, created_at) 
                                     SELECT {$_SESSION['user_id']}, user_id, id, 'Your listing has been rejected: $reason', NOW() 
                                     FROM listings WHERE id = $listing_id";
                    mysqli_query($conn, $notify_query);
                }
                
                $message = count($listing_ids) . " listings rejected successfully.";
            } else {
                $error = "Error rejecting listings: " . mysqli_error($conn);
            }
        }
    }
    
    // Single delete
    if ($_POST['action'] === 'delete' && isset($_POST['listing_id'])) {
        $listing_id = intval($_POST['listing_id']);
        
        // Delete related records
        mysqli_query($conn, "DELETE FROM listing_amenities WHERE listing_id = $listing_id");
        mysqli_query($conn, "DELETE FROM listing_images WHERE listing_id = $listing_id");
        mysqli_query($conn, "DELETE FROM messages WHERE listing_id = $listing_id");
        mysqli_query($conn, "DELETE FROM reviews WHERE listing_id = $listing_id");
        
        // Delete the listing
        if (mysqli_query($conn, "DELETE FROM listings WHERE id = $listing_id")) {
            $message = "Listing deleted successfully.";
        } else {
            $error = "Error deleting listing: " . mysqli_error($conn);
        }
    }
    
    // After processing, redirect to refresh the page (prevents form resubmission)
    if (empty($error)) {
        header("Location: my-listings.php?status=$status&type=$type&owner=$owner&search=$search&page=$page&success=" . urlencode($message));
        exit();
    }
}

// Display success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Include header
include '../includes/admin_header.php';
?>

<div class="flex-1 p-8 overflow-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">All Listings</h1>
            <p class="text-gray-600">Manage and monitor all property listings</p>
        </div>
        <a href="../owner/add-listing.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Listing
        </a>
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
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border-top rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Total Listings</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_count']; ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-home text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border-top rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Approved</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['approved_count']; ?></h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border-top rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Pending</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['pending_count']; ?></h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border-top rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Rejected</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['rejected_count']; ?></h3>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-times text-red-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border-top rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500">Property Owners</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['owner_count']; ?></h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-user text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white border-top rounded-lg shadow-sm p-4 mb-6">
        <form action="my-listings.php" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="md:w-1/5">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="md:w-1/5">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Property Type</label>
                <select name="type" id="type" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Room</option>
                    <option value="pg" <?php echo $type === 'pg' ? 'selected' : ''; ?>>PG</option>
                    <option value="roommate" <?php echo $type === 'roommate' ? 'selected' : ''; ?>>Roommate</option>
                </select>
            </div>
            
            <div class="md:w-1/5">
                <label for="owner" class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                <select name="owner" id="owner" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Owners</option>
                    <?php foreach ($owners as $owner_option): ?>
                        <option value="<?php echo $owner_option['id']; ?>" <?php echo $owner == $owner_option['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($owner_option['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:w-1/5">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    id="search" 
                    placeholder="Title, location, or owner" 
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            
            <div class="md:w-1/5 flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Filter Results
                </button>
            </div>
        </form>
    </div>
    
    <!-- Listings Table -->
    <div class="bg-white border-top rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-bold">Property Listings</h2>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="flex space-x-2">
                    <button 
                        type="button" 
                        onclick="bulkApprove()"
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center"
                    >
                        <i class="fas fa-check mr-1"></i> Approve Selected
                    </button>
                    
                    <button 
                        type="button" 
                        onclick="bulkReject()"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm flex items-center"
                    >
                        <i class="fas fa-times mr-1"></i> Reject Selected
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <form id="bulk-action-form" method="POST">
            <input type="hidden" name="action" id="bulk-action" value="">
            <input type="hidden" name="rejection_reason" id="bulk-rejection-reason" value="">
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-gray-600 text-sm">
                        <tr>
                            <th class="py-3 px-4 text-left">
                                <input type="checkbox" id="select-all" class="rounded">
                            </th>
                            <th class="py-3 px-4 text-left">Property</th>
                            <th class="py-3 px-4 text-left">Owner</th>
                            <th class="py-3 px-4 text-left">Type</th>
                            <th class="py-3 px-4 text-left">Price</th>
                            <th class="py-3 px-4 text-left">Status</th>
                            <th class="py-3 px-4 text-left">Listed On</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($listing = mysqli_fetch_assoc($result)): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <input 
                                            type="checkbox" 
                                            name="listing_ids[]" 
                                            value="<?php echo $listing['id']; ?>" 
                                            class="listing-checkbox rounded"
                                        >
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 rounded-md overflow-hidden flex-shrink-0 mr-3">
                                                <?php 
                                                if (!empty($listing['primary_image'])) {
                                                    $image = getImageUrl($listing['primary_image']);
                                                    echo '<img src="' . htmlspecialchars($image) . '" alt="Listing" class="w-full h-full object-cover">';
                                                } else {
                                                    echo '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="fas fa-home"></i></div>';
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <h4 class="font-medium"><?php echo htmlspecialchars($listing['title']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div class="font-medium"><?php echo htmlspecialchars($listing['owner_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($listing['owner_email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 capitalize"><?php echo $listing['type']; ?></td>
                                    <td class="py-3 px-4">₹<?php echo number_format($listing['price']); ?>/month</td>
                                    <td class="py-3 px-4">
                                        <?php if ($listing['status'] === 'Approved'): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                                Approved
                                            </span>
                                        <?php elseif ($listing['status'] === 'Rejected'): ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                                Rejected
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500">
                                        <?php echo date('M j, Y', strtotime($listing['created_at'])); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex justify-center space-x-2">
                                            <a href="../../listing.php?id=<?php echo $listing['id']; ?>" class="text-blue-600 hover:text-blue-800" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../owner/edit-listing.php?id=<?php echo $listing['id']; ?>" class="text-gray-600 hover:text-gray-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button 
                                                type="button"
                                                onclick="confirmDelete(<?php echo $listing['id']; ?>, '<?php echo htmlspecialchars(addslashes($listing['title'])); ?>')"
                                                class="text-red-600 hover:text-red-800"
                                                title="Delete"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
                                        <p class="text-lg font-medium">No listings found</p>
                                        <p class="text-sm">Try adjusting your filters or search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-4 py-3 border-t flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> listings
                </div>
                
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a 
                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                            class="px-3 py-1 border rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-bold mb-4">Confirm Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="delete-listing-title" class="font-semibold"></span>? This action cannot be undone.</p>
        
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                Cancel
            </button>
            
            <form id="delete-form" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="listing_id" id="delete-listing-id">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div id="rejection-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-bold mb-4">Provide Rejection Reason</h3>
        <p class="mb-4">Please provide a reason for rejecting the selected listings:</p>
        
        <textarea 
            id="rejection-reason" 
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" 
            rows="3" 
            placeholder="Enter rejection reason..."
        ></textarea>
        
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeRejectionModal()" class="px-4 py-2 border rounded-md hover:bg-gray-50">
                Cancel
            </button>
            
            <button 
                type="button" 
                onclick="submitBulkReject()"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md"
            >
                Reject Listings
            </button>
        </div>
    </div>
</div>

<script>
    // Checkbox functionality
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.listing-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    document.querySelectorAll('.listing-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(document.querySelectorAll('.listing-checkbox')).every(c => c.checked);
            document.getElementById('select-all').checked = allChecked;
        });
    });
    
    // Bulk approval
    function bulkApprove() {
        const checkboxes = document.querySelectorAll('.listing-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one listing to approve.');
            return;
        }
        
        if (confirm('Are you sure you want to approve ' + checkboxes.length + ' selected listings?')) {
            document.getElementById('bulk-action').value = 'bulk_approve';
            document.getElementById('bulk-action-form').submit();
        }
    }
    
    // Bulk rejection
    function bulkReject() {
        const checkboxes = document.querySelectorAll('.listing-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one listing to reject.');
            return;
        }
        
        document.getElementById('rejection-modal').classList.remove('hidden');
    }
    
    function submitBulkReject() {
        const reason = document.getElementById('rejection-reason').value.trim();
        if (!reason) {
            alert('Please provide a rejection reason.');
            return;
        }
        
        document.getElementById('bulk-action').value = 'bulk_reject';
        document.getElementById('bulk-rejection-reason').value = reason;
        document.getElementById('bulk-action-form').submit();
        closeRejectionModal();
    }
    
    function closeRejectionModal() {
        document.getElementById('rejection-modal').classList.add('hidden');
        document.getElementById('rejection-reason').value = '';
    }
    
    // Delete functionality
    function confirmDelete(id, title) {
        document.getElementById('delete-listing-id').value = id;
        document.getElementById('delete-listing-title').textContent = title;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
    
    // Close modals if clicking outside
    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    
    document.getElementById('rejection-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectionModal();
        }
    });
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>