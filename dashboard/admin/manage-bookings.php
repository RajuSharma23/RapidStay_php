<?php
// Start session
session_start();

// Include database connection and access control
require_once '../../includes/db_connect.php';
require_once '../../includes/access_control.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page
    header("Location: ../../login.php?redirect=dashboard/admin/manage-bookings.php");
    exit();
}

// Set default values for filtering and pagination
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Build SQL query
$sql = "SELECT b.*, u.name as user_name, l.title as listing_title
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN listings l ON b.listing_id = l.id
        WHERE 1=1";

// Add search condition if search term is provided
if (!empty($searchTerm)) {
    $sql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%' OR u.name LIKE '%$searchTerm%')";
}

// Add status filter if not 'all'
if ($filterStatus !== 'all') {
    $sql .= " AND b.status = '$filterStatus'";
}

// Add order by clause
$sql .= " ORDER BY b.created_at DESC";

// Get total records for pagination
$countSql = "SELECT COUNT(*) as total FROM bookings b LEFT JOIN users u ON b.user_id = u.id WHERE 1=1";

// Add the same search and filter conditions
if (!empty($searchTerm)) {
    $countSql .= " AND (b.listing_id LIKE '%$searchTerm%' OR b.id LIKE '%$searchTerm%' OR u.name LIKE '%$searchTerm%')";
}

if ($filterStatus !== 'all') {
    $countSql .= " AND b.status = '$filterStatus'";
}

// Execute count query
$totalResult = $conn->query($countSql);
$totalRecords = 0;
if ($totalResult && $totalRow = $totalResult->fetch_assoc()) {
    $totalRecords = $totalRow['total'];
}
$totalPages = ceil($totalRecords / $recordsPerPage);

// Add limit for pagination
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute query
$result = $conn->query($sql);

// Include header
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
    <link rel="stylesheet" href="../../assets/css/managebook.css">
    <!-- Keeping the external CSS file reference -->
    <style>
        /* Adding essential styles in case the CSS file doesn't load properly */
        .container {
            max-width: 1200px;
            margin-left: 50px;
            margin-top: 40px;
            background: white;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            border-top: 4px solid #4c57ef;
        }
        
        /* Status Badges */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 90px;
        }
        
        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-confirmed { background-color: #DCFCE7; color: #166534; }
        .status-cancelled { background-color: #FEE2E2; color: #B91C1C; }
        .status-completed { background-color: #E0F2FE; color: #0C4A6E; }
        
        /* Table container with scrolling */
        .table-container {
            max-height: 500px;
            overflow-y: auto !important;
            overflow-x: auto;
            position: relative;
            margin-bottom: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="contener-item">
            <div class="page-header">
                <h1>Booking Management</h1>
                <p>Admin Control Panel - Full Booking Management</p>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-container">
                <form action="" method="get" class="filter-form">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                            placeholder="Search by ID, user or listing" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    
                    <div class="form-group" style="min-width: auto;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-reset">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Bookings Table -->
            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Listing</th>
                                <th>Check-in</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo $row['listing_title']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['move_in_date'])); ?></td>
                                    <td><?php echo $row['duration'] . ' months'; ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_booking.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">View</a>
                                        <a href="edit_booking.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Edit</a>
                                        <a href="delete_booking.php?id=<?php echo $row['id']; ?>" 
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Are you sure you want to permanently delete this booking? This action cannot be undone.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>" 
                                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i>📅</i>
                        <h3>No booking records found</h3>
                        <p>No booking history matches your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Force minimum height on the table container to activate scrolling
        const tableContainer = document.querySelector('.table-container');
        const table = tableContainer.querySelector('table');
        
        // Ensure scrolling is activated
        function ensureScroll() {
            // Force min-height for testing
            if (table && table.offsetHeight < 501) {
                console.log('Adding minimum height to ensure scrollbar appears');
                tableContainer.style.minHeight = '500px';
                
                // Add a small spacer to bottom of table if needed
                if (table.querySelector('tbody') && table.querySelectorAll('tbody tr').length > 0) {
                    const spacer = document.createElement('tr');
                    const spacerCell = document.createElement('td');
                    spacerCell.setAttribute('colspan', '8');
                    spacerCell.style.height = '1px';
                    spacerCell.style.padding = '0';
                    spacer.appendChild(spacerCell);
                    table.querySelector('tbody').appendChild(spacer);
                }
            }
        }
        
        // Run after a slight delay to ensure DOM is fully loaded
        setTimeout(ensureScroll, 100);
    });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>