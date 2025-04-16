<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a PG owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/owner/my-listings.php");
    exit();
}

// Database connection
require_once '../../includes/db_connect.php';
require_once '../../includes/image_helpers.php';

// Get owner ID
$owner_id = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Build query
$query = "SELECT l.*, 
            (SELECT image_url FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
            CASE 
                WHEN l.is_verified = 1 THEN 'Approved'
                WHEN l.is_verified = 2 THEN 'Rejected'
                ELSE 'Pending'
            END as status
          FROM listings l
          WHERE l.user_id = $owner_id";

$count_query = "SELECT COUNT(*) as total FROM listings l WHERE l.user_id = $owner_id";

// Add filters
if (!empty($type)) {
    $query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
    $count_query .= " AND l.type = '" . mysqli_real_escape_string($conn, $type) . "'";
}

if (!empty($search)) {
    $query .= " AND (l.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR l.city LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
    $count_query .= " AND (l.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR l.city LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
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
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN is_verified = 2 THEN 1 ELSE 0 END) as rejected_count,
    COUNT(*) as total_count
    FROM listings WHERE user_id = $owner_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Process actions like delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['listing_id'])) {
        $listing_id = intval($_POST['listing_id']);
        
        // Check if listing belongs to the owner
        $check_query = "SELECT id FROM listings WHERE id = $listing_id AND user_id = $owner_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Delete related records
            mysqli_query($conn, "DELETE FROM listing_amenities WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM listing_images WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM messages WHERE listing_id = $listing_id");
            mysqli_query($conn, "DELETE FROM reviews WHERE listing_id = $listing_id");
            
            // Delete the listing
            mysqli_query($conn, "DELETE FROM listings WHERE id = $listing_id");
            
            // Redirect to refresh the page
            header("Location: my-listings.php?message=deleted");
            exit();
        }
    }
}

// Include header
include '../includes/owner_header.php';
?>
<style>
    .w-full{
        width: 100%;
        
    }
</style>
<div class="p-6 md:p-8 overflow-auto w-full bg-gray-50" >
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-home text-primary-600 mr-3"></i>My Listings
            </h1>
            <p class="text-gray-600 mt-1">Manage and monitor all your property listings</p>
        </div>
        <a href="add-listing.php" class="bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-lg flex items-center transition-all shadow-sm hover:shadow">
            <i class="fas fa-plus-circle mr-2"></i> Add New Listing
        </a>
    </div>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6 flex items-center shadow-sm">
            <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
            <span>Listing has been successfully deleted.</span>
            <button class="ml-auto text-green-500 hover:text-green-700" onclick="this.parentElement.remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-blue-500 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Total Listings</p>
                    <h3 class="text-3xl font-bold mt-2 text-gray-800"><?php echo $stats['total_count']; ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-home text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-green-500 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Approved</p>
                    <h3 class="text-3xl font-bold mt-2 text-gray-800"><?php echo $stats['approved_count']; ?></h3>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-yellow-500 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Pending</p>
                    <h3 class="text-3xl font-bold mt-2 text-gray-800"><?php echo $stats['pending_count']; ?></h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-red-500 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Rejected</p>
                    <h3 class="text-3xl font-bold mt-2 text-gray-800"><?php echo $stats['rejected_count']; ?></h3>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Filter Listings</h3>
        </div>
        <div class="p-5">
            <form action="my-listings.php" method="GET" class="flex flex-col md:flex-row gap-5">
                <div class="md:w-1/4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Status</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="md:w-1/4">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Property Type</label>
                    <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Types</option>
                        <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Room</option>
                        <option value="pg" <?php echo $type === 'pg' ? 'selected' : ''; ?>>PG</option>
                        <option value="roommate" <?php echo $type === 'roommate' ? 'selected' : ''; ?>>Roommate</option>
                    </select>
                </div>
                
                <div class="md:w-1/3">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            id="search" 
                            placeholder="Search by title or location" 
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                    </div>
                </div>
                
                <div class="md:w-1/6 flex items-end">
                    <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-4 py-2.5 rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Listings Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="py-4 px-6">Property</th>
                        <th class="py-4 px-6">Type</th>
                        <th class="py-4 px-6">Price</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6">Listed On</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($listing = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 mr-4 border border-gray-200">
                                            <?php 
                                            if (!empty($listing['primary_image'])) {
                                                $image = getImageUrl($listing['primary_image']);
                                                echo '<img src="' . htmlspecialchars($image) . '" alt="Listing" class="w-full h-full object-cover">';
                                            } else {
                                                echo '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="fas fa-home fa-lg"></i></div>';
                                            }
                                            ?>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($listing['title']); ?></h4>
                                            <p class="text-sm text-gray-500 mt-1 flex items-center">
                                                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                                <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 capitalize">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $listing['type']; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-medium">₹<?php echo number_format($listing['price']); ?><span class="text-gray-500 text-sm">/month</span></td>
                                <td class="py-4 px-6">
                                    <?php if ($listing['status'] === 'Approved'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Approved
                                        </span>
                                    <?php elseif ($listing['status'] === 'Rejected'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Rejected
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-gray-500">
                                    <div class="flex items-center">
                                        <i class="far fa-calendar-alt mr-2 text-gray-400"></i>
                                        <?php echo date('M j, Y', strtotime($listing['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex justify-center space-x-3">
                                        <a href="../../listing.php?id=<?php echo $listing['id']; ?>" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 rounded-full transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 p-2 rounded-full transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button 
                                            type="button"
                                            onclick="confirmDelete(<?php echo $listing['id']; ?>, '<?php echo htmlspecialchars(addslashes($listing['title'])); ?>')"
                                            class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded-full transition-colors"
                                            title="Delete"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-gray-100 p-6 rounded-full mb-4">
                                        <i class="fas fa-home text-gray-400 text-4xl"></i>
                                    </div>
                                    <p class="text-lg font-medium text-gray-800">No listings found</p>
                                    <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">You haven't created any listings yet or none match your filters.</p>
                                    <a href="add-listing.php" class="mt-6 bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-lg flex items-center transition-all shadow-sm">
                                        <i class="fas fa-plus-circle mr-2"></i> Create Your First Listing
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $items_per_page, $total_items); ?></span> of <span class="font-medium"><?php echo $total_items; ?></span> listings
                </div>
                
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-600 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
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
                            class="px-3 py-1.5 border rounded-md <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-50 text-gray-600'; ?> transition-colors"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-600 transition-colors">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 transform transition-all">
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900">Confirm Deletion</h3>
            <p class="mt-2 text-gray-600">Are you sure you want to delete <span id="delete-listing-title" class="font-semibold text-gray-800"></span>? This action cannot be undone.</p>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            
            <form id="delete-form" method="POST" action="my-listings.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="listing_id" id="delete-listing-id">
                <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Delete Listing
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, title) {
        document.getElementById('delete-listing-id').value = id;
        document.getElementById('delete-listing-title').textContent = title;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
    
    // Close modal if clicking outside
    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    
    // Close alert messages
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const closeButton = alert.querySelector('.close-button');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                alert.remove();
            });
        }
    });
</script>

<?php
// Include footer
include '../includes/owner_footer.php';
?>